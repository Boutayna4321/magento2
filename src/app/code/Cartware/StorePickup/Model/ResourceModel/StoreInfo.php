<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StoreInfo extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('cartware_pickup_store_info', 'entity_id');
    }

    public function getIdBySourceCode(string $sourceCode): int
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['entity_id'])
            ->where('source_code = :source_code');
        return (int)$connection->fetchOne($select, ['source_code' => $sourceCode]);
    }
}
