<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Model;

use AlpineCommerce\EuVat\Api\Data\VatValidationInterface;
use Magento\Framework\Model\AbstractModel;

class VatValidation extends AbstractModel implements VatValidationInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\EuVat\Model\ResourceModel\VatValidation::class);
    }

    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    public function getCountryId(): string
    {
        return (string) $this->getData(self::COUNTRY_ID);
    }

    public function setCountryId(string $countryId): self
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    public function getVatNumber(): string
    {
        return (string) $this->getData(self::VAT_NUMBER);
    }

    public function setVatNumber(string $vatNumber): self
    {
        return $this->setData(self::VAT_NUMBER, $vatNumber);
    }

    public function isValid(): bool
    {
        return (bool) $this->getData(self::IS_VALID);
    }

    public function setIsValid(bool $isValid): self
    {
        return $this->setData(self::IS_VALID, $isValid ? 1 : 0);
    }

    public function getRequestDate(): ?string
    {
        return $this->getData(self::REQUEST_DATE);
    }

    public function setRequestDate(?string $requestDate): self
    {
        return $this->setData(self::REQUEST_DATE, $requestDate);
    }

    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    public function setName(?string $name): self
    {
        return $this->setData(self::NAME, $name);
    }

    public function getAddress(): ?string
    {
        return $this->getData(self::ADDRESS);
    }

    public function setAddress(?string $address): self
    {
        return $this->setData(self::ADDRESS, $address);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
