<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Api\Data;

interface LoyaltyBalanceInterface
{
    /**
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): LoyaltyBalanceInterface;

    /**
     * @return int
     */
    public function getPoints(): int;

    /**
     * @param int $points
     * @return $this
     */
    public function setPoints(int $points): LoyaltyBalanceInterface;
}
