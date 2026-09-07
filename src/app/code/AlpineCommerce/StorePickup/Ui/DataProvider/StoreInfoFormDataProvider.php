<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Ui\DataProvider;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

class StoreInfoFormDataProvider extends ModifierPoolDataProvider
{
    private array $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        StoreInfoRepositoryInterface $storeInfoRepository,
        StoreInfoInterfaceFactory $storeInfoFactory,
        CollectionFactory $collectionFactory,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->storeInfoRepository = $storeInfoRepository;
        $this->storeInfoFactory = $storeInfoFactory;
        $this->request = $request;
    }

    private StoreInfoRepositoryInterface $storeInfoRepository;
    private StoreInfoInterfaceFactory $storeInfoFactory;
    private RequestInterface $request;

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $entityId = (int) $this->request->getParam($this->primaryFieldName);

        if ($entityId) {
            $storeInfo = $this->storeInfoRepository->getById($entityId);
            $this->loadedData = [$entityId => $storeInfo->getData()];
        } else {
            $this->loadedData = [0 => []];
        }

        return $this->loadedData;
    }
}
