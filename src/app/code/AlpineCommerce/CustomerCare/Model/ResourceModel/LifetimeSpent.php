<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Aggregates lifetime spend from completed orders for a customer.
 */
class LifetimeSpent
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function sumCompletedOrders(int $customerId): float
    {
        $connection = $this->resource->getConnection('sales');
        $select = $connection->select()
            ->from(
                $this->resource->getTableName('sales_order', 'sales'),
                ['lifetime' => 'SUM(grand_total)']
            )
            ->where('customer_id = ?', $customerId)
            ->where('state IN (?)', ['complete', 'closed'])
            ->where('status NOT IN (?)', ['canceled', 'fraud']);

        return (float) $connection->fetchOne($select);
    }
}
