<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface RmaInterface extends ExtensibleDataInterface
{
    public const RMAS_TABLE_NAME = 'alpinecommerce_rma';
    public const KEY_RMA_ID = 'rma_id';
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_CUSTOMER_ID = 'customer_id';
    public const KEY_STATUS = 'status';
    public const KEY_REASON = 'reason';
    public const KEY_CREATED_AT = 'created_at';
    public const KEY_UPDATED_AT = 'updated_at';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CLOSED = 'closed';

    public function getRmaId(): ?int;
    public function setRmaId(int $rmaId): self;
    public function getOrderId(): ?int;
    public function setOrderId(int $orderId): self;
    public function getCustomerId(): ?int;
    public function setCustomerId(int $customerId): self;
    public function getStatus(): ?string;
    public function setStatus(string $status): self;
    public function getReason(): ?string;
    public function setReason(string $reason): self;
    public function getCreatedAt(): ?string;
    public function setCreatedAt(string $createdAt): self;
    public function getUpdatedAt(): ?string;
    public function setUpdatedAt(string $updatedAt): self;
    /**
     * Get extension attributes.
     *
     * @return \AlpineCommerce\Rma\Api\Data\RmaExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\AlpineCommerce\Rma\Api\Data\RmaExtensionInterface;

    /**
     * Set extension attributes.
     *
     * @param \AlpineCommerce\Rma\Api\Data\RmaExtensionInterface|null $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(?\AlpineCommerce\Rma\Api\Data\RmaExtensionInterface $extensionAttributes): self;
}
