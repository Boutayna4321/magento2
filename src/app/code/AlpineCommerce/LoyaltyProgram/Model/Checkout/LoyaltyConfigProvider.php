<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\Checkout;

use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Model\Session as CustomerSession;

class LoyaltyConfigProvider implements ConfigProviderInterface
{
    public const REDEMPTION_RATE = 1.0;

    /**
     * @param LoyaltyBalanceRepositoryInterface $balanceRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private readonly LoyaltyBalanceRepositoryInterface $balanceRepository,
        private readonly CustomerSession $customerSession
    ) {
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $customerId = (int) $this->customerSession->getCustomerId();
        $available = $customerId
            ? $this->balanceRepository->getByCustomerId($customerId)->getPoints()
            : 0;

        return [
            'loyaltyPoints' => [
                'available' => max(0, $available),
                'redemptionRate' => self::REDEMPTION_RATE,
            ],
        ];
    }
}
