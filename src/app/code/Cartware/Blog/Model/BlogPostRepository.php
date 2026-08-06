<?php
declare(strict_types=1);

namespace Cartware\Blog\Model;

use Cartware\Blog\Api\BlogPostRepositoryInterface;
use Cartware\Blog\Api\Data\BlogPostInterface;
use Cartware\Blog\Api\Data\BlogPostSearchResultsInterface;
use Cartware\Blog\Model\ResourceModel\BlogPost as BlogPostResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BlogPostRepository implements BlogPostRepositoryInterface
{
    public function __construct(
        private readonly BlogPostFactory $postFactory,
        private readonly BlogPostResource $postResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \Cartware\Blog\Api\Data\BlogPostSearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(BlogPostInterface $post): BlogPostInterface
    {
        try {
            $this->postResource->save($post);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog post.'), $e);
        }

        return $post;
    }

    public function getById(int $id): BlogPostInterface
    {
        /** @var BlogPostInterface $post */
        $post = $this->postFactory->create();
        $this->postResource->load($post, $id);

        if (!$post->getId()) {
            throw new NoSuchEntityException(__('Blog post with ID "%1" does not exist.', $id));
        }

        return $post;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): BlogPostSearchResultsInterface
    {
        /** @var \Cartware\Blog\Model\ResourceModel\BlogPost\Collection $collection */
        $collection = $this->postFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var BlogPostSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(BlogPostInterface $post): bool
    {
        try {
            $this->postResource->delete($post);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog post.'), $e);
        }

        return true;
    }
}
