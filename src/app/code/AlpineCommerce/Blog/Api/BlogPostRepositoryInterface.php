<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use AlpineCommerce\Blog\Api\Data\BlogPostInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogPostRepositoryInterface
{
    /**
     * @param BlogPostInterface $post
     * @return BlogPostInterface
     */
    public function save(BlogPostInterface $post): BlogPostInterface;

    /**
     * @param int $id
     * @return BlogPostInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): BlogPostInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return BlogPostSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): BlogPostSearchResultsInterface;

    /**
     * @param BlogPostInterface $post
     * @return bool
     */
    public function delete(BlogPostInterface $post): bool;
}
