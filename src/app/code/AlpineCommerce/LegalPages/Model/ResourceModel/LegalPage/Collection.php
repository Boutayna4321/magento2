<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Model\ResourceModel\LegalPage;

use AlpineCommerce\LegalPages\Model\LegalPage as LegalPageModel;
use AlpineCommerce\LegalPages\Model\ResourceModel\LegalPage as LegalPageResource;
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
