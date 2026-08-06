<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model\DataProvider;

use AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel\CollectionFactory as ProductLabelCollectionFactory;
use Magento\Framework\App\Helper\ArrayManager;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\DataProvider\DataProvider as UiDataProvider;

class ProductLabelDataProvider extends UiDataProvider
{
    private readonly ProductLabelCollectionFactory $collectionFactory;
    private readonly UrlInterface $urlBuilder;
    private readonly ArrayManager $arrayManager;

    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        ProductLabelCollectionFactory $collectionFactory,
        UrlInterface $urlBuilder,
        ArrayManager $arrayManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->urlBuilder = $urlBuilder;
        $this->arrayManager = $arrayManager;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $collection = $this->collectionFactory->create();
        $items = $collection->getData();
        $this->loadedData = [
            "items" => array_values($items),
            "totalRecords" => $collection->getSize()
        ];
        return $this->loadedData;
    }
}
