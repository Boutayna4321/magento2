<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use AlpineCommerce\ProductLabels\Api\Data\ProductLabelSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ProductLabelRepositoryInterface
{
    /**
     * Save product label.
     *
     * @param ProductLabelInterface $label
     * @return ProductLabelInterface
     * @throws CouldNotSaveException
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
     * @throws CouldNotDeleteException
     */
    public function delete(ProductLabelInterface $label): bool;

    /**
     * Delete product label by ID.
     *
     * @param int $entityId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Get active labels assigned to a product.
     *
     * @param int $productId
     * @return array
     */
    public function getLabelsByProductId(int $productId): array;

    /**
     * Assign labels to a product.
     *
     * @param int $productId
     * @param int[] $labelIds
     * @return bool
     */
    public function assignLabelsToProduct(int $productId, array $labelIds): bool;

    /**
     * Get product IDs assigned to a label.
     *
     * @param int $labelId
     * @return int[]
     */
    public function getProductIdsByLabel(int $labelId): array;

    /**
     * Assign products to a label.
     *
     * @param int $labelId
     * @param int[] $productIds
     * @return bool
     */
    public function assignProductsToLabel(int $labelId, array $productIds): bool;
}
