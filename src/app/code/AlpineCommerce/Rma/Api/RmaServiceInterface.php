<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api;

use AlpineCommerce\Rma\Api\Data\RmaCreationDataInterface;
use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Service contract for RMA workflow operations.
 *
 * @api
 */
interface RmaServiceInterface
{
    /**
     * Create a new RMA request.
     *
     * @param RmaCreationDataInterface $data
     * @param int $customerId
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function createRma(RmaCreationDataInterface $data, int $customerId): RmaInterface;

    /**
     * Approve an RMA.
     *
     * @param int $rmaId
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function approve(int $rmaId): RmaInterface;

    /**
     * Reject an RMA.
     *
     * @param int $rmaId
     * @param string $reason
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function reject(int $rmaId, string $reason): RmaInterface;

    /**
     * Mark RMA items as received.
     *
     * @param int $rmaId
     * @param array $itemQtys Order item ID => received quantity
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function receive(int $rmaId, array $itemQtys): RmaInterface;

    /**
     * Process a credit memo refund for an RMA.
     *
     * @param int $rmaId
     * @return \Magento\Sales\Api\Data\CreditmemoInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function refund(int $rmaId): \Magento\Sales\Api\Data\CreditmemoInterface;

    /**
     * Close an RMA (final state).
     *
     * @param int $rmaId
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function close(int $rmaId): RmaInterface;

    /**
     * Change RMA status (used by admin controller thin wrapper for approve/reject/close).
     *
     * @param int $rmaId
     * @param string $status
     * @return RmaInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function changeStatus(int $rmaId, string $status): RmaInterface;

    /**
     * Get all RMAs for an order.
     *
     * @param int $orderId
     * @return RmaInterface[]
     */
    public function getByOrder(int $orderId): array;

    /**
     * Get RMA statistics.
     *
     * @return array
     */
    public function getStats(): array;
}
