<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Api\Data;

/**
 * Store interface.
 */
interface StoreInterface
{
    const ENTITY_ID = 'entity_id';
    const NAME = 'name';
    const STREET = 'street';
    const CITY = 'city';
    const REGION = 'region';
    const POSTCODE = 'postcode';
    const COUNTRY_ID = 'country_id';
    const PHONE = 'phone';
    const LATITUDE = 'latitude';
    const LONGITUDE = 'longitude';
    const OPENING_HOURS = 'opening_hours';
    const IS_ACTIVE = 'is_active';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function getEntityId(): int;

    public function getName(): string;

    public function setName(string $name): StoreInterface;

    public function getStreet(): ?string;

    public function setStreet(?string $street): StoreInterface;

    public function getCity(): ?string;

    public function setCity(?string $city): StoreInterface;

    public function getRegion(): ?string;

    public function setRegion(?string $region): StoreInterface;

    public function getPostcode(): ?string;

    public function setPostcode(?string $postcode): StoreInterface;

    public function getCountryId(): ?string;

    public function setCountryId(?string $countryId): StoreInterface;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): StoreInterface;

    public function getLatitude(): ?float;

    public function setLatitude(?float $latitude): StoreInterface;

    public function getLongitude(): ?float;

    public function setLongitude(?float $longitude): StoreInterface;

    public function getOpeningHours(): ?string;

    public function setOpeningHours(?string $openingHours): StoreInterface;

    public function getIsActive(): int;

    public function setIsActive(int $isActive): StoreInterface;

    public function getCreatedAt(): string;

    public function getUpdatedAt(): string;
}
