<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo;

use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo as StoreInfoResource;
use AlpineCommerce\StorePickup\Model\StoreInfo;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(StoreInfo::class, StoreInfoResource::class);
    }
}
