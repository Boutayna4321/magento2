<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Api\Data;

interface StoreInfoInterface
{
    const ENTITY_ID = 'entity_id';
    const SOURCE_CODE = 'source_code';
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

    public function getSourceCode(): ?string;

    public function setSourceCode(?string $sourceCode): void;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getStreet(): ?string;

    public function setStreet(?string $street): void;

    public function getCity(): ?string;

    public function setCity(?string $city): void;

    public function getRegion(): ?string;

    public function setRegion(?string $region): void;

    public function getPostcode(): ?string;

    public function setPostcode(?string $postcode): void;

    public function getCountryId(): ?string;

    public function setCountryId(?string $countryId): void;

    public function getPhone(): ?string;

    public function setPhone(?string $phone): void;

    public function getLatitude(): ?float;

    public function setLatitude(?float $latitude): void;

    public function getLongitude(): ?float;

    public function setLongitude(?float $longitude): void;

    public function getOpeningHours(): ?string;

    public function setOpeningHours(?string $openingHours): void;

    public function getIsActive(): ?int;

    public function setIsActive(?int $isActive): void;
}
