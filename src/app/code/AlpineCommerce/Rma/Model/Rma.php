<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use AlpineCommerce\Rma\Api\Data\RmaExtensionInterface;
use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Model\AbstractModel;

class Rma extends AbstractModel implements RmaInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Rma\Model\ResourceModel\Rma::class);
    }

    public function getRmaId(): ?int
    {
        return $this->getData(self::KEY_RMA_ID) === null ? null : (int) $this->getData(self::KEY_RMA_ID);
    }

    public function setRmaId(int $rmaId): RmaInterface
    {
        return $this->setData(self::KEY_RMA_ID, $rmaId);
    }

    public function getOrderId(): ?int
    {
        return $this->getData(self::KEY_ORDER_ID) === null ? null : (int) $this->getData(self::KEY_ORDER_ID);
    }

    public function setOrderId(int $orderId): RmaInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::KEY_CUSTOMER_ID) === null ? null : (int) $this->getData(self::KEY_CUSTOMER_ID);
    }

    public function setCustomerId(int $customerId): RmaInterface
    {
        return $this->setData(self::KEY_CUSTOMER_ID, $customerId);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::KEY_STATUS);
    }

    public function setStatus(string $status): RmaInterface
    {
        return $this->setData(self::KEY_STATUS, $status);
    }

    public function getReason(): ?string
    {
        return $this->getData(self::KEY_REASON);
    }

    public function setReason(string $reason): RmaInterface
    {
        return $this->setData(self::KEY_REASON, $reason);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): RmaInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }

    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::KEY_UPDATED_AT);
    }

    public function setUpdatedAt(string $updatedAt): RmaInterface
    {
        return $this->setData(self::KEY_UPDATED_AT, $updatedAt);
    }

    public function getExtensionAttributes(): ?RmaExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    public function setExtensionAttributes(?RmaExtensionInterface $extensionAttributes): RmaInterface
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
