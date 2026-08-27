<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Plugin;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class OrderCancelPlugin
{
    public function __construct(
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoService $creditmemoService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function afterCancel(Order $subject, bool $result): bool
    {
        if (!$result) {
            return $result;
        }

        $storeId = (int) $subject->getStoreId();
        if (!$this->isModuleEnabled($storeId)) {
            return $result;
        }

        $payment = $subject->getPayment();
        if (!$payment) {
            return $result;
        }

        $paymentMethod = (string) $payment->getMethodInstance()->getCode();
        if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
            return $result;
        }

        if (!$subject->canCreditmemo()) {
            return $result;
        }

        $qtys = [];
        foreach ($subject->getAllItems() as $item) {
            $qtyToRefund = (float) $item->getQtyOrdered() - (float) $item->getQtyRefunded();
            if ($qtyToRefund > 0) {
                $qtys[$item->getId()] = $qtyToRefund;
            }
        }

        try {
            $creditmemo = $this->creditmemoFactory->createByOrder($subject, ["qtys" => $qtys]);
            if (!$creditmemo->getTotalQty()) {
                return $result;
            }

            $creditmemo->setRefundToStoreCreditAmount(0);
            $creditmemo->setCommentText(
                __("Credit Memo #%1 created automatically upon cancellation.", $creditmemo->getIncrementId())
            );
            $creditmemo->setCustomerNote(__("Auto-generated credit memo on order cancellation."));
            $creditmemo->setCustomerNoteNotify(false);

            if ($this->isAutoRefundEnabled($storeId)) {
                $this->creditmemoService->refund($creditmemo);
            } else {
                $creditmemo->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
                $creditmemo->save();
            }
        } catch (\Throwable $e) {
            $this->logger->error("AutoCreditMemo error: " . $e->getMessage(), [
                "order_id" => $subject->getEntityId(),
                "exception" => $e,
            ]);
        }

        return $result;
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            "autocreditmemo/general/enabled",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function isPaymentMethodAllowed(string $paymentMethod, int $storeId): bool
    {
        $configuredMethods = $this->scopeConfig->getValue(
            "autocreditmemo/general/payment_methods",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($configuredMethods)) {
            return true;
        }

        $allowedMethods = array_map("trim", explode(",", (string) $configuredMethods));

        return in_array($paymentMethod, $allowedMethods, true);
    }

    private function isAutoRefundEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            "autocreditmemo/general/auto_refund",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
