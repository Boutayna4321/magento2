<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model;

use Cartware\LoyaltyProgram\Api\Data\LoyaltyBalanceInterface;
use Magento\Framework\Model\AbstractModel;

class LoyaltyBalance extends AbstractModel implements LoyaltyBalanceInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\Cartware\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance::class);
    }

    /**
     * @return int
     */
    public function getCustomerId(): int
    {
        return (int) $this->getData('customer_id');
    }

    /**
     * @param int $customerId
     * @return LoyaltyBalanceInterface
     */
    public function setCustomerId(int $customerId): LoyaltyBalanceInterface
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * @return int
     */
    public function getPoints(): int
    {
        return (int) $this->getData('points');
    }

    /**
     * @param int $points
     * @return LoyaltyBalanceInterface
     */
    public function setPoints(int $points): LoyaltyBalanceInterface
    {
        return $this->setData('points', $points);
    }
}
