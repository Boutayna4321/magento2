<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Review extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_product_review', 'review_id');
    }
}
