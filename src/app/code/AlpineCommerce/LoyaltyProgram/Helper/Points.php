<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Helper;

class Points
{
    public const DIVISOR = 10.0;

    public const DEFAULT_POINTS_PER_TEN = 1.0;

    /**
     * Number of points earned for a given amount.
     *
     * Rule: 1 point per 10 MAD spent, scaled by the conversion rate
     * configured by the merchant (points per 10 MAD).
     *
     * @param float $amount
     * @param float $pointsPerTen
     * @return int
     */
    public function calculatePoints(float $amount, float $pointsPerTen = self::DEFAULT_POINTS_PER_TEN): int
    {
        if ($pointsPerTen <= 0) {
            return 0;
        }

        return (int) floor($amount * $pointsPerTen / self::DIVISOR);
    }
}
