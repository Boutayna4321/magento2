<?php
declare(strict_types=1);

namespace Cartware\EuVat\Model;

use Cartware\EuVat\Api\Data\VatValidationInterface;
use Magento\Framework\Model\AbstractModel;

class VatValidation extends AbstractModel implements VatValidationInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\Cartware\EuVat\Model\ResourceModel\VatValidation::class);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getData('entity_id');
    }

    /**
     * @return string
     */
    public function getCountryCode(): string
    {
        return (string) $this->getData('country_code');
    }

    /**
     * @param string $countryCode
     * @return VatValidationInterface
     */
    public function setCountryCode(string $countryCode): VatValidationInterface
    {
        return $this->setData('country_code', $countryCode);
    }

    /**
     * @return string
     */
    public function getVatNumber(): string
    {
        return (string) $this->getData('vat_number');
    }

    /**
     * @param string $vatNumber
     * @return VatValidationInterface
     */
    public function setVatNumber(string $vatNumber): VatValidationInterface
    {
        return $this->setData('vat_number', $vatNumber);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return (bool) $this->getData('valid');
    }

    /**
     * @param bool $valid
     * @return VatValidationInterface
     */
    public function setIsValid(bool $valid): VatValidationInterface
    {
        return $this->setData('valid', (int) $valid);
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getData('name');
    }

    /**
     * @param string|null $name
     * @return VatValidationInterface
     */
    public function setName(?string $name): VatValidationInterface
    {
        return $this->setData('name', $name);
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->getData('address');
    }

    /**
     * @param string|null $address
     * @return VatValidationInterface
     */
    public function setAddress(?string $address): VatValidationInterface
    {
        return $this->setData('address', $address);
    }

    /**
     * @return string|null
     */
    public function getRequestId(): ?string
    {
        return $this->getData('request_id');
    }

    /**
     * @param string|null $requestId
     * @return VatValidationInterface
     */
    public function setRequestId(?string $requestId): VatValidationInterface
    {
        return $this->setData('request_id', $requestId);
    }

    /**
     * @return string|null
     */
    public function getRequestDate(): ?string
    {
        return $this->getData('request_date');
    }

    /**
     * @param string|null $requestDate
     * @return VatValidationInterface
     */
    public function setRequestDate(?string $requestDate): VatValidationInterface
    {
        return $this->setData('request_date', $requestDate);
    }
}
