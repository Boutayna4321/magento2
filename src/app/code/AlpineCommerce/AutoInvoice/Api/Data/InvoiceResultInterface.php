<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface InvoiceResultInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys for \Magento\Framework\Api\ExtensibleDataInterface methods
     */
    public const KEY_ORDER_ID = 'order_id';
    public const KEY_INVOICE_ID = 'invoice_id';
    public const KEY_INVOICE_INCREMENT_ID = 'invoice_increment_id';
    public const KEY_STATUS = 'status';
    public const KEY_MESSAGE = 'message';

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
    public function getInvoiceId(): ?int;

    /**
     * @param int|null $invoiceId
     * @return $this
     */
    public function setInvoiceId(?int $invoiceId): self;

    /**
     * @return string|null
     */
    public function getInvoiceIncrementId(): ?string;

    /**
     * @param string|null $incrementId
     * @return $this
     */
    public function setInvoiceIncrementId(?string $incrementId): self;

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
    public function getMessage(): ?string;

    /**
     * @param string|null $message
     * @return $this
     */
    public function setMessage(?string $message): self;
}
