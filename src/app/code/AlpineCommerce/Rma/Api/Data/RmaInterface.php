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
    public const STATUS_RETURNED = 'returned';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CLOSED = 'closed';

    /**
     * @return int|null
     */
    public function getRmaId(): ?int;

    /**
     * @param int $rmaId
     * @return $this
     */
    public function setRmaId(int $rmaId): self;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string|null
     */
    public function getReason(): ?string;

    /**
     * @param string $reason
     * @return $this
     */
    public function setReason(string $reason): self;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * @param string $updatedAt
     * @return $this
     */
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
