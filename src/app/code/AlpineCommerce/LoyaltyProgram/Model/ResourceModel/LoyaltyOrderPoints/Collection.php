<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints;

use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPoints as LoyaltyOrderPointsModel;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
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
