<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Model;

use AlpineCommerce\ProductQuestions\Api\Data\VoteInterface;
use Magento\Framework\Model\AbstractModel;

class Vote extends AbstractModel implements VoteInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductQuestions\Model\ResourceModel\Vote::class);
    }

    public function getId(): int
    {
        return (int) $this->getData(self::VOTE_ID);
    }

    public function getQuestionId(): int
    {
        return (int) $this->getData(self::QUESTION_ID);
    }

    public function setQuestionId(int $questionId): VoteInterface
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    public function getCustomerId(): ?int
    {
        $customerId = $this->getData(self::CUSTOMER_ID);
        return $customerId !== null ? (int) $customerId : null;
    }

    public function setCustomerId(?int $customerId): VoteInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getIp(): ?string
    {
        return $this->getData(self::IP);
    }

    public function setIp(?string $ip): VoteInterface
    {
        return $this->setData(self::IP, $ip);
    }

    public function getValue(): int
    {
        return (int) $this->getData(self::VALUE);
    }

    public function setValue(int $value): VoteInterface
    {
        return $this->setData(self::VALUE, $value);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
