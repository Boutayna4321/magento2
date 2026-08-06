<?php
declare(strict_types=1);

namespace Cartware\Blog\Ui\DataProvider;

use Cartware\Blog\Model\ResourceModel\BlogPost\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class PostFormDataProvider extends ModifierPoolDataProvider
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

        $postId = (int) $request->getParam($requestFieldName);
        if ($postId) {
            $this->collection->addFieldToFilter('post_id', $postId);
        }
    }

    public function getData()
    {
        $data = parent::getData();
        $items = $data['items'] ?? [];

        if (isset($items[0])) {
            $postId = (int) $items[0]['post_id'];
            return [$postId => $items[0]];
        }

        return [];
    }
}
