<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewHelpful;

use AlpineCommerce\ProductReviews\Model\ReviewHelpful as ReviewHelpfulModel;
use AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewHelpful as ReviewHelpfulResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(ReviewHelpfulModel::class, ReviewHelpfulResource::class);
    }
}
