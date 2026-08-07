<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Ui\DataProvider;

use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ProductLabelListingDataProvider extends AbstractDataProvider
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
