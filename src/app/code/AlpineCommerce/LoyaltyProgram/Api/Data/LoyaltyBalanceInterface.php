<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Api\Data;

interface LoyaltyBalanceInterface
{
    public const CUSTOMER_ID = "customer_id";
    public const POINTS = "points";

    public function getCustomerId(): int;

    public function setCustomerId(int $customerId): self;

    public function getPoints(): int;

    public function setPoints(int $points): self;
}
