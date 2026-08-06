<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance;

use Cartware\LoyaltyProgram\Model\LoyaltyBalance as LoyaltyBalanceModel;
use Cartware\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance as LoyaltyBalanceResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LoyaltyBalanceModel::class, LoyaltyBalanceResource::class);
    }
}
