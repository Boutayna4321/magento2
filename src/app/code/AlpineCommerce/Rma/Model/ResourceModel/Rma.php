<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Rma extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alpinecommerce_rma', 'rma_id');
    }
}
