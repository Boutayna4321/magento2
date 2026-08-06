<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model\Total\Quote;

use Cartware\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

class LoyaltyDiscount extends AbstractTotal
{
    public const TOTAL_CODE = 'cartware_loyalty_discount';

    public const QUOTE_FIELD_POINTS_USED = 'cartware_loyalty_points_used';

    public const REDEMPTION_RATE = 1.0;

    /**
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     */
    public function __construct(
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository
    ) {
        $this->setCode(self::TOTAL_CODE);
    }

    /**
     * @inheritDoc
     */
    public function _resetState(): void
    {
        parent::_resetState();
        $this->setCode(self::TOTAL_CODE);
    }

    /**
     * Apply the loyalty discount. Everything is recomputed server-side from
     * the available balance so a client cannot cheat the amount.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        $pointsRequested = (int) $quote->getData(self::QUOTE_FIELD_POINTS_USED);
        if ($pointsRequested <= 0) {
            return $this;
        }

        $discount = $this->calculateDiscount($quote, $pointsRequested, $total);
        if ($discount <= 0) {
            return $this;
        }

        $quote->setData(self::QUOTE_FIELD_POINTS_USED, (int) round($discount / self::REDEMPTION_RATE));
        $total->addTotalAmount(self::TOTAL_CODE, -$discount);
        $total->addBaseTotalAmount(self::TOTAL_CODE, -$discount);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        $pointsUsed = (int) $quote->getData(self::QUOTE_FIELD_POINTS_USED);
        if ($pointsUsed <= 0) {
            return [];
        }

        $amount = -(float) ($pointsUsed * self::REDEMPTION_RATE);

        return [
            'code' => self::TOTAL_CODE,
            'title' => __('Loyalty Discount'),
            'value' => $amount,
        ];
    }

    /**
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('Loyalty Discount');
    }

    /**
     * Server-side discount: clamp the requested points to the customer's
     * balance and to the order grand total.
     *
     * @param Quote $quote
     * @param int $pointsRequested
     * @param Total $total
     * @return float
     */
    private function calculateDiscount(Quote $quote, int $pointsRequested, Total $total): float
    {
        $available = $this->getAvailablePoints($quote);

        $points = min($pointsRequested, $available);
        $discount = $points * self::REDEMPTION_RATE;

        $currentTotal = max(0, (float) array_sum($total->getAllTotalAmounts()));

        return min($discount, $currentTotal);
    }

    /**
     * @param Quote $quote
     * @return int
     */
    private function getAvailablePoints(Quote $quote): int
    {
        if (!$quote->getCustomerId()) {
            return 0;
        }

        return max(
            0,
            (int) $this->balanceRepository->getByCustomerId((int) $quote->getCustomerId())->getPoints()
        );
    }
}
