<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

use AlpineCommerce\Blog\Api\BlogCategoryRepositoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategoryInterface;
use AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterface;
use AlpineCommerce\Blog\Model\ResourceModel\BlogCategory as BlogCategoryResource;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class BlogCategoryRepository implements BlogCategoryRepositoryInterface
{
    public function __construct(
        private readonly BlogCategoryFactory $categoryFactory,
        private readonly BlogCategoryResource $categoryResource,
        private readonly CollectionProcessorInterface $collectionProcessor,
        private readonly \AlpineCommerce\Blog\Api\Data\BlogCategorySearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    public function save(BlogCategoryInterface $category): BlogCategoryInterface
    {
        try {
            $this->categoryResource->save($category);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the blog category.'), $e);
        }

        return $category;
    }

    public function getById(int $id): BlogCategoryInterface
    {
        /** @var BlogCategoryInterface $category */
        $category = $this->categoryFactory->create();
        $this->categoryResource->load($category, $id);

        if (!$category->getId()) {
            throw new NoSuchEntityException(__('Blog category with ID "%1" does not exist.', $id));
        }

        return $category;
    }

    public function getList(SearchCriteriaInterface $searchCriteria): BlogCategorySearchResultsInterface
    {
        /** @var \AlpineCommerce\Blog\Model\ResourceModel\BlogCategory\Collection $collection */
        $collection = $this->categoryFactory->create()->getCollection();
        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var BlogCategorySearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function delete(BlogCategoryInterface $category): bool
    {
        try {
            $this->categoryResource->delete($category);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the blog category.'), $e);
        }

        return true;
    }
}
