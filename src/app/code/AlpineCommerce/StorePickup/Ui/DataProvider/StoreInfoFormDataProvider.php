<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Ui\DataProvider;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class StoreInfoFormDataProvider extends ModifierPoolDataProvider
{
    protected $collection;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        StoreInfoRepositoryInterface $storeInfoRepository,
        StoreInfoInterfaceFactory $storeInfoFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $storeInfoFactory->create();

        $entityId = (int) $request->getParam($requestFieldName);
        if ($entityId) {
            $this->collection = $storeInfoRepository->getById($entityId);
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
