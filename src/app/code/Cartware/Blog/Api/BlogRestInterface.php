<?php
declare(strict_types=1);

namespace Cartware\Blog\Api;

use Cartware\Blog\Api\Data\BlogCategoryInterface;
use Cartware\Blog\Api\Data\BlogCategorySearchResultsInterface;
use Cartware\Blog\Api\Data\BlogPostInterface;
use Cartware\Blog\Api\Data\BlogPostSearchResultsInterface;

/**
 * Blog REST API.
 */
interface BlogRestInterface
{
    /**
     * @param int $page
     * @param int $pageSize
     * @return BlogPostSearchResultsInterface
     */
    public function getPosts(int $page = 1, int $pageSize = 20): BlogPostSearchResultsInterface;

    /**
     * @param int $postId
     * @return BlogPostInterface
     */
    public function getPost(int $postId): BlogPostInterface;

    /**
     * @param int $page
     * @param int $pageSize
     * @return BlogCategorySearchResultsInterface
     */
    public function getCategories(int $page = 1, int $pageSize = 20): BlogCategorySearchResultsInterface;

    /**
     * @param int $categoryId
     * @return BlogCategoryInterface
     */
    public function getCategory(int $categoryId): BlogCategoryInterface;
}
