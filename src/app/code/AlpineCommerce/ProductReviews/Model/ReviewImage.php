<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model;

use AlpineCommerce\ProductReviews\Api\Data\ReviewImageInterface;
use Magento\Framework\Model\AbstractModel;

class ReviewImage extends AbstractModel implements ReviewImageInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewImage::class);
    }

    public function getId(): ?int
    {
        $value = $this->getData(self::IMAGE_ID);
        return $value === null ? null : (int) $value;
    }

    public function getReviewId(): int
    {
        return (int) $this->getData(self::REVIEW_ID);
    }

    public function setReviewId(int $reviewId): ReviewImageInterface
    {
        return $this->setData(self::REVIEW_ID, $reviewId);
    }

    public function getImagePath(): string
    {
        return (string) $this->getData(self::IMAGE_PATH);
    }

    public function setImagePath(string $imagePath): ReviewImageInterface
    {
        return $this->setData(self::IMAGE_PATH, $imagePath);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): ReviewImageInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
