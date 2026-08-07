<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model;

use AlpineCommerce\ProductReviews\Api\Data\ReviewInterface;
use Magento\Framework\Model\AbstractModel;

class Review extends AbstractModel implements ReviewInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductReviews\Model\ResourceModel\Review::class);
    }

    public function getId(): ?int
    {
        $value = $this->getData(self::REVIEW_ID);
        return $value === null ? null : (int) $value;
    }

    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): ReviewInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId !== null ? (int) $customerId : null;
    }

    public function setCustomerId(?int $customerId): ReviewInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    public function setTitle(string $title): ReviewInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getDetail(): ?string
    {
        return $this->getData(self::DETAIL);
    }

    public function setDetail(?string $detail): ReviewInterface
    {
        return $this->setData(self::DETAIL, $detail);
    }

    public function getRating(): int
    {
        return (int) $this->getData(self::RATING);
    }

    public function setRating(int $rating): ReviewInterface
    {
        return $this->setData(self::RATING, $rating);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): ReviewInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getIsVerified(): bool
    {
        return (bool) $this->getData(self::IS_VERIFIED);
    }

    public function setIsVerified(bool $isVerified): ReviewInterface
    {
        return $this->setData(self::IS_VERIFIED, (int) $isVerified);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
