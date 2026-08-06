<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Api\Data;

/**
 * Data interface for a VAT validation record.
 */
interface VatValidationInterface
{
    public const ENTITY_ID = 'entity_id';
    public const COUNTRY_ID = 'country_id';
    public const VAT_NUMBER = 'vat_number';
    public const IS_VALID = 'is_valid';
    public const REQUEST_DATE = 'request_date';
    public const NAME = 'name';
    public const ADDRESS = 'address';
    public const CREATED_AT = 'created_at';

    /**
     * Get the entity ID.
     *
     * @return int
     */
    public function getEntityId(): int;

    /**
     * Get the two-letter ISO country code.
     *
     * @return string
     */
    public function getCountryId(): string;

    /**
     * Set the two-letter ISO country code.
     *
     * @param string $countryId
     * @return self
     */
    public function setCountryId(string $countryId): self;

    /**
     * Get the VAT number.
     *
     * @return string
     */
    public function getVatNumber(): string;

    /**
     * Set the VAT number.
     *
     * @param string $vatNumber
     * @return self
     */
    public function setVatNumber(string $vatNumber): self;

    /**
     * Check if the VAT number is valid.
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Set the validity flag.
     *
     * @param bool $isValid
     * @return self
     */
    public function setIsValid(bool $isValid): self;

    /**
     * Get the request date from VIES.
     *
     * @return string|null
     */
    public function getRequestDate(): ?string;

    /**
     * Set the request date from VIES.
     *
     * @param string|null $requestDate
     * @return self
     */
    public function setRequestDate(?string $requestDate): self;

    /**
     * Get the company name from VIES.
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set the company name from VIES.
     *
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self;

    /**
     * Get the company address from VIES.
     *
     * @return string|null
     */
    public function getAddress(): ?string;

    /**
     * Set the company address from VIES.
     *
     * @param string|null $address
     * @return self
     */
    public function setAddress(?string $address): self;

    /**
     * Get the creation timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
