<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints;

use Cartware\LoyaltyProgram\Model\LoyaltyOrderPoints as LoyaltyOrderPointsModel;
use Cartware\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LoyaltyOrderPointsModel::class, LoyaltyOrderPointsResource::class);
    }
}
