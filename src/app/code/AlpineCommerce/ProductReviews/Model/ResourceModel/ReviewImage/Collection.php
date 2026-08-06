<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewImage;

use AlpineCommerce\ProductReviews\Model\ReviewImage as ReviewImageModel;
use AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewImage as ReviewImageResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(ReviewImageModel::class, ReviewImageResource::class);
    }
}
