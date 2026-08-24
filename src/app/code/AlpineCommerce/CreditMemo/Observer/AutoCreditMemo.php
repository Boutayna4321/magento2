<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class AutoCreditMemo implements ObserverInterface
{
    public function __construct(
        private readonly CreditmemoService $creditmemoService,
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

        if (!$order->canCreditmemo()) {
            return;
        }

        try {
            $creditmemo = $this->creditmemoService->createCreditmemo($order);
            if (!$creditmemo->getTotalQty()) {
                return;
            }

            $creditmemo->setRefundToStoreCreditAmount(0);
            $creditmemo->setAutomaticallyRefunded($this->isAutoRefundEnabled($storeId) ? 1 : 0);
            $creditmemo->setCommentText(__('Credit Memo #%1 created automatically.', $creditmemo->getIncrementId()));
            $creditmemo->setIsCustomerNotified(false);

            $this->creditmemoService->refund($creditmemo);

            $order->addStatusHistoryComment(
                __('Credit Memo #%1 created automatically.', $creditmemo->getIncrementId())
            )->setIsCustomerNotified(false);

            $order->save();
        } catch (\Throwable $e) {
            $this->logger->error('AutoCreditMemo error: ' . $e->getMessage(), [
                'order_id' => $order->getEntityId(),
                'exception' => $e
            ]);
        }
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'autocreditmemo/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function isPaymentMethodAllowed(string $paymentMethod, int $storeId): bool
    {
        $configuredMethods = $this->scopeConfig->getValue(
            'autocreditmemo/general/payment_methods',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($configuredMethods)) {
            return true;
        }

        $allowedMethods = array_map('trim', explode(',', (string) $configuredMethods));

        return in_array($paymentMethod, $allowedMethods, true);
    }

    private function isAutoRefundEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'autocreditmemo/general/auto_refund',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
