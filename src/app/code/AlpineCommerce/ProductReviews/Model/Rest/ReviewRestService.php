<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model\Rest;

use AlpineCommerce\ProductReviews\Api\Data\ReviewHelpfulInterfaceFactory;
use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use AlpineCommerce\ProductReviews\Api\Data\ReviewSearchResultsInterface;
use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use AlpineCommerce\ProductReviews\Api\ReviewRestInterface;
use AlpineCommerce\ProductReviews\Model\ReviewFactory;
use AlpineCommerce\ProductReviews\Model\Status;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Serialize\SerializerInterface;

class ReviewRestService implements ReviewRestInterface
{
    public function __construct(
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly ReviewFactory $reviewFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly UserContextInterface $userContext,
        private readonly ReviewHelpfulInterfaceFactory $helpfulFactory,
        private readonly \AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewHelpful $helpfulResource
    ) {
    }

    public function getReviews(int $productId, int $page = 1, int $pageSize = 20): ReviewSearchResultsInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(ReviewInterface::PRODUCT_ID, $productId, 'eq')
            ->addFilter(ReviewInterface::STATUS, Status::STATUS_APPROVED, 'eq')
            ->setPageSize($pageSize)
            ->setCurrentPage(max(1, $page))
            ->create();

        return $this->reviewRepository->getList($searchCriteria);
    }

    public function getReview(int $reviewId): ReviewInterface
    {
        $review = $this->reviewRepository->getById($reviewId);

        if ($review->getStatus() !== Status::STATUS_APPROVED) {
            throw new NoSuchEntityException(
                __('The product review with ID "%1" does not exist.', $reviewId)
            );
        }

        return $review;
    }

    public function addReview(int $productId, string $title, string $detail, int $rating): ReviewInterface
    {
        if ($rating < 1 || $rating > 5) {
            throw new StateException(__('Rating must be between 1 and 5.'));
        }

        $customer = $this->getCustomer();
        $customerId = $customer !== null ? $customer->getId() : null;

        /** @var ReviewInterface $review */
        $review = $this->reviewFactory->create();
        $review->setProductId($productId);
        $review->setCustomerId($customerId !== null ? (int) $customerId : null);
        $review->setTitle($title);
        $review->setDetail($detail);
        $review->setRating($rating);
        $review->setStatus(Status::STATUS_PENDING);
        $review->setIsVerified(false);

        return $this->reviewRepository->save($review);
    }

    public function voteHelpful(int $reviewId, int $helpful): bool
    {
        $review = $this->reviewRepository->getById($reviewId);

        if ($review->getStatus() !== Status::STATUS_APPROVED) {
            throw new NoSuchEntityException(
                __('The product review with ID "%1" does not exist.', $reviewId)
            );
        }

        $customerId = $this->getCustomer()?->getId();

        /** @var \AlpineCommerce\ProductReviews\Api\Data\ReviewHelpfulInterface $vote */
        $vote = $this->helpfulFactory->create();
        $vote->setReviewId($reviewId);
        $vote->setCustomerId($customerId !== null ? (int) $customerId : null);
        $vote->setHelpful($helpful);

        try {
            $this->helpfulResource->save($vote);
        } catch (\Exception $e) {
            throw new StateException(__('Unable to record your vote.'), $e);
        }

        return true;
    }

    private function getCustomer(): ?\Magento\Customer\Api\Data\CustomerInterface
    {
        try {
            $customerId = $this->userContext->getUserId();
            if ($customerId === null) {
                return null;
            }
            return $this->customerRepository->getById((int) $customerId);
        } catch (\Exception $e) {
            return null;
        }
    }
}
