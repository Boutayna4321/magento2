<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Api;

use AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Customer Care: VIP status, lifetime spend and maintenance operations.
 *
 * @api
 */
interface CustomerCareInterface
{
    /**
     * Get the VIP status of a customer (admin).
     *
     * @param int $customerId
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getVipStatus(int $customerId): VipStatusInterface;

    /**
     * Recompute lifetime spend and VIP level for a customer based on orders.
     *
     * @param int $customerId
     * @return \AlpineCommerce\CustomerCare\Api\Data\VipStatusInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function recalculateVipStatus(int $customerId): VipStatusInterface;

    /**
     * Recompute lifetime spend and VIP level for all customers (cron / admin).
     *
     * @return int number of customers updated
     */
    public function recalculateAll(): int;

    /**
     * Reset VIP attributes to default (used when the program is disabled).
     *
     * @return void
     */
    public function resetAll(): void;
}
