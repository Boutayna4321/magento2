<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Api\Data;

interface ReviewImageInterface
{
    public const IMAGE_ID = 'image_id';
    public const REVIEW_ID = 'review_id';
    public const IMAGE_PATH = 'image_path';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getReviewId(): int;

    /**
     * @param int $reviewId
     * @return ReviewImageInterface
     */
    public function setReviewId(int $reviewId): ReviewImageInterface;

    /**
     * @return string
     */
    public function getImagePath(): string;

    /**
     * @param string $imagePath
     * @return ReviewImageInterface
     */
    public function setImagePath(string $imagePath): ReviewImageInterface;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param int $status
     * @return ReviewImageInterface
     */
    public function setStatus(int $status): ReviewImageInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
