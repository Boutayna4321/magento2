<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api\Data;

interface QuestionInterface
{
    public const QUESTION_ID = 'question_id';
    public const PRODUCT_ID = 'product_id';
    public const CUSTOMER_ID = 'customer_id';
    public const CUSTOMER_NAME = 'customer_name';
    public const CUSTOMER_EMAIL = 'customer_email';
    public const QUESTION = 'question';
    public const STATUS = 'status';
    public const IS_VERIFIED_PURCHASE = 'is_verified_purchase';
    public const HELPFUL_COUNT = 'helpful_count';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     * @return QuestionInterface
     */
    public function setProductId(int $productId): QuestionInterface;

    /**
     * @return int
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return QuestionInterface
     */
    public function setCustomerId(?int $customerId): QuestionInterface;

    /**
     * @return string
     */
    public function getCustomerName(): ?string;

    /**
     * @param string|null $customerName
     * @return QuestionInterface
     */
    public function setCustomerName(?string $customerName): QuestionInterface;

    /**
     * @return string
     */
    public function getCustomerEmail(): ?string;

    /**
     * @param string|null $customerEmail
     * @return QuestionInterface
     */
    public function setCustomerEmail(?string $customerEmail): QuestionInterface;

    /**
     * @return string
     */
    public function getQuestion(): string;

    /**
     * @param string $question
     * @return QuestionInterface
     */
    public function setQuestion(string $question): QuestionInterface;

    /**
     * @return int
     */
    public function getStatus(): int;

    /**
     * @param int $status
     * @return QuestionInterface
     */
    public function setStatus(int $status): QuestionInterface;

    /**
     * @return bool
     */
    public function getIsVerifiedPurchase(): bool;

    /**
     * @param bool $isVerifiedPurchase
     * @return QuestionInterface
     */
    public function setIsVerifiedPurchase(bool $isVerifiedPurchase): QuestionInterface;

    /**
     * @return int
     */
    public function getHelpfulCount(): int;

    /**
     * @param int $helpfulCount
     * @return QuestionInterface
     */
    public function setHelpfulCount(int $helpfulCount): QuestionInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
