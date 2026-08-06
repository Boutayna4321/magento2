<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\ResourceModel\Question;

use AlpineCommerce\ProductQuestions\Model\Question as QuestionModel;
use AlpineCommerce\ProductQuestions\Model\ResourceModel\Question as QuestionResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(QuestionModel::class, QuestionResource::class);
    }
}
