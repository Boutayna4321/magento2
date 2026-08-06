<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Api;

use Cartware\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;

interface LoyaltyBalanceRepositoryInterface
{
    /**
     * Get the loyalty balance of a customer.
     *
     * Returns a balance object even when the customer has no record yet
     * (points = 0).
     *
     * @param int $customerId
     * @return LoyaltyBalanceInterface
     */
    public function getByCustomerId(int $customerId): LoyaltyBalanceInterface;

    /**
     * Persist a loyalty balance.
     *
     * @param LoyaltyBalanceInterface $balance
     * @return LoyaltyBalanceInterface
     */
    public function save(LoyaltyBalanceInterface $balance): LoyaltyBalanceInterface;
}
