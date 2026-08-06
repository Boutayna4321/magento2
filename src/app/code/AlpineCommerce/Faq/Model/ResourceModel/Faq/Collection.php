<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Model\ResourceModel\Faq;

use AlpineCommerce\Faq\Model\Faq as FaqModel;
use AlpineCommerce\Faq\Model\ResourceModel\Faq as FaqResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(FaqModel::class, FaqResource::class);
    }
}
