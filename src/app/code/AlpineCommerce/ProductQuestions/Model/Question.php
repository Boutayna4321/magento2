<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use Magento\Framework\Model\AbstractModel;

class Question extends AbstractModel implements QuestionInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductQuestions\Model\ResourceModel\Question::class);
    }

    public function getId(): ?int
    {
        $value = $this->getData(self::QUESTION_ID);
        return $value === null ? null : (int) $value;
    }

    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): QuestionInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId !== null ? (int) $customerId : null;
    }

    public function setCustomerId(?int $customerId): QuestionInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getCustomerName(): ?string
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    public function setCustomerName(?string $customerName): QuestionInterface
    {
        return $this->setData(self::CUSTOMER_NAME, $customerName);
    }

    public function getCustomerEmail(): ?string
    {
        return $this->getData(self::CUSTOMER_EMAIL);
    }

    public function setCustomerEmail(?string $customerEmail): QuestionInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $customerEmail);
    }

    public function getQuestion(): string
    {
        return (string) $this->getData(self::QUESTION);
    }

    public function setQuestion(string $question): QuestionInterface
    {
        return $this->setData(self::QUESTION, $question);
    }

    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    public function setStatus(int $status): QuestionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getIsVerifiedPurchase(): bool
    {
        return (bool) $this->getData(self::IS_VERIFIED_PURCHASE);
    }

    public function setIsVerifiedPurchase(bool $isVerifiedPurchase): QuestionInterface
    {
        return $this->setData(self::IS_VERIFIED_PURCHASE, (int) $isVerifiedPurchase);
    }

    public function getHelpfulCount(): int
    {
        return (int) $this->getData(self::HELPFUL_COUNT);
    }

    public function setHelpfulCount(int $helpfulCount): QuestionInterface
    {
        return $this->setData(self::HELPFUL_COUNT, $helpfulCount);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
