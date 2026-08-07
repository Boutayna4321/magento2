<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Ui\DataProvider;

use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class StoreListingDataProvider extends AbstractDataProvider
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
