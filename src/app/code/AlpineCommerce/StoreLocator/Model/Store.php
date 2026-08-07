<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Model;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterface;
use Magento\Framework\Model\AbstractModel;

class Store extends AbstractModel implements StoreInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\StoreLocator\Model\ResourceModel\Store::class);
    }

    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    public function setName(string $name): StoreInterface
    {
        return $this->setData(self::NAME, $name);
    }

    public function getStreet(): ?string
    {
        $value = $this->getData(self::STREET);
        return $value === null ? null : (string) $value;
    }

    public function setStreet(?string $street): StoreInterface
    {
        return $this->setData(self::STREET, $street);
    }

    public function getCity(): ?string
    {
        $value = $this->getData(self::CITY);
        return $value === null ? null : (string) $value;
    }

    public function setCity(?string $city): StoreInterface
    {
        return $this->setData(self::CITY, $city);
    }

    public function getRegion(): ?string
    {
        $value = $this->getData(self::REGION);
        return $value === null ? null : (string) $value;
    }

    public function setRegion(?string $region): StoreInterface
    {
        return $this->setData(self::REGION, $region);
    }

    public function getPostcode(): ?string
    {
        $value = $this->getData(self::POSTCODE);
        return $value === null ? null : (string) $value;
    }

    public function setPostcode(?string $postcode): StoreInterface
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    public function getCountryId(): ?string
    {
        $value = $this->getData(self::COUNTRY_ID);
        return $value === null ? null : (string) $value;
    }

    public function setCountryId(?string $countryId): StoreInterface
    {
        return $this->setData(self::COUNTRY_ID, $countryId);
    }

    public function getPhone(): ?string
    {
        $value = $this->getData(self::PHONE);
        return $value === null ? null : (string) $value;
    }

    public function setPhone(?string $phone): StoreInterface
    {
        return $this->setData(self::PHONE, $phone);
    }

    public function getLatitude(): ?float
    {
        $value = $this->getData(self::LATITUDE);
        return $value !== null ? (float) $value : null;
    }

    public function setLatitude(?float $latitude): StoreInterface
    {
        return $this->setData(self::LATITUDE, $latitude);
    }

    public function getLongitude(): ?float
    {
        $value = $this->getData(self::LONGITUDE);
        return $value !== null ? (float) $value : null;
    }

    public function setLongitude(?float $longitude): StoreInterface
    {
        return $this->setData(self::LONGITUDE, $longitude);
    }

    public function getOpeningHours(): ?string
    {
        $value = $this->getData(self::OPENING_HOURS);
        return $value === null ? null : (string) $value;
    }

    public function setOpeningHours(?string $openingHours): StoreInterface
    {
        return $this->setData(self::OPENING_HOURS, $openingHours);
    }

    public function getIsActive(): int
    {
        return (int) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(int $isActive): StoreInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }

    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }
}
