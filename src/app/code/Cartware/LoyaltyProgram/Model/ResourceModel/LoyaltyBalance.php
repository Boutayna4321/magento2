<?php
declare(strict_types=1);

namespace Cartware\LoyaltyProgram\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class LoyaltyBalance extends AbstractDb
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('cartware_loyalty_balance', 'entity_id');
    }
}
