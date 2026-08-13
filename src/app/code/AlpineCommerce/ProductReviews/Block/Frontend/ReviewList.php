<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Block\Frontend;

use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use AlpineCommerce\ProductReviews\Service\ImageProcessor;
use AlpineCommerce\ProductReviews\Model\Status;
use AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewImage\CollectionFactory as ImageCollectionFactory;
use AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewHelpful\CollectionFactory as HelpfulCollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Api\ProductRepositoryInterface;

class ReviewList extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly SortOrderBuilder $sortOrderBuilder,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ImageProcessor $imageProcessor,
        private readonly Session $customerSession,
        private readonly ImageCollectionFactory $imageCollectionFactory,
        private readonly HelpfulCollectionFactory $helpfulCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductId(): int
    {
        return (int) $this->getRequest()->getParam('id', 0);
    }

    public function getProductName(): string
    {
        try {
            $product = $this->productRepository->getById($this->getProductId());
            return $product->getName();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @return ReviewInterface[]
     */
    public function getReviews(): array
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('created_at')
            ->setDirection(SortOrder::SORT_DESC)
            ->create();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReviewInterface::PRODUCT_ID, $this->getProductId(), 'eq')
            ->addFilter(ReviewInterface::STATUS, Status::STATUS_APPROVED, 'eq')
            ->setSortOrders([$sortOrder])
            ->create();

        return $this->reviewRepository->getList($searchCriteria)->getItems();
    }

    public function getAverageRating(array $reviews): float
    {
        if (empty($reviews)) {
            return 0.0;
        }

        $total = 0;
        foreach ($reviews as $review) {
            $total += $review->getRating();
        }

        return $total / count($reviews);
    }

    public function getStatusLabel(int $status): string
    {
        return Status::getLabel($status);
    }

    public function getImages(int $reviewId): array
    {
        $collection = $this->imageCollectionFactory->create();
        $collection->addFieldToFilter('review_id', $reviewId);

        return $collection->getItems();
    }

    public function getImageUrl(string $path): string
    {
        return $this->imageProcessor->getUploadUrl() . '/' . $path;
    }

    public function getHelpfulCount(int $reviewId, int $helpful): int
    {
        $collection = $this->helpfulCollectionFactory->create();
        $collection->addFieldToFilter('review_id', $reviewId);
        $collection->addFieldToFilter('helpful', $helpful);

        return (int) $collection->getSize();
    }

    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    public function getWriteReviewUrl(): string
    {
        return $this->getUrl('productreviews/index/view', ['id' => $this->getProductId()]);
    }
}
