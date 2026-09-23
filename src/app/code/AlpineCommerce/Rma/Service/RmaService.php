<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Service;

use AlpineCommerce\Rma\Api\Data\RmaCreationDataInterface;
use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Api\RmaItemRepositoryInterface;
use AlpineCommerce\Rma\Api\RmaRepositoryInterface;
use AlpineCommerce\Rma\Api\RmaServiceInterface;
use AlpineCommerce\Rma\Model\Rma\State;
use AlpineCommerce\Rma\Model\ResourceModel\Rma as RmaResource;
use AlpineCommerce\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use AlpineCommerce\Rma\Model\RmaFactory;
use AlpineCommerce\Rma\Model\RmaItemFactory;
use AlpineCommerce\Rma\Service\ReturnWindow;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for RMA workflow operations.
 *
 * Extracted from controllers and observer while preserving all existing
 * business logic: state transitions, ownership validation, return windows.
 */
class RmaService implements RmaServiceInterface
{
    public function __construct(
        private readonly RmaRepositoryInterface $rmaRepository,
        private readonly RmaItemRepositoryInterface $rmaItemRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreditmemoFactory $creditmemoFactory,
        private readonly CreditmemoService $creditmemoService,
        private readonly ReturnWindow $returnWindow,
         private readonly RmaResource $rmaResource,
         private readonly RmaItemResource $rmaItemResource,
         private readonly RmaFactory $rmaFactory,
        private readonly RmaItemFactory $rmaItemFactory,
         private readonly ScopeConfigInterface $scopeConfig,
         private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
         private readonly LoggerInterface $logger
    ) {
    }

    public function createRma(RmaCreationDataInterface $data, int $customerId): RmaInterface
    {
        $orderId = (int) $data->getOrderId();
        $order = $this->orderRepository->get($orderId);

        if ((int) $order->getCustomerId() !== $customerId) {
            throw new LocalizedException(
                __('You do not have permission to return this order.')
            );
        }

        $storeId = (int) $order->getStoreId();

        if (!$this->isModuleEnabled($storeId)) {
            throw new LocalizedException(__('RMA is not enabled.'));
        }

        $this->assertRmaEligible($order);

        $allowedUntil = $this->returnWindow->computeDeadline(
            $order->getCreatedAt(),
            $this->getAllowReturnDays($storeId)
        );

        if ($this->returnWindow->isExpired($allowedUntil)) {
            throw new LocalizedException(
                __('The return period has expired for this order.')
            );
        }

        $this->assertNoOpenRma($orderId, $customerId);

        $requestedItems = $data->getItems();
        if (empty($requestedItems)) {
            throw new LocalizedException(
                __('Please select at least one item and quantity to return.')
            );
        }

        $itemQtys = $this->validateItemQuantities($order, $requestedItems);
        if (empty($itemQtys)) {
            throw new LocalizedException(
                __('No eligible items could be matched to this order. Check shipped quantities and try again.')
            );
        }

        $rma = $this->rmaFactory->create();
        $rma->setOrderId($orderId);
        $rma->setCustomerId($customerId);
        $rma->setStatus(RmaInterface::STATUS_PENDING);
        $rma->setReason((string) $data->getReason());
        $rma->setRmaEnabled(1);
        $rma->setAllowedUntil($allowedUntil);
        $rma->setCreatedAt(date('Y-m-d H:i:s'));

        $connection = $this->rmaResource->getConnection();
        try {
            $connection->beginTransaction();

            $this->rmaResource->save($rma);
            $rma->setExternalId($this->buildExternalId((int) $rma->getRmaId()));
            $this->rmaResource->save($rma);

            foreach ($itemQtys as $orderItemId => $qty) {
                $orderItem = $order->getItemById($orderItemId);
                $rmaItem = $this->rmaItemFactory->create();
                $rmaItem->setRmaId((int) $rma->getRmaId());
                $rmaItem->setOrderItemId($orderItemId);
                $rmaItem->setProductName((string) $orderItem->getName());
                $rmaItem->setSku((string) $orderItem->getSku());
                $rmaItem->setQtyOrdered((float) $orderItem->getQtyOrdered());
                $rmaItem->setQtyInvoiced((float) $orderItem->getQtyInvoiced());
                $rmaItem->setQtyShipped((float) $orderItem->getQtyShipped());
                $rmaItem->setQtyRequested($qty);
                $rmaItem->setQtyApproved(0.0);
                $rmaItem->setQtyReceived(0.0);
                $rmaItem->setQtyRefunded(0.0);
                $rmaItem->setCreatedAt(date('Y-m-d H:i:s'));
                $this->rmaResource->save($rmaItem);
            }

            $connection->commit();
            return $this->rmaRepository->getById((int) $rma->getRmaId());
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function approve(int $rmaId): RmaInterface
    {
        return $this->changeStatus($rmaId, RmaInterface::STATUS_APPROVED);
    }

    public function reject(int $rmaId, string $reason): RmaInterface
    {
        $rma = $this->changeStatus($rmaId, RmaInterface::STATUS_REJECTED);
        $rma->setReason($reason);
        return $this->rmaRepository->save($rma);
    }

    public function receive(int $rmaId, array $itemQtys): RmaInterface
    {
        $rma = $this->rmaRepository->getById($rmaId);
        $current = (string) $rma->getStatus();

        if (!State::canTransition($current, RmaInterface::STATUS_RECEIVED)) {
            throw new LocalizedException(
                __('The RMA must be approved before goods can be received.')
            );
        }

        foreach ($this->rmaItemRepository->getByRma($rmaId) as $item) {
            $itemId = (int) $item->getOrderItemId();
            $requested = (float) $item->getQtyRequested();
            $received = isset($itemQtys[$itemId]) ? (float) $itemQtys[$itemId] : $requested;

            if ($received < 0.0) {
                $received = 0.0;
            }
            if ($received > $requested) {
                $received = $requested;
            }

            $item->setQtyReceived($received);
            $this->rmaItemRepository->save($item);
        }

        $rma->setStatus(RmaInterface::STATUS_RECEIVED);
        $rma->setUpdatedAt(date('Y-m-d H:i:s'));
        return $this->rmaRepository->save($rma);
    }

    public function refund(int $rmaId): \Magento\Sales\Api\Data\CreditmemoInterface
    {
        $rma = $this->rmaRepository->getById($rmaId);
        $current = (string) $rma->getStatus();

        if (!State::canTransition($current, RmaInterface::STATUS_REFUNDED)) {
            throw new LocalizedException(
                __('A refund can only be processed for a received RMA.')
            );
        }

        if ($rma->getCreditmemoId()) {
            throw new LocalizedException(
                __('A credit memo has already been issued for this RMA (id #%1).', (int) $rma->getCreditmemoId())
            );
        }

        $order = $this->orderRepository->get((int) $rma->getOrderId());

        $qtys = $this->collectRefundableQtys($rmaId);
        if (empty($qtys)) {
            throw new LocalizedException(
                __('No refundable quantity remains on this RMA.')
            );
        }

        $creditmemo = $this->creditmemoFactory->createByOrder($order, ['qtys' => $qtys]);
        $creditmemo->setRestockRefunded(true);
        $creditmemo->setCustomerNoteNotify(false);
        $creditmemo->setCommentText(
            __('Credit memo #%1 created from RMA #%2.', $creditmemo->getIncrementId(), $rmaId)
        );

        $this->creditmemoService->refund($creditmemo);

        $rma->setStatus(RmaInterface::STATUS_REFUNDED);
        $rma->setCreditmemoId((int) $creditmemo->getId());
        $rma->setUpdatedAt(date('Y-m-d H:i:s'));
        $this->rmaRepository->save($rma);

        $this->persistRefundedQtys($rmaId, $qtys);

        return $creditmemo;
    }

    public function close(int $rmaId): RmaInterface
    {
        return $this->changeStatus($rmaId, RmaInterface::STATUS_CLOSED);
    }

    public function changeStatus(int $rmaId, string $status): RmaInterface
    {
        $rma = $this->rmaRepository->getById($rmaId);
        $current = (string) $rma->getStatus();

        if (!State::canTransition($current, $status)) {
            throw new LocalizedException(
                __('Invalid status transition from "%1" to "%2".', $current, $status)
            );
        }

        $rma->setStatus($status);
        $rma->setUpdatedAt(date('Y-m-d H:i:s'));
        return $this->rmaRepository->save($rma);
    }

    public function getByOrder(int $orderId): array
    {
        $connection = $this->rmaResource->getConnection();
        $rmaIds = $connection->fetchCol(
            $connection->select()
                ->from($this->rmaResource->getTable(RmaInterface::RMAS_TABLE_NAME), ['rma_id'])
                ->where('order_id = ?', $orderId)
        );

        if (empty($rmaIds)) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('rma_id', $rmaIds, 'in')
            ->create();

        $list = $this->rmaRepository->getList($searchCriteria);
        return $list->getItems();
    }

    public function getStats(): array
    {
        $connection = $this->rmaResource->getConnection();
        $table = $this->rmaResource->getTable(RmaInterface::RMAS_TABLE_NAME);

        $statuses = $connection->fetchPairs(
            $connection->select()
                ->from($table, ['status', 'COUNT(*)'])
                ->group('status')
        );

        return [
            'total' => array_sum($statuses),
            'by_status' => $statuses,
        ];
    }

    private function collectRefundableQtys(int $rmaId): array
    {
        $qtys = [];
        foreach ($this->rmaItemRepository->getByRma($rmaId) as $item) {
            $available = (float) $item->getQtyReceived() - (float) $item->getQtyRefunded();
            if ($available > 0.0) {
                $qtys[(int) $item->getOrderItemId()] = $available;
            }
        }
        return $qtys;
    }

    private function persistRefundedQtys(int $rmaId, array $refundedQtys): void
    {
        foreach ($this->rmaItemRepository->getByRma($rmaId) as $item) {
            $orderItemId = (int) $item->getOrderItemId();
            if (isset($refundedQtys[$orderItemId])) {
                $item->setQtyRefunded($item->getQtyReceived());
                $this->rmaItemRepository->save($item);
            }
        }
    }

    private function assertRmaEligible(OrderModel $order): void
    {
        if (in_array((string) $order->getState(), ['canceled', 'closed', 'fraud'], true)) {
            throw new LocalizedException(__('This order cannot be returned.'));
        }

        if (!$order->getInvoiceCollection()->getSize()) {
            throw new LocalizedException(
                __('Return is only available for captured (invoiced) orders.')
            );
        }
    }

    private function assertNoOpenRma(int $orderId, int $customerId): void
    {
        $existing = $this->rmaResource->getConnection()
            ->select()
            ->from($this->rmaResource->getTable(RmaInterface::RMAS_TABLE_NAME), ['rma_id'])
            ->where('order_id = ?', $orderId)
            ->where('customer_id = ?', $customerId)
            ->where('status != ?', RmaInterface::STATUS_CLOSED)
            ->limit(1);

        $result = $this->rmaResource->getConnection()->fetchOne($existing);
        if ($result) {
            throw new LocalizedException(
                __('A return request is already in progress for this order.')
            );
        }
    }

    private function validateItemQuantities(OrderModel $order, array $requestedItems): array
    {
        $alreadyReturned = $this->loadAlreadyReturnedQtyByOrderItem((int) $order->getId());

        $itemQtys = [];
        foreach ($requestedItems as $orderItemId => $qtyRaw) {
            $orderItemId = (int) $orderItemId;
            $orderItem = $order->getItemById($orderItemId);
            if (!$orderItem) {
                continue;
            }

            $qtyShipped = (float) $orderItem->getQtyShipped();
            $availableReturn = $qtyShipped - (float) ($alreadyReturned[$orderItemId] ?? 0.0);

            $qty = (float) $qtyRaw;
            if ($qty <= 0.0 || $qty > $availableReturn) {
                continue;
            }

            $itemQtys[$orderItemId] = $qty;
        }

        return $itemQtys;
    }

    private function loadAlreadyReturnedQtyByOrderItem(int $orderId): array
    {
        $connection = $this->rmaItemResource->getConnection();
        $select = $connection->select()
            ->from(
                ['i' => $this->rmaItemResource->getTable('alpinecommerce_rma_item')],
                ['order_item_id', 'qty_requested']
            )
            ->joinInner(
                ['r' => $this->rmaResource->getTable(RmaInterface::RMAS_TABLE_NAME)],
                'i.rma_id = r.rma_id',
                []
            )
            ->where('r.order_id = ?', $orderId)
            ->where('r.status != ?', RmaInterface::STATUS_CLOSED);

        $rows = $connection->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $orderItemId = (int) $row['order_item_id'];
            $result[$orderItemId] = ($result[$orderItemId] ?? 0.0) + (float) $row['qty_requested'];
        }

        return $result;
    }

    private function isModuleEnabled(int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag(
            'rma/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getAllowReturnDays(int $storeId): int
    {
        $value = $this->scopeConfig->getValue(
            'rma/general/allow_return_days',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value !== null ? (int) $value : 30;
    }

    private function buildExternalId(int $rmaId): string
    {
        $prefix = (string) $this->scopeConfig->getValue(
            'rma/general/rma_prefix',
            ScopeInterface::SCOPE_STORE
        );

        return $prefix . '-' . $rmaId;
    }
}
