<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Service;

use AlpineCommerce\CreditMemo\Api\CreditMemoAutomationInterface;
use AlpineCommerce\CreditMemo\Api\Data\CreditMemoResultInterface;
use AlpineCommerce\CreditMemo\Model\Data\CreditMemoResult;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService as MagentoCreditmemoService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for automatic credit memo creation on order cancellation.
 *
 * Extracted from Plugin\OrderCancelPlugin while preserving all existing
 * business logic and refund safety controls.
 */
class CreditMemoService implements CreditMemoAutomationInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly MagentoCreditmemoService $creditmemoService,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    public function processCancellation(int $orderId): CreditMemoResultInterface
    {
        $order = $this->orderRepository->get($orderId);
        $storeId = (int) $order->getStoreId();
        $result = new CreditMemoResult();
        $result->setOrderId($orderId);

        if (!$this->isModuleEnabled($storeId)) {
            $result->setStatus('disabled');
            $result->setMessage('Auto Credit Memo is not enabled for this store.');
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
            $result->setMessage(sprintf('Payment method "%s" is not configured for auto credit memo.', $paymentMethod));
            return $result;
        }

        if (!$order->canCreditmemo()) {
            $result->setStatus('not_eligible');
            $result->setMessage('Order cannot be credited.');
            return $result;
        }

        $qtys = [];
        foreach ($order->getAllItems() as $item) {
            $qtyToRefund = (float) $item->getQtyOrdered() - (float) $item->getQtyRefunded();
            if ($qtyToRefund > 0) {
                $qtys[$item->getId()] = $qtyToRefund;
            }
        }

        if (empty($qtys)) {
            $result->setStatus('not_eligible');
            $result->setMessage('No refundable items found.');
            return $result;
        }

        try {
            $creditmemo = $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtys]);
            if (!$creditmemo->getTotalQty()) {
                $result->setStatus('not_eligible');
                $result->setMessage('Credit memo has zero quantity.');
                return $result;
            }

            $creditmemo->setRefundToStoreCreditAmount(0);
            $creditmemo->setCommentText(
                __('Credit Memo #%1 created automatically upon cancellation.', $creditmemo->getIncrementId())
            );
            $creditmemo->setCustomerNote(__('Auto-generated credit memo on order cancellation.'));
            $creditmemo->setCustomerNoteNotify(false);

            $refundedAmount = 0.0;
            if ($this->isAutoRefundEnabled($storeId)) {
                $this->creditmemoService->refund($creditmemo, true);
                $refundedAmount = (float) $creditmemo->getGrandTotal();
                $result->setStatus('refunded');
                $result->setMessage('Credit memo created and refund processed.');
            } else {
                $creditmemo->setState(Creditmemo::STATE_OPEN);
                $creditmemo->save();
                $result->setStatus('pending');
                $result->setMessage('Credit memo created (refund pending admin approval).');
            }

            $result->setCreditmemoId((int) $creditmemo->getId());
            $result->setCreditmemoIncrementId($creditmemo->getIncrementId());
            $result->setRefundedAmount($refundedAmount);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('AutoCreditMemo error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'exception' => $e,
            ]);
            $result->setStatus('error');
            $result->setMessage('Failed to create credit memo: ' . $e->getMessage());
            return $result;
        }
    }

    public function getConfig(int $storeId): array
    {
        return [
            'enabled' => $this->isModuleEnabled($storeId),
            'payment_methods' => array_filter(array_map('trim', explode(',', (string) $this->scopeConfig->getValue(
                'autocreditmemo/general/payment_methods',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )))),
            'auto_refund' => $this->isAutoRefundEnabled($storeId),
        ];
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
