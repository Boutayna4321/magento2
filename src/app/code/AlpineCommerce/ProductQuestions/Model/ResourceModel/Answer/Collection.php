<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\ResourceModel\Answer;

use AlpineCommerce\ProductQuestions\Model\Answer as AnswerModel;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Answer as AnswerResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(AnswerModel::class, AnswerResource::class);
    }
}
