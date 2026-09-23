<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Model;

use AlpineCommerce\Rma\Api\Data\RmaItemInterface;
use Magento\Framework\Model\AbstractModel;

class RmaItem extends AbstractModel implements RmaItemInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Rma\Model\ResourceModel\RmaItem::class);
    }

    public function getItemId(): ?int
    {
        $value = $this->getData(self::KEY_ITEM_ID);
        return $value === null ? null : (int) $value;
    }

    public function setItemId(int $itemId): RmaItemInterface
    {
        return $this->setData(self::KEY_ITEM_ID, $itemId);
    }

    public function getRmaId(): ?int
    {
        $value = $this->getData(self::KEY_RMA_ID);
        return $value === null ? null : (int) $value;
    }

    public function setRmaId(int $rmaId): RmaItemInterface
    {
        return $this->setData(self::KEY_RMA_ID, $rmaId);
    }

    public function getOrderItemId(): ?int
    {
        $value = $this->getData(self::KEY_ORDER_ITEM_ID);
        return $value === null ? null : (int) $value;
    }

    public function setOrderItemId(int $orderItemId): RmaItemInterface
    {
        return $this->setData(self::KEY_ORDER_ITEM_ID, $orderItemId);
    }

    public function getProductName(): ?string
    {
        return $this->getData(self::KEY_PRODUCT_NAME);
    }

    public function setProductName(string $productName): RmaItemInterface
    {
        return $this->setData(self::KEY_PRODUCT_NAME, $productName);
    }

    public function getSku(): ?string
    {
        return $this->getData(self::KEY_SKU);
    }

    public function setSku(string $sku): RmaItemInterface
    {
        return $this->setData(self::KEY_SKU, $sku);
    }

    public function getQtyOrdered(): ?float
    {
        $value = $this->getData(self::KEY_QTY_ORDERED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyOrdered(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_ORDERED, $qty);
    }

    public function getQtyInvoiced(): ?float
    {
        $value = $this->getData(self::KEY_QTY_INVOICED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyInvoiced(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_INVOICED, $qty);
    }

    public function getQtyShipped(): ?float
    {
        $value = $this->getData(self::KEY_QTY_SHIPPED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyShipped(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_SHIPPED, $qty);
    }

    public function getQtyRequested(): ?float
    {
        $value = $this->getData(self::KEY_QTY_REQUESTED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyRequested(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_REQUESTED, $qty);
    }

    public function getQtyApproved(): ?float
    {
        $value = $this->getData(self::KEY_QTY_APPROVED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyApproved(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_APPROVED, $qty);
    }

    public function getQtyReceived(): ?float
    {
        $value = $this->getData(self::KEY_QTY_RECEIVED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyReceived(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_RECEIVED, $qty);
    }

    public function getQtyRefunded(): ?float
    {
        $value = $this->getData(self::KEY_QTY_REFUNDED);
        return $value === null ? null : (float) $value;
    }

    public function setQtyRefunded(float $qty): RmaItemInterface
    {
        return $this->setData(self::KEY_QTY_REFUNDED, $qty);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::KEY_CREATED_AT);
    }

    public function setCreatedAt(string $createdAt): RmaItemInterface
    {
        return $this->setData(self::KEY_CREATED_AT, $createdAt);
    }
}
