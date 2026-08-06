<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Model\ResourceModel\StoreInfo;

use Cartware\StorePickup\Model\ResourceModel\StoreInfo as StoreInfoResource;
use Cartware\StorePickup\Model\StoreInfo;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(StoreInfo::class, StoreInfoResource::class);
    }
}
