<?php
declare(strict_types=1);

namespace Cartware\EuVat\Api\Data;

/**
 * Represents the result of an EU VAT number validation.
 */
interface VatValidationInterface
{
    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @return string
     */
    public function getCountryCode(): string;

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode(string $countryCode): VatValidationInterface;

    /**
     * @return string
     */
    public function getVatNumber(): string;

    /**
     * @param string $vatNumber
     * @return $this
     */
    public function setVatNumber(string $vatNumber): VatValidationInterface;

    /**
     * @return bool
     */
    public function isValid(): bool;

    /**
     * @param bool $valid
     * @return $this
     */
    public function setIsValid(bool $valid): VatValidationInterface;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): VatValidationInterface;

    /**
     * @return string|null
     */
    public function getAddress(): ?string;

    /**
     * @param string|null $address
     * @return $this
     */
    public function setAddress(?string $address): VatValidationInterface;

    /**
     * @return string|null
     */
    public function getRequestId(): ?string;

    /**
     * @param string|null $requestId
     * @return $this
     */
    public function setRequestId(?string $requestId): VatValidationInterface;

    /**
     * @return string|null
     */
    public function getRequestDate(): ?string;

    /**
     * @param string|null $requestDate
     * @return $this
     */
    public function setRequestDate(?string $requestDate): VatValidationInterface;
}
