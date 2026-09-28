<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Model\Data;

use AlpineCommerce\StorePickup\Api\Data\PickupStoreInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class PickupStore extends AbstractSimpleObject implements PickupStoreInterface
{
    public function getSourceCode(): ?string
    {
        $value = $this->_get(self::KEY_SOURCE_CODE);

        return $value === null ? null : (string) $value;
    }

    public function setSourceCode(?string $sourceCode): PickupStoreInterface
    {
        return $this->setData(self::KEY_SOURCE_CODE, $sourceCode);
    }

    public function getName(): ?string
    {
        $value = $this->_get(self::KEY_NAME);

        return $value === null ? null : (string) $value;
    }

    public function setName(?string $name): PickupStoreInterface
    {
        return $this->setData(self::KEY_NAME, $name);
    }

    public function getStreet(): ?string
    {
        $value = $this->_get(self::KEY_STREET);

        return $value === null ? null : (string) $value;
    }

    public function setStreet(?string $street): PickupStoreInterface
    {
        return $this->setData(self::KEY_STREET, $street);
    }

    public function getCity(): ?string
    {
        $value = $this->_get(self::KEY_CITY);

        return $value === null ? null : (string) $value;
    }

    public function setCity(?string $city): PickupStoreInterface
    {
        return $this->setData(self::KEY_CITY, $city);
    }

    public function getRegion(): ?string
    {
        $value = $this->_get(self::KEY_REGION);

        return $value === null ? null : (string) $value;
    }

    public function setRegion(?string $region): PickupStoreInterface
    {
        return $this->setData(self::KEY_REGION, $region);
    }

    public function getPostcode(): ?string
    {
        $value = $this->_get(self::KEY_POSTCODE);

        return $value === null ? null : (string) $value;
    }

    public function setPostcode(?string $postcode): PickupStoreInterface
    {
        return $this->setData(self::KEY_POSTCODE, $postcode);
    }

    public function getCountryId(): ?string
    {
        $value = $this->_get(self::KEY_COUNTRY_ID);

        return $value === null ? null : (string) $value;
    }

    public function setCountryId(?string $countryId): PickupStoreInterface
    {
        return $this->setData(self::KEY_COUNTRY_ID, $countryId);
    }

    public function getPhone(): ?string
    {
        $value = $this->_get(self::KEY_PHONE);

        return $value === null ? null : (string) $value;
    }

    public function setPhone(?string $phone): PickupStoreInterface
    {
        return $this->setData(self::KEY_PHONE, $phone);
    }

    public function getLatitude(): ?float
    {
        $value = $this->_get(self::KEY_LATITUDE);

        return $value === null ? null : (float) $value;
    }

    public function setLatitude(?float $latitude): PickupStoreInterface
    {
        return $this->setData(self::KEY_LATITUDE, $latitude);
    }

    public function getLongitude(): ?float
    {
        $value = $this->_get(self::KEY_LONGITUDE);

        return $value === null ? null : (float) $value;
    }

    public function setLongitude(?float $longitude): PickupStoreInterface
    {
        return $this->setData(self::KEY_LONGITUDE, $longitude);
    }

    public function getOpeningHours(): ?string
    {
        $value = $this->_get(self::KEY_OPENING_HOURS);

        return $value === null ? null : (string) $value;
    }

    public function setOpeningHours(?string $openingHours): PickupStoreInterface
    {
        return $this->setData(self::KEY_OPENING_HOURS, $openingHours);
    }
}
