<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api\Data;

interface ReviewInterface
{
    public const REVIEW_ID = 'review_id';
    public const PRODUCT_ID = 'product_id';
    public const CUSTOMER_ID = 'customer_id';
    public const TITLE = 'title';
    public const DETAIL = 'detail';
    public const RATING = 'rating';
    public const STATUS = 'status';
    public const IS_VERIFIED = 'is_verified';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     * @return ReviewInterface
     */
    public function setProductId(int $productId): ReviewInterface;

    /**
     * @return int
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return ReviewInterface
     */
    public function setCustomerId(?int $customerId): ReviewInterface;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     * @return ReviewInterface
     */
    public function setTitle(string $title): ReviewInterface;

    /**
     * @return string
     */
    public function getDetail(): ?string;

    /**
     * @param string|null $detail
     * @return ReviewInterface
     */
    public function setDetail(?string $detail): ReviewInterface;

    /**
     * @return int
     */
    public function getRating(): int;

    /**
     * @param int $rating
     * @return ReviewInterface
     */
    public function setRating(int $rating): ReviewInterface;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param int $status
     * @return ReviewInterface
     */
    public function setStatus(int $status): ReviewInterface;

    /**
     * @return bool
     */
    public function getIsVerified(): bool;

    /**
     * @param bool $isVerified
     * @return ReviewInterface
     */
    public function setIsVerified(bool $isVerified): ReviewInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
