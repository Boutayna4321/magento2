<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Service;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;
use AlpineCommerce\AutoInvoice\Model\Data\InvoiceResult;
use AlpineCommerce\PartialInvoice\Api\PartialInvoiceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for partial invoice processing.
 *
 * Extracted from Observer\AutoPartialInvoice while preserving all existing
 * business logic: backorder check, min-qty rules, shippable-item filtering.
 */
class PartialInvoiceService implements PartialInvoiceInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceService $invoiceService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processOrder(int $orderId): InvoiceResultInterface
    {
        $order = $this->orderRepository->get($orderId);
        $storeId = (int) $order->getStoreId();
        $result = new InvoiceResult();
        $result->setOrderId($orderId);

        if (!$this->isModuleEnabled($storeId)) {
            $result->setStatus('disabled');
            $result->setMessage('PartialInvoice is not enabled for this store.');
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
            $result->setMessage(sprintf('Payment method "%s" is not configured for partial invoicing.', $paymentMethod));
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
        $allowBackorders = $this->isBackordersAllowed($storeId);
        $minQty = $this->getMinQtyToInvoice($storeId);

        foreach ($order->getAllItems() as $item) {
            $qtyOrdered = (float) $item->getQtyOrdered();
            $qtyInvoiced = (float) $item->getQtyInvoiced();
            $qtyAvailable = $qtyOrdered - $qtyInvoiced;

            if ($qtyAvailable <= $minQty) {
                continue;
            }

            if (!$allowBackorders && !$this->canInvoiceItem($item)) {
                continue;
            }

            $itemsQty[$item->getItemId()] = $qtyAvailable;
        }

        if (empty($itemsQty)) {
            $result->setStatus('not_eligible');
            $result->setMessage('No eligible items for partial invoicing.');
            return $result;
        }

        try {
            $invoice = $this->invoiceService->prepareInvoice($order, $itemsQty);
            if (!$invoice->getTotalQty()) {
                $result->setStatus('not_eligible');
                $result->setMessage('Prepared partial invoice has zero quantity.');
                return $result;
            }

            $isGateway = (bool) $payment->getMethodInstance()->isGateway();
            $invoice->setRequestedCaptureCase($isGateway ? '' : Invoice::CAPTURE_OFFLINE);
            $invoice->register();
            if (!$invoice->getIsPaid() && (float) $payment->getAmountPaid() > 0) {
                $invoice->setIsPaid(true);
            }

            $order->addStatusHistoryComment(
                __('Partial Invoice #%1 created automatically for %2 item(s).', $invoice->getIncrementId(), count($itemsQty))
            )->setIsCustomerNotified(false);

            $order->save();
            $invoice->save();

            $result->setInvoiceId((int) $invoice->getId());
            $result->setInvoiceIncrementId($invoice->getIncrementId());
            $result->setStatus('created');
            $result->setMessage(sprintf('Partial Invoice #%s created for %d item(s).', $invoice->getIncrementId(), count($itemsQty)));
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('PartialInvoice error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e,
            ]);
            $result->setStatus('error');
            $result->setMessage('Failed to create partial invoice: ' . $e->getMessage());
            return $result;
        }
    }

    public function getConfig(int $storeId): array
    {
        return [
            'enabled' => $this->isModuleEnabled($storeId),
            'payment_methods' => array_filter(array_map('trim', explode(',', (string) $this->scopeConfig->getValue(
                'partialinvoice/general/payment_methods',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )))),
            'allow_backorders' => $this->isBackordersAllowed($storeId),
            'min_qty_to_invoice' => $this->getMinQtyToInvoice($storeId),
        ];
    }

    public function canInvoice(int $orderId): bool
    {
        $order = $this->orderRepository->get($orderId);
        $storeId = (int) $order->getStoreId();

        if (!$this->isModuleEnabled($storeId)) {
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
        if ($amountPaid <= 0.0 && $grandTotal > 0.0) {
            return false;
        }

        $allowBackorders = $this->isBackordersAllowed($storeId);
        $minQty = $this->getMinQtyToInvoice($storeId);

        foreach ($order->getAllItems() as $item) {
            $qtyOrdered = (float) $item->getQtyOrdered();
            $qtyInvoiced = (float) $item->getQtyInvoiced();
            $qtyAvailable = $qtyOrdered - $qtyInvoiced;

            if ($qtyAvailable <= $minQty) {
                continue;
            }

            if (!$allowBackorders && !$this->canInvoiceItem($item)) {
                continue;
            }
            return true;
        }

        return false;
    }

    private function isModuleEnabled(int $storeId): bool
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
            'partialinvoice/general/payment_methods',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($configuredMethods)) {
            return true;
        }

        $allowedMethods = array_map('trim', explode(',', (string) $configuredMethods));

        return in_array($paymentMethod, $allowedMethods, true);
    }

    private function isBackordersAllowed(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'partialinvoice/general/allow_backorders',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getMinQtyToInvoice(int $storeId): float
    {
        $value = $this->scopeConfig->getValue(
            'partialinvoice/general/min_qty_to_invoice',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value !== null ? (float) $value : 0.0;
    }

    private function canInvoiceItem(\Magento\Sales\Api\Data\OrderItemInterface $item): bool
    {
        $qtyBackordered = (float) $item->getQtyBackordered();
        return $qtyBackordered <= 0;
    }
}
