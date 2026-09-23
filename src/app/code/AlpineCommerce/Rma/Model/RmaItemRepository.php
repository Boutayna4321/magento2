<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use AlpineCommerce\Rma\Api\Data\RmaItemInterface;
use AlpineCommerce\Rma\Api\RmaItemRepositoryInterface;
use AlpineCommerce\Rma\Model\ResourceModel\RmaItem as RmaItemResource;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class RmaItemRepository implements RmaItemRepositoryInterface
{
    public function __construct(
        private readonly RmaItemFactory $rmaItemFactory,
        private readonly RmaItemResource $resource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(RmaItemInterface $rmaItem): RmaItemInterface
    {
        try {
            $this->resource->save($rmaItem);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save RMA item: %1', $e->getMessage()), $e);
        }

        return $rmaItem;
    }

    public function getById(int $itemId): RmaItemInterface
    {
        $rmaItem = $this->rmaItemFactory->create();
        $this->resource->load($rmaItem, $itemId);
        if (!$rmaItem->getId()) {
            throw new NoSuchEntityException(__('RMA item with id "%1" does not exist.', $itemId));
        }

        return $rmaItem;
    }

    public function getByRma(int $rmaId): array
    {
        $collection = $this->rmaItemFactory->create()->getCollection();
        $collection->addFieldToFilter(RmaItemInterface::KEY_RMA_ID, (int) $rmaId);

        return $collection->getItems();
    }

    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->rmaItemFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(RmaItemInterface $rmaItem): bool
    {
        try {
            $this->resource->delete($rmaItem);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete RMA item: %1', $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $itemId): bool
    {
        return $this->delete($this->getById($itemId));
    }
}
