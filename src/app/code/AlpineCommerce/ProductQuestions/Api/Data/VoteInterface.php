<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api\Data;

interface VoteInterface
{
    public const VOTE_ID = 'vote_id';
    public const QUESTION_ID = 'question_id';
    public const CUSTOMER_ID = 'customer_id';
    public const IP = 'ip';
    public const VALUE = 'value';
    public const CREATED_AT = 'created_at';

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return int
     */
    public function getQuestionId(): int;

    /**
     * @param int $questionId
     * @return VoteInterface
     */
    public function setQuestionId(int $questionId): VoteInterface;

    /**
     * @return int
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return VoteInterface
     */
    public function setCustomerId(?int $customerId): VoteInterface;

    /**
     * @return string
     */
    public function getIp(): ?string;

    /**
     * @param string|null $ip
     * @return VoteInterface
     */
    public function setIp(?string $ip): VoteInterface;

    /**
     * @return int
     */
    public function getValue(): int;

    /**
     * @param int $value
     * @return VoteInterface
     */
    public function setValue(int $value): VoteInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
