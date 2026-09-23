<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Service;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;
use AlpineCommerce\AutoInvoice\Api\InvoiceAutomationInterface;
use AlpineCommerce\AutoInvoice\Model\Data\InvoiceResultFactory;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for automatic invoice processing.
 *
 * Extracted from Observer\AutoInvoice while preserving all existing
 * business logic and behavior.
 */
class InvoiceAutomationService implements InvoiceAutomationInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceService $invoiceService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly InvoiceResultFactory $resultFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processOrder(int $orderId): InvoiceResultInterface
    {
        $order = $this->orderRepository->get($orderId);
        $storeId = (int) $order->getStoreId();
        $result = $this->resultFactory->create();
        $result->setOrderId($orderId);

        if (!$this->isModuleEnabled($storeId)) {
            $result->setStatus('disabled');
            $result->setMessage('AutoInvoice is not enabled for this store.');
            return $result;
        }

        if ($this->isPartialInvoiceEnabled($storeId)) {
            $result->setStatus('partial_invoice_owned');
            $result->setMessage('PartialInvoice module is enabled; AutoInvoice is deferred.');
            return $result;
        }

        if (!$order->canInvoice()) {
            $result->setStatus('not_eligible');
            $result->setMessage('Order cannot be invoiced.');
            return $result;
        }

        $payment = $order->getPayment();
        if (!$payment) {
            $result->setStatus('not_eligible');
            $result->setMessage('Order has no payment.');
            return $result;
        }

        $paymentMethod = (string) $payment->getMethodInstance()->getCode();
        if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
            $result->setStatus('not_eligible');
            $result->setMessage(sprintf('Payment method "%s" is not configured for auto-invoicing.', $paymentMethod));
            return $result;
        }

        $amountPaid = (float) $payment->getAmountPaid();
        $grandTotal = (float) $order->getGrandTotal();
        if ($amountPaid <= 0.0 && $grandTotal > 0.0) {
            $result->setStatus('not_eligible');
            $result->setMessage('Order cannot be invoiced: payment not captured.');
            return $result;
        }

        $itemsQty = [];
        foreach ($order->getAllItems() as $item) {
            $qtyOrdered = (float) $item->getQtyOrdered();
            $qtyInvoiced = (float) $item->getQtyInvoiced();
            $qtyAvailable = $qtyOrdered - $qtyInvoiced;
            if ($qtyAvailable <= 0) {
                continue;
            }
            $itemsQty[$item->getItemId()] = $qtyAvailable;
        }

        if (empty($itemsQty)) {
            $result->setStatus('not_eligible');
            $result->setMessage('No invoiceable items remaining.');
            return $result;
        }

        try {
            $invoice = $this->invoiceService->prepareInvoice($order, $itemsQty);
            if (!$invoice->getTotalQty()) {
                $result->setStatus('not_eligible');
                $result->setMessage('Prepared invoice has zero quantity.');
                return $result;
            }

            $isGateway = (bool) $payment->getMethodInstance()->isGateway();
            $invoice->setRequestedCaptureCase($isGateway ? '' : Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            if (!$invoice->getIsPaid() && (float) $payment->getAmountPaid() > 0) {
                $invoice->setIsPaid(true);
            }

            $order->addStatusHistoryComment(
                __('Invoice #%1 created automatically.', $invoice->getIncrementId())
            )->setIsCustomerNotified(false);

            $order->save();
            $invoice->save();

            $result->setInvoiceId((int) $invoice->getId());
            $result->setInvoiceIncrementId($invoice->getIncrementId());
            $result->setStatus('created');
            $result->setMessage(sprintf('Invoice #%s created automatically.', $invoice->getIncrementId()));
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('AutoInvoice error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e,
            ]);
            $result->setStatus('error');
            $result->setMessage('Failed to create invoice: ' . $e->getMessage());
            return $result;
        }
    }

    public function getConfig(int $storeId): array
    {
        return [
            'enabled' => $this->isModuleEnabled($storeId),
            'payment_methods' => array_filter(array_map('trim', explode(',', (string) $this->scopeConfig->getValue(
                'autoinvoice/general/payment_methods',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )))),
            'capture_mode' => $this->scopeConfig->getValue(
                'autoinvoice/general/payment_methods',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ? 'offline' : 'none',
        ];
    }

    public function canInvoice(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        $storeId = (int) $order->getStoreId();

        if (!$this->isModuleEnabled($storeId) || $this->isPartialInvoiceEnabled($storeId)) {
            return false;
        }

        if (!$order->canInvoice()) {
            return false;
        }

        $payment = $order->getPayment();
        if (!$payment) {
            return false;
        }

        $paymentMethod = (string) $payment->getMethodInstance()->getCode();
        if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
            return false;
        }

        $amountPaid = (float) $payment->getAmountPaid();
        $grandTotal = (float) $order->getGrandTotal();
        return $amountPaid > 0.0 || $grandTotal <= 0.0;
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'autoinvoice/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function isPartialInvoiceEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'partialinvoice/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function isPaymentMethodAllowed(string $paymentMethod, int $storeId): bool
    {
        $configuredMethods = $this->scopeConfig->getValue(
            'autoinvoice/general/payment_methods',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($configuredMethods)) {
            return true;
        }

        $allowedMethods = array_map('trim', explode(',', (string) $configuredMethods));

        return in_array($paymentMethod, $allowedMethods, true);
    }
}
