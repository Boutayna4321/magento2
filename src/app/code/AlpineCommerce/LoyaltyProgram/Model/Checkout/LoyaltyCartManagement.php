<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\Checkout;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Api\LoyaltyCartManagementInterface;
use AlpineCommerce\LoyaltyProgram\Model\Total\Quote\LoyaltyDiscount;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsInterface;

class LoyaltyCartManagement implements LoyaltyCartManagementInterface
{
    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     */
    public function __construct(
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly CartTotalRepositoryInterface $cartTotalRepository,
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function setPointsUsed(int $cartId, int $points): TotalsInterface
    {
        $quote = $this->quoteRepository->getActive($cartId);

        $available = 0;
        if ($quote->getCustomerId()) {
            $available = max(
                0,
                (int) $this->balanceRepository->getByCustomerId((int) $quote->getCustomerId())->getPoints()
            );
        }

        $points = max(0, min($points, $available));

        $quote->setData(LoyaltyDiscount::QUOTE_FIELD_POINTS_USED, $points);
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);

        return $this->cartTotalRepository->get($cartId);
    }
}
