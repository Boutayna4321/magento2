<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Message interface for async credit memo processing.
 *
 * @api
 */
interface CreditMemoMessageInterface extends ExtensibleDataInterface
{
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_STORE_ID = 'store_id';
    public const KEY_CREATED_AT = 'created_at';
    public const KEY_CORRELATION_ID = 'correlation_id';
    public const KEY_NOTIFY_CUSTOMER = 'notify_customer';
    public const KEY_REFUND_SHIPPING = 'refund_shipping';

    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @param int $orderId
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string|null
     */
    public function getCorrelationId(): ?string;

    /**
     * @param string|null $correlationId
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setCorrelationId(?string $correlationId): self;

    /**
     * @return bool
     */
    public function getNotifyCustomer(): bool;

    /**
     * @param bool $notify
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setNotifyCustomer(bool $notify): self;

    /**
     * @return bool
     */
    public function getRefundShipping(): bool;

    /**
     * @param bool $refund
     * @return \AlpineCommerce\CreditMemo\Api\Data\CreditMemoMessageInterface
     */
    public function setRefundShipping(bool $refund): self;
}
