<?php
declare(strict_types=1);

namespace Cartware\Faq\Model;

use Cartware\Faq\Api\Data\FaqInterface;
use Cartware\Faq\Api\Data\FaqSearchResultsInterface;
use Cartware\Faq\Api\FaqRepositoryInterface;
use Cartware\Faq\Model\ResourceModel\Faq as FaqResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class FaqRepository implements FaqRepositoryInterface
{
    public function __construct(
        private readonly FaqFactory $faqFactory,
        private readonly FaqResource $faqResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \Cartware\Faq\Api\Data\FaqSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * @param FaqInterface $faq
     * @return FaqInterface
     */
    public function save(FaqInterface $faq): FaqInterface
    {
        try {
            $this->faqResource->save($faq);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the FAQ entry.'), $e);
        }

        return $faq;
    }

    /**
     * @param int $id
     * @return FaqInterface
     */
    public function getById(int $id): FaqInterface
    {
        /** @var FaqInterface $faq */
        $faq = $this->faqFactory->create();
        $this->faqResource->load($faq, $id);

        if (!$faq->getId()) {
            throw new NoSuchEntityException(__('FAQ entry with ID "%1" does not exist.', $id));
        }

        return $faq;
    }

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return FaqSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): FaqSearchResultsInterface
    {
        /** @var \Cartware\Faq\Model\ResourceModel\Faq\Collection $collection */
        $collection = $this->faqFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var FaqSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @param FaqInterface $faq
     * @return bool
     */
    public function delete(FaqInterface $faq): bool
    {
        try {
            $this->faqResource->delete($faq);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the FAQ entry.'), $e);
        }

        return true;
    }
}
