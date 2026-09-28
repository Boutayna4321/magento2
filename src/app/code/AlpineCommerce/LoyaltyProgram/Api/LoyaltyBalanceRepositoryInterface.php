<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Api;

use AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;

interface LoyaltyBalanceRepositoryInterface
{
    /**
     * Get the loyalty balance of a customer.
     *
     * Returns a balance object even when the customer has no record yet
     * (points = 0).
     *
     * @param int $customerId
     * @return \AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface
     */
    public function getByCustomerId(int $customerId): LoyaltyBalanceInterface;

    /**
     * Persist a loyalty balance.
     *
     * @param \AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface $balance
     * @return \AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface
     */
    public function save(LoyaltyBalanceInterface $balance): LoyaltyBalanceInterface;
}
