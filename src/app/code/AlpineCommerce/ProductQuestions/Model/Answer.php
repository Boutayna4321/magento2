<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use Magento\Framework\Model\AbstractModel;

class Answer extends AbstractModel implements AnswerInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductQuestions\Model\ResourceModel\Answer::class);
    }

    public function getId(): int
    {
        return (int) $this->getData(self::ANSWER_ID);
    }

    public function getQuestionId(): int
    {
        return (int) $this->getData(self::QUESTION_ID);
    }

    public function setQuestionId(int $questionId): AnswerInterface
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    public function getAdminUserId(): ?int
    {
        $adminUserId = $this->getData(self::ADMIN_USER_ID);
        return $adminUserId !== null ? (int) $adminUserId : null;
    }

    public function setAdminUserId(?int $adminUserId): AnswerInterface
    {
        return $this->setData(self::ADMIN_USER_ID, $adminUserId);
    }

    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId !== null ? (int) $customerId : null;
    }

    public function setCustomerId(?int $customerId): AnswerInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getAnswer(): string
    {
        return (string) $this->getData(self::ANSWER);
    }

    public function setAnswer(string $answer): AnswerInterface
    {
        return $this->setData(self::ANSWER, $answer);
    }

    public function getIsOfficial(): bool
    {
        return (bool) $this->getData(self::IS_OFFICIAL);
    }

    public function setIsOfficial(bool $isOfficial): AnswerInterface
    {
        return $this->setData(self::IS_OFFICIAL, (int) $isOfficial);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
