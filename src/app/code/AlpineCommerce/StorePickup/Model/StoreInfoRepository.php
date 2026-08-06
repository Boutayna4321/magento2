<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface;
use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo as StoreInfoResource;
use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class StoreInfoRepository implements StoreInfoRepositoryInterface
{
    public function __construct(
        private readonly StoreInfoResource $resource,
        private readonly StoreInfoInterfaceFactory $factory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function save(StoreInfoInterface $storeInfo): StoreInfoInterface
    {
        try {
            $this->resource->save($storeInfo);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save store info: %1', $e->getMessage()), $e);
        }
        return $storeInfo;
    }

    public function getById(int $entityId): StoreInfoInterface
    {
        /** @var StoreInfoInterface $storeInfo */
        $storeInfo = $this->factory->create();
        $this->resource->load($storeInfo, $entityId);
        if (!$storeInfo->getId()) {
            throw new NoSuchEntityException(__('Store info with id "%1" does not exist.', $entityId));
        }
        return $storeInfo;
    }

    public function getBySourceCode(string $sourceCode): StoreInfoInterface
    {
        $entityId = $this->resource->getIdBySourceCode($sourceCode);
        if (!$entityId) {
            throw new NoSuchEntityException(__('Store info for source "%1" does not exist.', $sourceCode));
        }
        return $this->getById($entityId);
    }

    public function getActiveStores(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(StoreInfoInterface::IS_ACTIVE, 1);
        return $collection->getItems();
    }

    public function delete(StoreInfoInterface $storeInfo): bool
    {
        try {
            $this->resource->delete($storeInfo);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete store info: %1', $e->getMessage()), $e);
        }
        return true;
    }
}
