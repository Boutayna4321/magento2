<?php
declare(strict_types=1);

namespace Cartware\Blog\Api;

use Cartware\Blog\Api\Data\BlogCategoryInterface;
use Cartware\Blog\Api\Data\BlogCategorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogCategoryRepositoryInterface
{
    /**
     * @param BlogCategoryInterface $category
     * @return BlogCategoryInterface
     */
    public function save(BlogCategoryInterface $category): BlogCategoryInterface;

    /**
     * @param int $id
     * @return BlogCategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): BlogCategoryInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return BlogCategorySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BlogCategorySearchResultsInterface;

    /**
     * @param BlogCategoryInterface $category
     * @return bool
     */
    public function delete(BlogCategoryInterface $category): bool;
}
