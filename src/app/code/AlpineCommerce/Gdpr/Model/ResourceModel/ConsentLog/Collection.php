<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model\ResourceModel\ConsentLog;

use AlpineCommerce\Gdpr\Model\ConsentLog as ConsentLogModel;
use AlpineCommerce\Gdpr\Model\ResourceModel\ConsentLog as ConsentLogResource;
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
