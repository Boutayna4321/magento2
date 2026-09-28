<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Api\Data;

/**
 * Pickup store available for a cart, as returned by the web API (GET /V1/carts/mine/store-pickup).
 *
 * @api
 */
interface PickupStoreInterface
{
    public const KEY_SOURCE_CODE = 'source_code';
    public const KEY_NAME = 'name';
    public const KEY_STREET = 'street';
    public const KEY_CITY = 'city';
    public const KEY_REGION = 'region';
    public const KEY_POSTCODE = 'postcode';
    public const KEY_COUNTRY_ID = 'country_id';
    public const KEY_PHONE = 'phone';
    public const KEY_LATITUDE = 'latitude';
    public const KEY_LONGITUDE = 'longitude';
    public const KEY_OPENING_HOURS = 'opening_hours';

    /**
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * @param string|null $sourceCode
     * @return $this
     */
    public function setSourceCode(?string $sourceCode): self;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): self;

    /**
     * @return string|null
     */
    public function getStreet(): ?string;

    /**
     * @param string|null $street
     * @return $this
     */
    public function setStreet(?string $street): self;

    /**
     * @return string|null
     */
    public function getCity(): ?string;

    /**
     * @param string|null $city
     * @return $this
     */
    public function setCity(?string $city): self;

    /**
     * @return string|null
     */
    public function getRegion(): ?string;

    /**
     * @param string|null $region
     * @return $this
     */
    public function setRegion(?string $region): self;

    /**
     * @return string|null
     */
    public function getPostcode(): ?string;

    /**
     * @param string|null $postcode
     * @return $this
     */
    public function setPostcode(?string $postcode): self;

    /**
     * @return string|null
     */
    public function getCountryId(): ?string;

    /**
     * @param string|null $countryId
     * @return $this
     */
    public function setCountryId(?string $countryId): self;

    /**
     * @return string|null
     */
    public function getPhone(): ?string;

    /**
     * @param string|null $phone
     * @return $this
     */
    public function setPhone(?string $phone): self;

    /**
     * @return float|null
     */
    public function getLatitude(): ?float;

    /**
     * @param float|null $latitude
     * @return $this
     */
    public function setLatitude(?float $latitude): self;

    /**
     * @return float|null
     */
    public function getLongitude(): ?float;

    /**
     * @param float|null $longitude
     * @return $this
     */
    public function setLongitude(?float $longitude): self;

    /**
     * @return string|null
     */
    public function getOpeningHours(): ?string;

    /**
     * @param string|null $openingHours
     * @return $this
     */
    public function setOpeningHours(?string $openingHours): self;
}
