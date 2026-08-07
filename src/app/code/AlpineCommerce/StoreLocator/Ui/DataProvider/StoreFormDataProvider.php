<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Ui\DataProvider;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterfaceFactory;
use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class StoreFormDataProvider extends ModifierPoolDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        StoreRepositoryInterface $storeRepository,
        StoreInterfaceFactory $storeFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $storeFactory->create();

        $entityId = (int) $request->getParam($requestFieldName);
        if ($entityId) {
            $this->collection = $storeRepository->getById($entityId);
        }
    }

    private $loadedData = [];

    public function getData()
    {
        if ($this->loadedData) {
            return $this->loadedData;
        }

        $this->loadedData[$this->collection->getEntityId()] = $this->collection->getData();

        return $this->loadedData;
    }
}
