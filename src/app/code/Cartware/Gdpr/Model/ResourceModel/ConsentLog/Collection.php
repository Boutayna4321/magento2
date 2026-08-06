<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Model\ResourceModel\ConsentLog;

use Cartware\Gdpr\Model\ConsentLog as ConsentLogModel;
use Cartware\Gdpr\Model\ResourceModel\ConsentLog as ConsentLogResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ConsentLogModel::class, ConsentLogResource::class);
    }
}
