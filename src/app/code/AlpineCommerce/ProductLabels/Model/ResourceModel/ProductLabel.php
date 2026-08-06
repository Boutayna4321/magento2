<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model\ResourceModel;

class ProductLabel extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct(): void
    {
        $this->_init("alphacommerce_product_label", "entity_id");
    }
}
