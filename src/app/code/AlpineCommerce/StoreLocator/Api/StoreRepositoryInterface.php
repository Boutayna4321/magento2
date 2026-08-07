<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Api;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface StoreRepositoryInterface
{
    /**
     * Save store.
     *
     * @param StoreInterface $store
     * @return StoreInterface
     */
    public function save(StoreInterface $store): StoreInterface;

    /**
     * Retrieve store by ID.
     *
     * @param int $entityId
     * @return StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $entityId): StoreInterface;

    /**
     * Retrieve stores matching search criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Retrieve active stores.
     *
     * @return StoreInterface[]
     */
    public function getActiveStores(): array;

    /**
     * Delete store.
     *
     * @param StoreInterface $store
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(StoreInterface $store): bool;
}
