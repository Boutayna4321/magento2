<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Model\ResourceModel\Store;

use AlpineCommerce\StoreLocator\Model\Store as StoreModel;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store as StoreResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(StoreModel::class, StoreResource::class);
    }
}
