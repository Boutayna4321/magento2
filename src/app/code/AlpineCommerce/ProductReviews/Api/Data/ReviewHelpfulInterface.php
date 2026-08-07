<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api\Data;

interface ReviewHelpfulInterface
{
    public const ENTITY_ID = 'entity_id';
    public const REVIEW_ID = 'review_id';
    public const CUSTOMER_ID = 'customer_id';
    public const HELPFUL = 'helpful';
    public const CREATED_AT = 'created_at';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return int
     */
    public function getReviewId(): int;

    /**
     * @param int $reviewId
     * @return ReviewHelpfulInterface
     */
    public function setReviewId(int $reviewId): ReviewHelpfulInterface;

    /**
     * @return int
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return ReviewHelpfulInterface
     */
    public function setCustomerId(?int $customerId): ReviewHelpfulInterface;

    /**
     * @return int
     */
    public function getHelpful(): int;

    /**
     * @param int $helpful
     * @return ReviewHelpfulInterface
     */
    public function setHelpful(int $helpful): ReviewHelpfulInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
