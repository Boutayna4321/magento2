<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Service;

class PointsCalculator
{
    public const DIVISOR = 10.0;

    public const DEFAULT_POINTS_PER_TEN = 1.0;

    public function calculatePoints(float $amount, float $pointsPerTen = self::DEFAULT_POINTS_PER_TEN): int
    {
        if ($pointsPerTen <= 0) {
            return 0;
        }

        return (int) floor($amount * $pointsPerTen / self::DIVISOR);
    }
}
