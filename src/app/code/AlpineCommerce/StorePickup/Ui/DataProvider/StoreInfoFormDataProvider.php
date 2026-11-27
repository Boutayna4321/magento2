<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Ui\DataProvider;

use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Data provider of the pickup store form (new and edit).
 *
 * $collection must be a real collection: the form component calls addFilter() on the
 * provider, which forwards to $collection->addFieldToFilter(). Without it the
 * "Add New Store" page failed with an exception in AbstractDataProvider.
 */
class StoreInfoFormDataProvider extends AbstractDataProvider
{
    private array $loadedData = [];

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

        $entityId = (int) $request->getParam($requestFieldName);
        if ($entityId) {
            $this->collection->addFieldToFilter($primaryFieldName, $entityId);
        }
    }

    public function getData(): array
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        foreach ($this->collection->getItems() as $storeInfo) {
            $this->loadedData[(int) $storeInfo->getId()] = $storeInfo->getData();
        }

        return $this->loadedData;
    }
}
