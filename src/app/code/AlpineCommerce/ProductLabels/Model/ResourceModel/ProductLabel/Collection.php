<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel;

use AlpineCommerce\ProductLabels\Model\ProductLabel as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(
            Model::class,
            \AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel::class
        );
    }
}
