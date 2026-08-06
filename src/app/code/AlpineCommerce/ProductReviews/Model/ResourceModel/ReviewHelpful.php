<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ReviewHelpful extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_product_review_helpful', 'entity_id');
    }
}
