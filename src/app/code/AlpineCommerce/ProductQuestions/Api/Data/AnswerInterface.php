<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api\Data;

interface AnswerInterface
{
    public const ANSWER_ID = 'answer_id';
    public const QUESTION_ID = 'question_id';
    public const ADMIN_USER_ID = 'admin_user_id';
    public const CUSTOMER_ID = 'customer_id';
    public const ANSWER = 'answer';
    public const IS_OFFICIAL = 'is_official';
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
     * @return AnswerInterface
     */
    public function setQuestionId(int $questionId): AnswerInterface;

    /**
     * @return int
     */
    public function getAdminUserId(): ?int;

    /**
     * @param int|null $adminUserId
     * @return AnswerInterface
     */
    public function setAdminUserId(?int $adminUserId): AnswerInterface;

    /**
     * @return int
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return AnswerInterface
     */
    public function setCustomerId(?int $customerId): AnswerInterface;

    /**
     * @return string
     */
    public function getAnswer(): string;

    /**
     * @param string $answer
     * @return AnswerInterface
     */
    public function setAnswer(string $answer): AnswerInterface;

    /**
     * @return bool
     */
    public function getIsOfficial(): bool;

    /**
     * @param bool $isOfficial
     * @return AnswerInterface
     */
    public function setIsOfficial(bool $isOfficial): AnswerInterface;

    /**
     * @return string
     */
    public function getCreatedAt(): ?string;
}
