<?php
declare(strict_types=1);

namespace Cartware\StorePickup\Model;

use Cartware\StorePickup\Api\Data\StoreInfoInterface;
use Cartware\StorePickup\Model\ResourceModel\StoreInfo as StoreInfoResource;
use Magento\Framework\Model\AbstractModel;

class StoreInfo extends AbstractModel implements StoreInfoInterface
{
    protected function _construct(): void
    {
        $this->_init(StoreInfoResource::class);
    }

    public function getSourceCode(): ?string
    {
        return $this->getData(self::SOURCE_CODE);
    }

    public function setSourceCode(?string $sourceCode): void
    {
        $this->setData(self::SOURCE_CODE, $sourceCode);
    }

    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    public function setName(?string $name): void
    {
        $this->setData(self::NAME, $name);
    }

    public function getStreet(): ?string
    {
        return $this->getData(self::STREET);
    }

    public function setStreet(?string $street): void
    {
        $this->setData(self::STREET, $street);
    }

    public function getCity(): ?string
    {
        return $this->getData(self::CITY);
    }

    public function setCity(?string $city): void
    {
        $this->setData(self::CITY, $city);
    }

    public function getRegion(): ?string
    {
        return $this->getData(self::REGION);
    }

    public function setRegion(?string $region): void
    {
        $this->setData(self::REGION, $region);
    }

    public function getPostcode(): ?string
    {
        return $this->getData(self::POSTCODE);
    }

    public function setPostcode(?string $postcode): void
    {
        $this->setData(self::POSTCODE, $postcode);
    }

    public function getCountryId(): ?string
    {
        return $this->getData(self::COUNTRY_ID);
    }

    public function setCountryId(?string $countryId): void
    {
        $this->setData(self::COUNTRY_ID, $countryId);
    }

    public function getPhone(): ?string
    {
        return $this->getData(self::PHONE);
    }

    public function setPhone(?string $phone): void
    {
        $this->setData(self::PHONE, $phone);
    }

    public function getLatitude(): ?float
    {
        $value = $this->getData(self::LATITUDE);
        return $value !== null ? (float)$value : null;
    }

    public function setLatitude(?float $latitude): void
    {
        $this->setData(self::LATITUDE, $latitude);
    }

    public function getLongitude(): ?float
    {
        $value = $this->getData(self::LONGITUDE);
        return $value !== null ? (float)$value : null;
    }

    public function setLongitude(?float $longitude): void
    {
        $this->setData(self::LONGITUDE, $longitude);
    }

    public function getOpeningHours(): ?string
    {
        return $this->getData(self::OPENING_HOURS);
    }

    public function setOpeningHours(?string $openingHours): void
    {
        $this->setData(self::OPENING_HOURS, $openingHours);
    }

    public function getIsActive(): ?int
    {
        $value = $this->getData(self::IS_ACTIVE);
        return $value !== null ? (int)$value : null;
    }

    public function setIsActive(?int $isActive): void
    {
        $this->setData(self::IS_ACTIVE, $isActive);
    }
}
