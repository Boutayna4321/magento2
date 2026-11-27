<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Api\Data;

/**
 * Auto-invoice configuration of a store, as returned by the web API.
 *
 * @api
 */
interface AutoInvoiceConfigInterface
{
    public const KEY_ENABLED = 'enabled';
    public const KEY_PAYMENT_METHODS = 'payment_methods';
    public const KEY_CAPTURE_MODE = 'capture_mode';

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
     * @return string
     */
    public function getCaptureMode(): string;

    /**
     * @param string $captureMode
     * @return $this
     */
    public function setCaptureMode(string $captureMode): self;
}
