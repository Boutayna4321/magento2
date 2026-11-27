<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Model\Data;

use AlpineCommerce\PartialInvoice\Api\Data\PartialInvoiceConfigInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class PartialInvoiceConfig extends AbstractSimpleObject implements PartialInvoiceConfigInterface
{
    public function isEnabled(): bool
    {
        return (bool) $this->_get(self::KEY_ENABLED);
    }

    public function setEnabled(bool $enabled): PartialInvoiceConfigInterface
    {
        return $this->setData(self::KEY_ENABLED, $enabled);
    }

    public function getPaymentMethods(): array
    {
        return (array) ($this->_get(self::KEY_PAYMENT_METHODS) ?? []);
    }

    public function setPaymentMethods(array $paymentMethods): PartialInvoiceConfigInterface
    {
        return $this->setData(self::KEY_PAYMENT_METHODS, array_values($paymentMethods));
    }

    public function isAllowBackorders(): bool
    {
        return (bool) $this->_get(self::KEY_ALLOW_BACKORDERS);
    }

    public function setAllowBackorders(bool $allowBackorders): PartialInvoiceConfigInterface
    {
        return $this->setData(self::KEY_ALLOW_BACKORDERS, $allowBackorders);
    }

    public function getMinQtyToInvoice(): float
    {
        return (float) $this->_get(self::KEY_MIN_QTY_TO_INVOICE);
    }

    public function setMinQtyToInvoice(float $minQtyToInvoice): PartialInvoiceConfigInterface
    {
        return $this->setData(self::KEY_MIN_QTY_TO_INVOICE, $minQtyToInvoice);
    }
}
