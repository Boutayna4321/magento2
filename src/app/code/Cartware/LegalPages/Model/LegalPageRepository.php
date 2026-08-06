<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Model;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
use Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Cartware\LegalPages\Api\LegalPageRepositoryInterface;
use Cartware\LegalPages\Model\ResourceModel\LegalPage as LegalPageResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class LegalPageRepository implements LegalPageRepositoryInterface
{
    public function __construct(
        private readonly LegalPageFactory $pageFactory,
        private readonly LegalPageResource $pageResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly \Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(LegalPageInterface $page): LegalPageInterface
    {
        try {
            $this->pageResource->save($page);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the legal page.'), $e);
        }

        return $page;
    }

    public function getById(int $id): LegalPageInterface
    {
        /** @var LegalPageInterface $page */
        $page = $this->pageFactory->create();
        $this->pageResource->load($page, $id);

        if (!$page->getId()) {
            throw new NoSuchEntityException(__('Legal page with ID "%1" does not exist.', $id));
        }

        return $page;
    }

    public function getByType(string $type): ?LegalPageInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(LegalPageInterface::TYPE, $type)
            ->addFilter(LegalPageInterface::IS_ACTIVE, 1)
            ->create();

        $items = $this->getList($searchCriteria)->getItems();

        return $items ? reset($items) : null;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): LegalPageSearchResultsInterface
    {
        /** @var \Cartware\LegalPages\Model\ResourceModel\LegalPage\Collection $collection */
        $collection = $this->pageFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var LegalPageSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(LegalPageInterface $page): bool
    {
        try {
            $this->pageResource->delete($page);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the legal page.'), $e);
        }

        return true;
    }
}
