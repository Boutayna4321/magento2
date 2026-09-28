<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api;

use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface ReviewRepositoryInterface
{
    /**
     * @param \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface $review
     * @return \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(ReviewInterface $review): ReviewInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): ReviewInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface;

    /**
     * @param \AlpineCommerce\ProductReviews\Api\Data\ReviewInterface $review
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(ReviewInterface $review): bool;

    /**
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $id): bool;
}
