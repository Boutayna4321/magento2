<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;

class AutoPartialInvoice implements ObserverInterface
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();

        if (!$order instanceof OrderInterface) {
            return;
        }

        if ((int) $order->getTotalInvoiced() > 0) {
            return;
        }

        $storeId = (int) $order->getStoreId();

        if (!$this->isModuleEnabled($storeId)) {
            return;
        }

        $paymentMethod = (string) $order->getPayment()->getMethodInstance()->getCode();
        if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
            return;
        }

        if (!$order->canInvoice()) {
            return;
        }

        $itemsQty = [];
        $allowBackorders = $this->isBackordersAllowed($storeId);
        $minQty = (float) $this->getMinQtyToInvoice($storeId);

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
            return;
        }

        try {
            $invoice = $this->invoiceService->prepareInvoice($order, $itemsQty);
            if (!$invoice->getTotalQty()) {
                return;
            }

            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->setIsPaid(true);

            $order->addStatusHistoryComment(
                __('Partial Invoice #%1 created automatically for %2 item(s).', $invoice->getIncrementId(), count($itemsQty))
            )->setIsCustomerNotified(false);

            $order->save();
            $invoice->save();
        } catch (\Throwable $e) {
            $this->logger->error('PartialInvoice error: ' . $e->getMessage(), [
                'order_id' => $order->getEntityId(),
                'exception' => $e
            ]);
        }
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
