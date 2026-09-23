<?php
declare(strict_types=1);

namespace AlpineCommerce\CustomerCare\Model\Data;

use AlpineCommerce\CustomerCare\Api\Data\VipRecalculationMessageInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class VipRecalculationMessage extends AbstractExtensibleObject implements VipRecalculationMessageInterface
{
    public function getCustomerId(): ?int
    {
        $value = $this->getData(self::KEY_CUSTOMER_ID);
        return $value === null ? null : (int) $value;
    }

    public function setCustomerId(int $customerId): VipRecalculationMessageInterface
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customerId);
    }

    public function getScope(): string
    {
        return (string) $this->getData(self::KEY_SCOPE);
    }

    public function setScope(string $scope): VipRecalculationMessageInterface
    {
        return $this->setData(self::KEY_SCOPE, $scope);
    }

    public function getStoreId(): ?int
    {
        $value = $this->getData(self::KEY_STORE_ID);
        return $value === null ? null : (int) $value;
    }

    public function setStoreId(?int $storeId): VipRecalculationMessageInterface
    {
        return $this->setData(self::KEY_STORE_ID, $storeId);
    }

    public function getCorrelationId(): ?string
    {
        return $this->getData(self::KEY_CORRELATION_ID);
    }

    public function setCorrelationId(?string $correlationId): VipRecalculationMessageInterface
    {
        return $this->setData(self::KEY_CORRELATION_ID, $correlationId);
    }

    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::KEY_CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): VipRecalculationMessageInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }
}
