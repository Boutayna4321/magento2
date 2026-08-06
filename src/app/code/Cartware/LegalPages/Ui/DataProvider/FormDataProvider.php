<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Ui\DataProvider;

use Cartware\LegalPages\Model\ResourceModel\LegalPage\CollectionFactory;
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

    public function getData()
    {
        $data = parent::getData();
        $items = $data['items'] ?? [];

        if (isset($items[0])) {
            $pageId = (int) $items[0]['page_id'];
            return [$pageId => $items[0]];
        }

        return [];
    }
}
