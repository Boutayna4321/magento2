<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use AlpineCommerce\Rma\Api\RmaRepositoryInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class RmaRepository implements RmaRepositoryInterface
{
    public function __construct(
        private readonly RmaFactory $rmaFactory,
        private readonly ResourceModel\Rma $resource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(RmaInterface $rma): RmaInterface
    {
        try {
            $this->resource->save($rma);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__("Could not save RMA: %1", $e->getMessage()), $e);
        }

        return $rma;
    }

    public function getById(int $rmaId): RmaInterface
    {
        $rma = $this->rmaFactory->create();
        $this->resource->load($rma, $rmaId);
        if (!$rma->getId()) {
            throw new NoSuchEntityException(__("RMA with id \"%1\" does not exist.", $rmaId));
        }

        return $rma;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): \Magento\Framework\Api\SearchResultsInterface
    {
        $collection = $this->rmaFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(RmaInterface $rma): bool
    {
        try {
            $this->resource->delete($rma);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__("Could not delete RMA: %1", $e->getMessage()), $e);
        }

        return true;
    }

    public function deleteById(int $rmaId): bool
    {
        return $this->delete($this->getById($rmaId));
    }
}