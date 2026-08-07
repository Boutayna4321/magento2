<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Model\ResourceModel;

use AlpineCommerce\StoreLocator\Model\Store as StoreModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Store extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_store_locator_store', 'entity_id');
    }
}
