<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface RmaItemInterface extends ExtensibleDataInterface
{
    public const RMA_ITEM_TABLE_NAME = 'alpinecommerce_rma_item';

    public const KEY_ITEM_ID = 'item_id';
    public const KEY_RMA_ID = 'rma_id';
    public const KEY_ORDER_ITEM_ID = 'order_item_id';
    public const KEY_PRODUCT_NAME = 'product_name';
    public const KEY_SKU = 'sku';
    public const KEY_QTY_ORDERED = 'qty_ordered';
    public const KEY_QTY_INVOICED = 'qty_invoiced';
    public const KEY_QTY_SHIPPED = 'qty_shipped';
    public const KEY_QTY_REQUESTED = 'qty_requested';
    public const KEY_QTY_APPROVED = 'qty_approved';
    public const KEY_QTY_RECEIVED = 'qty_received';
    public const KEY_QTY_REFUNDED = 'qty_refunded';
    public const KEY_CREATED_AT = 'created_at';

    public function getItemId(): ?int;
    public function setItemId(int $itemId): self;
    public function getRmaId(): ?int;
    public function setRmaId(int $rmaId): self;
    public function getOrderItemId(): ?int;
    public function setOrderItemId(int $orderItemId): self;
    public function getProductName(): ?string;
    public function setProductName(string $productName): self;
    public function getSku(): ?string;
    public function setSku(string $sku): self;
    public function getQtyOrdered(): ?float;
    public function setQtyOrdered(float $qty): self;
    public function getQtyInvoiced(): ?float;
    public function setQtyInvoiced(float $qty): self;
    public function getQtyShipped(): ?float;
    public function setQtyShipped(float $qty): self;
    public function getQtyRequested(): ?float;
    public function setQtyRequested(float $qty): self;
    public function getQtyApproved(): ?float;
    public function setQtyApproved(float $qty): self;
    public function getQtyReceived(): ?float;
    public function setQtyReceived(float $qty): self;
    public function getQtyRefunded(): ?float;
    public function setQtyRefunded(float $qty): self;
    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;
}
