<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Ui\DataProvider;

use AlpineCommerce\Faq\Model\ResourceModel\Faq\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class FaqFormDataProvider extends ModifierPoolDataProvider
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

        $faqId = (int) $request->getParam($requestFieldName);
        if ($faqId) {
            $this->collection->addFieldToFilter('faq_id', $faqId);
        }
    }

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $faq) {
            $this->loadedData[$faq->getId()] = $faq->getData();
        }

        return $this->loadedData;
    }
}
