<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Ui\DataProvider;

use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class StoreInfoListingDataProvider extends AbstractDataProvider
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
