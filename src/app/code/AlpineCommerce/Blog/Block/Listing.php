<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Block;

use AlpineCommerce\Blog\Api\BlogPostRepositoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogPostInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;

class Listing extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly BlogPostRepositoryInterface $postRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return BlogPostInterface[]
     */
    public function getPosts(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('is_active', 1)
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->postRepository->getList($searchCriteria)->getItems();
    }

    /**
     * @param BlogPostInterface $post
     * @return string
     */
    public function getPostUrl(BlogPostInterface $post): string
    {
        return $this->getUrl('blog/index/view', ['url_key' => $post->getUrlKey()]);
    }
}
