<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Model\Data;

use AlpineCommerce\CreditMemo\Api\Data\CreditMemoResultInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class CreditMemoResult extends AbstractExtensibleObject implements CreditMemoResultInterface
{
    public function getOrderId(): ?int
    {
        $value = $this->getData(self::KEY_ORDER_ID);
        return $value === null ? null : (int) $value;
    }

    public function setOrderId(int $orderId): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getCreditmemoId(): ?int
    {
        $value = $this->getData(self::KEY_CREDITMEMO_ID);
        return $value === null ? null : (int) $value;
    }

    public function setCreditmemoId(?int $creditmemoId): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_CREDITMEMO_ID, $creditmemoId);
    }

    public function getCreditmemoIncrementId(): ?string
    {
        return $this->getData(self::KEY_CREDITMEMO_INCREMENT_ID);
    }

    public function setCreditmemoIncrementId(?string $incrementId): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_CREDITMEMO_INCREMENT_ID, $incrementId);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::KEY_STATUS);
    }

    public function setStatus(string $status): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_STATUS, $status);
    }

    public function getRefundedAmount(): ?float
    {
        $value = $this->getData(self::KEY_REFUNDED_AMOUNT);
        return $value === null ? null : (float) $value;
    }

    public function setRefundedAmount(float $amount): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_REFUNDED_AMOUNT, $amount);
    }

    public function getMessage(): ?string
    {
        return $this->getData(self::KEY_MESSAGE);
    }

    public function setMessage(?string $message): CreditMemoResultInterface
    {
        return $this->setData(self::KEY_MESSAGE, $message);
    }
}
