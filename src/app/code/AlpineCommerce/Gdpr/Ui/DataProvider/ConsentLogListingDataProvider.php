<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Ui\DataProvider;

use AlpineCommerce\Gdpr\Model\ResourceModel\ConsentLog\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ConsentLogListingDataProvider extends AbstractDataProvider
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
