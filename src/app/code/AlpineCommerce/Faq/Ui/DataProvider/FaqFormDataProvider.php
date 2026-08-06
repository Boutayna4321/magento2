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

    public function getData()
    {
        $data = parent::getData();
        $items = $data['items'] ?? [];

        if (isset($items[0])) {
            $faqId = (int) $items[0]['faq_id'];
            return [$faqId => $items[0]];
        }

        return [];
    }
}
