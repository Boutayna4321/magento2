<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model;

use AlpineCommerce\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;
use AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance as LoyaltyBalanceResource;

class LoyaltyBalanceRepository implements LoyaltyBalanceRepositoryInterface
{
    /**
     * @param LoyaltyBalanceFactory $balanceFactory
     * @param LoyaltyBalanceResource $balanceResource
     */
    public function __construct(
        private readonly LoyaltyBalanceFactory $balanceFactory,
        private readonly LoyaltyBalanceResource $balanceResource
    ) {
    }

    /**
     * @param int $customerId
     * @return LoyaltyBalanceInterface
     */
    public function getByCustomerId(int $customerId): LoyaltyBalanceInterface
    {
        $balance = $this->balanceFactory->create();
        $this->balanceResource->load($balance, $customerId, 'customer_id');

        if (!$balance->getCustomerId()) {
            $balance->setCustomerId($customerId)->setPoints(0);
        }

        return $balance;
    }

    /**
     * @param LoyaltyBalanceInterface $balance
     * @return LoyaltyBalanceInterface
     */
    public function save(LoyaltyBalanceInterface $balance): LoyaltyBalanceInterface
    {
        $this->balanceResource->save($balance);

        return $balance;
    }
}
