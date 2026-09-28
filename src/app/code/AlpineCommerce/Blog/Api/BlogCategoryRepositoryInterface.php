<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use AlpineCommerce\Blog\Api\Data\BlogCategoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogCategoryRepositoryInterface
{
    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface $category
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface
     */
    public function save(BlogCategoryInterface $category): BlogCategoryInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): BlogCategoryInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BlogCategorySearchResultsInterface;

    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface $category
     * @return bool
     */
    public function delete(BlogCategoryInterface $category): bool;
}
