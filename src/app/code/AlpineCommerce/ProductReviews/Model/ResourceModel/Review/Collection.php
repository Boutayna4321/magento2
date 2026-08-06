<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\ResourceModel\Review;

use AlpineCommerce\ProductReviews\Model\Review as ReviewModel;
use AlpineCommerce\ProductReviews\Model\ResourceModel\Review as ReviewResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(ReviewModel::class, ReviewResource::class);
    }
}
