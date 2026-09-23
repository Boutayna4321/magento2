<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\ResourceModel\RmaItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = \AlpineCommerce\Rma\Api\Data\RmaItemInterface::KEY_ITEM_ID;
    protected $_eventPrefix = 'alpinecommerce_rma_item_collection';
    protected $_eventObject = 'rma_item_collection';

    protected function _construct(): void
    {
        $this->_init(
            \AlpineCommerce\Rma\Model\RmaItem::class,
            \AlpineCommerce\Rma\Model\ResourceModel\RmaItem::class
        );
    }
}
