<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance;

use AlpineCommerce\LoyaltyProgram\Model\LoyaltyBalance as LoyaltyBalanceModel;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyBalance as LoyaltyBalanceResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zend_Db_Expr;

class GridCollection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LoyaltyBalanceModel::class, LoyaltyBalanceResource::class);
    }

    /**
     * @return void
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();
        $this->getSelect()->join(
            ['customer' => $this->getTable('customer_entity')],
            'main_table.customer_id = customer.entity_id',
            [
                'customer_firstname' => 'customer.firstname',
                'customer_lastname' => 'customer.lastname',
                'customer_email' => 'customer.email',
            ]
        );
    }
}
