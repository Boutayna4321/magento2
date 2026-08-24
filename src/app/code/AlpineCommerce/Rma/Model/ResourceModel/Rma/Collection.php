<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\ResourceModel\Rma;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'rma_id';
    protected $_eventPrefix = 'alpinecommerce_rma_collection';
    protected $_eventObject = 'rma_collection';

    protected function _construct(): void
    {
        $this->_init(
            \AlpineCommerce\Rma\Model\Rma::class,
            \AlpineCommerce\Rma\Model\ResourceModel\Rma::class
        );
    }
}
