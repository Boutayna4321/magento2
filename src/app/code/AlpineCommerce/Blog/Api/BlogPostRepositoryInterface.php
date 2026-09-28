<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use AlpineCommerce\Blog\Api\Data\BlogPostInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogPostRepositoryInterface
{
    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogPostInterface $post
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostInterface
     */
    public function save(BlogPostInterface $post): BlogPostInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): BlogPostInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BlogPostSearchResultsInterface;

    /**
     * @param \AlpineCommerce\Blog\Api\Data\BlogPostInterface $post
     * @return bool
     */
    public function delete(BlogPostInterface $post): bool;
}
