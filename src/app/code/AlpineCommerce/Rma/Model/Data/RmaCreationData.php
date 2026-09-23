<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model\Data;

use AlpineCommerce\Rma\Api\Data\RmaCreationDataInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

/**
 * @inheritDoc
 */
class RmaCreationData extends AbstractExtensibleObject implements RmaCreationDataInterface
{
    public function getOrderId(): ?int
    {
        $value = $this->getData(self::KEY_ORDER_ID);
        return $value === null ? null : (int) $value;
    }

    public function setOrderId(int $orderId): RmaCreationDataInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getItems(): array
    {
        $items = $this->getData(self::KEY_ITEMS);
        return is_array($items) ? $items : [];
    }

    public function setItems(array $items): RmaCreationDataInterface
    {
        return $this->setData(self::KEY_ITEMS, $items);
    }

    public function getReason(): ?string
    {
        return $this->getData(self::KEY_REASON);
    }

    public function setReason(string $reason): RmaCreationDataInterface
    {
        return $this->setData(self::KEY_REASON, $reason);
    }

    public function getCustomerNotes(): ?string
    {
        return $this->getData(self::KEY_CUSTOMER_NOTES);
    }

    public function setCustomerNotes(string $notes): RmaCreationDataInterface
    {
        return $this->setData(self::KEY_CUSTOMER_NOTES, $notes);
    }
}
