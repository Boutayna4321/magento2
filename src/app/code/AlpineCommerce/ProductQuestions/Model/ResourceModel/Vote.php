<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Vote extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_product_question_vote', 'vote_id');
    }
}
