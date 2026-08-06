<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Ui\DataProvider;

use AlpineCommerce\ProductReviews\Model\ResourceModel\Review\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ReviewListingDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
