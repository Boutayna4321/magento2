<?php
declare(strict_types=1);

namespace AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints;

use AlpineCommerce\LoyaltyProgram\Model\LoyaltyOrderPoints as LoyaltyOrderPointsModel;
use AlpineCommerce\LoyaltyProgram\Model\ResourceModel\LoyaltyOrderPoints as LoyaltyOrderPointsResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class GridCollection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LoyaltyOrderPointsModel::class, LoyaltyOrderPointsResource::class);
    }

    /**
     * Filter history by customer ID through the sales_order relation.
     *
     * @param int $customerId
     * @return void
     */
    public function addCustomerFilter(int $customerId): void
    {
        $this->getSelect()->join(
            ['sales_order' => $this->getTable('sales_order')],
            'main_table.order_id = sales_order.entity_id AND sales_order.customer_id = ' . (int) $customerId,
            []
        );
    }
}
