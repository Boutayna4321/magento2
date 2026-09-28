<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Api\Data;

/**
 * Automatic credit memo configuration of a store, as returned by the web API.
 *
 * @api
 */
interface CreditMemoConfigInterface
{
    public const KEY_ENABLED = 'enabled';
    public const KEY_PAYMENT_METHODS = 'payment_methods';
    public const KEY_AUTO_REFUND = 'auto_refund';

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
    public function isAutoRefund(): bool;

    /**
     * @param bool $autoRefund
     * @return $this
     */
    public function setAutoRefund(bool $autoRefund): self;
}
