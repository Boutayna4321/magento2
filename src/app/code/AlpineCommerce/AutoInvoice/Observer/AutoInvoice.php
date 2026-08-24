<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Invoice;
use Psr\Log\LoggerInterface;

class AutoInvoice implements ObserverInterface
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

        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice->getTotalQty()) {
                return;
            }

            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->setIsPaid(true);

            $order->addStatusHistoryComment(
                __("Invoice #%1 created automatically.", $invoice->getIncrementId())
            )->setIsCustomerNotified(false);

            $order->save();
            $invoice->save();
        } catch (\Throwable $e) {
            $this->logger->error("AutoInvoice error: " . $e->getMessage(), [
                "order_id" => $order->getEntityId(),
                "exception" => $e
            ]);
        }
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            "autoinvoice/general/enabled",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function isPaymentMethodAllowed(string $paymentMethod, int $storeId): bool
    {
        $configuredMethods = $this->scopeConfig->getValue(
            "autoinvoice/general/payment_methods",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($configuredMethods)) {
            return true;
        }

        $allowedMethods = array_map("trim", explode(",", (string) $configuredMethods));

        return in_array($paymentMethod, $allowedMethods, true);
    }
}
