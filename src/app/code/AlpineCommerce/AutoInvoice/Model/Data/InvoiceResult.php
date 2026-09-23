<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Model\Data;

use AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class InvoiceResult extends AbstractExtensibleObject implements InvoiceResultInterface
{
    public function getOrderId(): ?int
    {
        $value = $this->getData(self::KEY_ORDER_ID);
        return $value === null ? null : (int) $value;
    }

    public function setOrderId(int $orderId): InvoiceResultInterface
    {
        return $this->setData(self::KEY_ORDER_ID, $orderId);
    }

    public function getInvoiceId(): ?int
    {
        $value = $this->getData(self::KEY_INVOICE_ID);
        return $value === null ? null : (int) $value;
    }

    public function setInvoiceId(?int $invoiceId): InvoiceResultInterface
    {
        return $this->setData(self::KEY_INVOICE_ID, $invoiceId);
    }

    public function getInvoiceIncrementId(): ?string
    {
        return $this->getData(self::KEY_INVOICE_INCREMENT_ID);
    }

    public function setInvoiceIncrementId(?string $incrementId): InvoiceResultInterface
    {
        return $this->setData(self::KEY_INVOICE_INCREMENT_ID, $incrementId);
    }

    public function getStatus(): ?string
    {
        return $this->getData(self::KEY_STATUS);
    }

    public function setStatus(string $status): InvoiceResultInterface
    {
        return $this->setData(self::KEY_STATUS, $status);
    }

    public function getMessage(): ?string
    {
        return $this->getData(self::KEY_MESSAGE);
    }

    public function setMessage(?string $message): InvoiceResultInterface
    {
        return $this->setData(self::KEY_MESSAGE, $message);
    }
}
