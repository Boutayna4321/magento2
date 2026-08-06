<?php
declare(strict_types=1);

namespace Cartware\Blog\Ui\DataProvider;

use Cartware\Blog\Model\ResourceModel\BlogPost\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class PostListingDataProvider extends AbstractDataProvider
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
