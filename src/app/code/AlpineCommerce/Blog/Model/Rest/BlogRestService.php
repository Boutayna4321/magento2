<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model\Rest;

use AlpineCommerce\Blog\Api\BlogCategoryRepositoryInterface;
use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use AlpineCommerce\Blog\Api\BlogRestInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class BlogRestService implements BlogRestInterface
{
    public function __construct(
        private readonly BlogPostRepositoryInterface $postRepository,
        private readonly BlogCategoryRepositoryInterface $categoryRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public function getPosts(int $page = 1, int $pageSize = 20): BlogPostSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(BlogPostInterface::IS_ACTIVE, 1, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->postRepository->getList($searchCriteria);
    }

    public function getPost(int $postId): BlogPostInterface
    {
        $post = $this->postRepository->getById($postId);
        if (!$post->isActive()) {
            throw new NoSuchEntityException(
                __('The blog post with ID "%1" does not exist.', $postId)
            );
        }

        return $post;
    }

    public function getCategories(int $page = 1, int $pageSize = 20): BlogCategorySearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(BlogCategoryInterface::IS_ACTIVE, 1, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->categoryRepository->getList($searchCriteria);
    }

    public function getCategory(int $categoryId): BlogCategoryInterface
    {
        return $this->categoryRepository->getById($categoryId);
    }
}
