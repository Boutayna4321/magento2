<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Model\ResourceModel\LegalPage;

use Cartware\LegalPages\Model\LegalPage as LegalPageModel;
use Cartware\LegalPages\Model\ResourceModel\LegalPage as LegalPageResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(LegalPageModel::class, LegalPageResource::class);
    }
}
