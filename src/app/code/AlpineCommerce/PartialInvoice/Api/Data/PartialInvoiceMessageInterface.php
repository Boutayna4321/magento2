<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Message interface for async partial invoice processing.
 *
 * @api
 */
interface PartialInvoiceMessageInterface extends ExtensibleDataInterface
{
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_STORE_ID = 'store_id';
    public const KEY_CREATED_AT = 'created_at';
    public const KEY_CORRELATION_ID = 'correlation_id';

    /**
     * @return int
     */
    public function getOrderId(): int;

    /**
     * @param int $orderId
     * @return \AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface
     */
    public function setOrderId(int $orderId): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return \AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @param string $createdAt
     * @return \AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface
     */
    public function setCreatedAt(string $createdAt): self;

    /**
     * @return string|null
     */
    public function getCorrelationId(): ?string;

    /**
     * @param string|null $correlationId
     * @return \AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceMessageInterface
     */
    public function setCorrelationId(?string $correlationId): self;
}
