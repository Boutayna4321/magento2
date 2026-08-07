<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Model;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterface;
use AlpineCommerce\StoreLocator\Api\Data\StoreInterfaceFactory;
use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store as StoreResource;
use AlpineCommerce\StoreLocator\Model\ResourceModel\Store\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class StoreRepository implements StoreRepositoryInterface
{
    public function __construct(
        private readonly StoreResource $resource,
        private readonly StoreInterfaceFactory $storeFactory,
        private readonly CollectionFactory $collectionFactory,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(StoreInterface $store): StoreInterface
    {
        try {
            $this->resource->save($store);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the store: %1', $e->getMessage()), $e);
        }
        return $store;
    }

    public function getById(int $entityId): StoreInterface
    {
        /** @var StoreInterface $store */
        $store = $this->storeFactory->create();
        $this->resource->load($store, $entityId);
        if (!$store->getEntityId()) {
            throw new NoSuchEntityException(__('Store with ID "%1" does not exist.', $entityId));
        }
        return $store;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function getActiveStores(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(StoreInterface::IS_ACTIVE, 1);
        return $collection->getItems();
    }

    public function delete(StoreInterface $store): bool
    {
        try {
            $this->resource->delete($store);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the store: %1', $e->getMessage()), $e);
        }
        return true;
    }
}
