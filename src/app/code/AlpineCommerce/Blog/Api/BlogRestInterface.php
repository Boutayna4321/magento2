<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use AlpineCommerce\Blog\Api\Data\BlogCategoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface;

/**
 * Blog REST API.
 */
interface BlogRestInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface
     */
    public function getPosts(int $page = 1, int $pageSize = 20): BlogPostSearchResultsInterface;

    /**
     * @param int $postId
     * @return \AlpineCommerce\Blog\Api\Data\BlogPostInterface
     */
    public function getPost(int $postId): BlogPostInterface;

    /**
     * @param int $page
     * @param int $pageSize
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface
     */
    public function getCategories(int $page = 1, int $pageSize = 20): BlogCategorySearchResultsInterface;

    /**
     * @param int $categoryId
     * @return \AlpineCommerce\Blog\Api\Data\BlogCategoryInterface
     */
    public function getCategory(int $categoryId): BlogCategoryInterface;
}
