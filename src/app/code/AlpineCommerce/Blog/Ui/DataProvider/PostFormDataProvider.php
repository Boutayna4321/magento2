<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Ui\DataProvider;

use AlpineCommerce\Blog\Model\ResourceModel\BlogPost\CollectionFactory;
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

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $post) {
            $this->loadedData[$post->getId()] = $post->getData();
        }

        return $this->loadedData;
    }
}
