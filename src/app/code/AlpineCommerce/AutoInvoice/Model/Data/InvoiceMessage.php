<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model\Data;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceMessageInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class InvoiceMessage extends AbstractExtensibleObject implements InvoiceMessageInterface
{
    public function getOrderId(): int
    {
        return (int) $this->getData(self::KEY_ORDER_ID);
    }

    public function setOrderId(int $orderId): InvoiceMessageInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getStoreId(): int
    {
        return (int) $this->getData(self::KEY_STORE_ID);
    }

    public function setStoreId(int $storeId): InvoiceMessageInterface
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::KEY_CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): InvoiceMessageInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getData(self::KEY_CORRELATION_ID);
    }

    public function setCorrelationId(?string $correlationId): InvoiceMessageInterface
    {
        return $this->setData(self::KEY_CORRELATION_ID, $correlationId);
    }
}
