<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model;

use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface;
use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use AlpineCommerce\ProductReviews\Model\ResourceModel\Review as ReviewResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ReviewRepository implements ReviewRepositoryInterface
{
    public function __construct(
        private readonly ReviewFactory $reviewFactory,
        private readonly ReviewResource $reviewResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(ReviewInterface $review): ReviewInterface
    {
        try {
            $this->reviewResource->save($review);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the product review.'), $e);
        }

        return $review;
    }

    public function getById(int $id): ReviewInterface
    {
        /** @var ReviewInterface $review */
        $review = $this->reviewFactory->create();
        $this->reviewResource->load($review, $id);

        if (!$review->getId()) {
            throw new NoSuchEntityException(__('Product review with ID "%1" does not exist.', $id));
        }

        return $review;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface
    {
        /** @var \AlpineCommerce\ProductReviews\Model\ResourceModel\Review\Collection $collection */
        $collection = $this->reviewFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var ReviewSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(ReviewInterface $review): bool
    {
        try {
            $this->reviewResource->delete($review);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the product review.'), $e);
        }

        return true;
    }

    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }
}
