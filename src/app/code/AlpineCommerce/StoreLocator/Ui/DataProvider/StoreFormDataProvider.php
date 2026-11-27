<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Ui\DataProvider;

use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

/**
 * Data provider of the store form (new and edit).
 *
 * $collection must be a real collection: the form component calls addFilter() on the
 * provider, which forwards to $collection->addFieldToFilter().
 */
class StoreFormDataProvider extends ModifierPoolDataProvider
{
    protected $collection;

    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
        $this->collection = $collectionFactory->create();

        $entityId = (int) $request->getParam($requestFieldName);
        if ($entityId) {
            $this->collection->addFieldToFilter($primaryFieldName, $entityId);
        }
    }

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $store) {
            $this->loadedData[(int) $store->getId()] = $store->getData();
        }

        return $this->loadedData;
    }
}
