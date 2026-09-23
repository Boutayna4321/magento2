<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RmaItem extends AbstractDb
{
    public const TABLE_NAME = 'alpinecommerce_rma_item';

    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, \AlpineCommerce\Rma\Api\Data\RmaItemInterface::KEY_ITEM_ID);
    }
}
