<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model\Data;

use AlpineCommerce\AutoInvoice\Api\Data\AutoInvoiceConfigInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class AutoInvoiceConfig extends AbstractSimpleObject implements AutoInvoiceConfigInterface
{
    public function isEnabled(): bool
    {
        return (bool) $this->_get(self::KEY_ENABLED);
    }

    public function setEnabled(bool $enabled): AutoInvoiceConfigInterface
    {
        return $this->setData(self::KEY_ENABLED, $enabled);
    }

    public function getPaymentMethods(): array
    {
        return (array) ($this->_get(self::KEY_PAYMENT_METHODS) ?? []);
    }

    public function setPaymentMethods(array $paymentMethods): AutoInvoiceConfigInterface
    {
        return $this->setData(self::KEY_PAYMENT_METHODS, array_values($paymentMethods));
    }

    public function getCaptureMode(): string
    {
        return (string) $this->_get(self::KEY_CAPTURE_MODE);
    }

    public function setCaptureMode(string $captureMode): AutoInvoiceConfigInterface
    {
        return $this->setData(self::KEY_CAPTURE_MODE, $captureMode);
    }
}
