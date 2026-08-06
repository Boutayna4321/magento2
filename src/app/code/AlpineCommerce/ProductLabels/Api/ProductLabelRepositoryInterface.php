<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ProductLabelRepositoryInterface
{
    /**
     * Save product label.
     *
     * @param ProductLabelInterface $label
     * @return ProductLabelInterface
     * @throws LocalizedException
     */
    public function save(ProductLabelInterface $label): ProductLabelInterface;

    /**
     * Retrieve product label by ID.
     *
     * @param int $entityId
     * @return ProductLabelInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): ProductLabelInterface;

    /**
     * Retrieve product labels matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return ProductLabelSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): ProductLabelSearchResultsInterface;

    /**
     * Delete product label.
     *
     * @param ProductLabelInterface $label
     * @return bool
     * @throws LocalizedException
     */
    public function delete(ProductLabelInterface $label): bool;

    /**
     * Delete product label by ID.
     *
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Get labels by product ID.
     *
     * @param int $productId
     * @return array
     */
    public function getLabelsByProductId(int $productId): array;

    /**
     * Assign labels to product.
     *
     * @param int $productId
     * @param int[] $labelIds
     * @return bool
     */
    public function assignLabelsToProduct(int $productId, array $labelIds): bool;
}
