<?php
declare(strict_types=1);

namespace Cartware\Faq\Model\ResourceModel\Faq;

use Cartware\Faq\Model\Faq as FaqModel;
use Cartware\Faq\Model\ResourceModel\Faq as FaqResource;
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
