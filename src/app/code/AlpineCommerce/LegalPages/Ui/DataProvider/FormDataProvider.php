<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Ui\DataProvider;

use AlpineCommerce\LegalPages\Model\ResourceModel\LegalPage\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class FormDataProvider extends ModifierPoolDataProvider
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

        $pageId = (int) $request->getParam($requestFieldName);
        if ($pageId) {
            $this->collection->addFieldToFilter('page_id', $pageId);
        }
    }

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $page) {
            $this->loadedData[$page->getId()] = $page->getData();
        }

        return $this->loadedData;
    }
}
