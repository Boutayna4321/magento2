<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Api;

use Magento\Quote\Api\Data\TotalsInterface;

/**
 * Manages loyalty points applied to a customer cart.
 *
 * @api
 */
interface LoyaltyCartManagementInterface
{
    /**
     * Set the number of loyalty points used for the given cart.
     *
     * The value is clamped server-side to the customer's available balance.
     *
     * @param int $cartId
     * @param int $points
     * @return TotalsInterface Recalculated cart totals.
     */
    public function setPointsUsed(int $cartId, int $points): TotalsInterface;
}
