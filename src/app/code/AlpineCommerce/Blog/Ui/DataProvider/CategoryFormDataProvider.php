<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Ui\DataProvider;

use AlpineCommerce\Blog\Model\ResourceModel\BlogCategory\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class CategoryFormDataProvider extends ModifierPoolDataProvider
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

        $categoryId = (int) $request->getParam($requestFieldName);
        if ($categoryId) {
            $this->collection->addFieldToFilter('category_id', $categoryId);
        }
    }

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $category) {
            $this->loadedData[$category->getId()] = $category->getData();
        }

        return $this->loadedData;
    }
}
