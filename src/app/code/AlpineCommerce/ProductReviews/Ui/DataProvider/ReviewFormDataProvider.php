<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Ui\DataProvider;

use AlpineCommerce\ProductReviews\Model\ResourceModel\Review\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class ReviewFormDataProvider extends ModifierPoolDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();

        $reviewId = (int) $request->getParam($requestFieldName);
        if ($reviewId) {
            $this->collection->addFieldToFilter('review_id', $reviewId);
        }
    }

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $review) {
            $this->loadedData[$review->getId()] = $review->getData();
        }

        return $this->loadedData;
    }
}
