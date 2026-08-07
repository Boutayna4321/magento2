<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Model;

use AlpineCommerce\ProductReviews\Api\Data\ReviewHelpfulInterface;
use Magento\Framework\Model\AbstractModel;

class ReviewHelpful extends AbstractModel implements ReviewHelpfulInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductReviews\Model\ResourceModel\ReviewHelpful::class);
    }

    public function getId(): ?int
    {
        $value = $this->getData(self::ENTITY_ID);
        return $value === null ? null : (int) $value;
    }

    public function getReviewId(): int
    {
        return (int) $this->getData(self::REVIEW_ID);
    }

    public function setReviewId(int $reviewId): ReviewHelpfulInterface
    {
        return $this->setData(self::REVIEW_ID, $reviewId);
    }

    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId !== null ? (int) $customerId : null;
    }

    public function setCustomerId(?int $customerId): ReviewHelpfulInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getHelpful(): int
    {
        return (int) $this->getData(self::HELPFUL);
    }

    public function setHelpful(int $helpful): ReviewHelpfulInterface
    {
        return $this->setData(self::HELPFUL, $helpful);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
