<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Model\Data;

use AlpineCommerce\CreditMemo\Api\Data\CreditMemoConfigInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class CreditMemoConfig extends AbstractSimpleObject implements CreditMemoConfigInterface
{
    public function isEnabled(): bool
    {
        return (bool) $this->_get(self::KEY_ENABLED);
    }

    public function setEnabled(bool $enabled): CreditMemoConfigInterface
    {
        return $this->setData(self::KEY_ENABLED, $enabled);
    }

    public function getPaymentMethods(): array
    {
        return (array) ($this->_get(self::KEY_PAYMENT_METHODS) ?? []);
    }

    public function setPaymentMethods(array $paymentMethods): CreditMemoConfigInterface
    {
        return $this->setData(self::KEY_PAYMENT_METHODS, array_values($paymentMethods));
    }

    public function isAutoRefund(): bool
    {
        return (bool) $this->_get(self::KEY_AUTO_REFUND);
    }

    public function setAutoRefund(bool $autoRefund): CreditMemoConfigInterface
    {
        return $this->setData(self::KEY_AUTO_REFUND, $autoRefund);
    }
}
