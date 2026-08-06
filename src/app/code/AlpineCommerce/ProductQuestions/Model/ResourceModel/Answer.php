<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Answer extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_product_answer', 'answer_id');
    }
}
