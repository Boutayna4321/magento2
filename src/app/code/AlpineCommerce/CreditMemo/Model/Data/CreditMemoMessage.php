<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Model\Data;

use AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class CreditMemoMessage extends AbstractExtensibleObject implements CreditMemoMessageInterface
{
    public function getOrderId(): int
    {
        return (int) $this->getData(self::KEY_ORDER_ID);
    }

    public function setOrderId(int $orderId): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getStoreId(): int
    {
        return (int) $this->getData(self::KEY_STORE_ID);
    }

    public function setStoreId(int $storeId): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::KEY_CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getData(self::KEY_CORRELATION_ID);
    }

    public function setCorrelationId(?string $correlationId): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_CORRELATION_ID, $correlationId);
    }

    public function getNotifyCustomer(): bool
    {
        $value = $this->getData(self::KEY_NOTIFY_CUSTOMER);
        return $value !== null ? (bool) $value : false;
    }

    public function setNotifyCustomer(bool $notify): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_NOTIFY_CUSTOMER, $notify);
    }

    public function getRefundShipping(): bool
    {
        $value = $this->getData(self::KEY_REFUND_SHIPPING);
        return $value !== null ? (bool) $value : false;
    }

    public function setRefundShipping(bool $refund): CreditMemoMessageInterface
    {
        return $this->setData(self::KEY_REFUND_SHIPPING, $refund);
    }
}
