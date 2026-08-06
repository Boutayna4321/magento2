<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\ResourceModel\Vote;

use AlpineCommerce\ProductQuestions\Model\Vote as VoteModel;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Vote as VoteResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(VoteModel::class, VoteResource::class);
    }
}
