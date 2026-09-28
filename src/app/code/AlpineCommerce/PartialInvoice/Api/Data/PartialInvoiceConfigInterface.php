<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Api\Data;

/**
 * Partial invoice configuration of a store, as returned by the web API.
 *
 * @api
 */
interface PartialInvoiceConfigInterface
{
    public const KEY_ENABLED = 'enabled';
    public const KEY_PAYMENT_METHODS = 'payment_methods';
    public const KEY_ALLOW_BACKORDERS = 'allow_backorders';
    public const KEY_MIN_QTY_TO_INVOICE = 'min_qty_to_invoice';

    /**
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled(bool $enabled): self;

    /**
     * @return string[]
     */
    public function getPaymentMethods(): array;

    /**
     * @param string[] $paymentMethods
     * @return $this
     */
    public function setPaymentMethods(array $paymentMethods): self;

    /**
     * @return bool
     */
    public function isAllowBackorders(): bool;

    /**
     * @param bool $allowBackorders
     * @return $this
     */
    public function setAllowBackorders(bool $allowBackorders): self;

    /**
     * @return float
     */
    public function getMinQtyToInvoice(): float;

    /**
     * @param float $minQtyToInvoice
     * @return $this
     */
    public function setMinQtyToInvoice(float $minQtyToInvoice): self;
}
