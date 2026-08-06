<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Model;

use AlpineCommerce\ProductLabels\Api\Data\ProductLabelInterface;
use Magento\Framework\Model\AbstractExtensibleModel;

class ProductLabel extends AbstractExtensibleModel implements ProductLabelInterface
{
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\ProductLabels\Model\ResourceModel\ProductLabel::class);
    }

    public function getEntityId(): ?int { return (int) $this->getData(self::ENTITY_ID); }
    public function setEntityId($entityId): self { return $this->setData(self::ENTITY_ID, $entityId); }
    public function getName(): ?string { return (string) $this->getData(self::NAME); }
    public function setName($name): self { return $this->setData(self::NAME, $name); }
    public function getCode(): ?string { return (string) $this->getData(self::CODE); }
    public function setCode($code): self { return $this->setData(self::CODE, $code); }
    public function getColor(): ?string { return $this->getData(self::COLOR); }
    public function setColor($color): self { return $this->setData(self::COLOR, $color); }
    public function getTextColor(): ?string { return $this->getData(self::TEXT_COLOR); }
    public function setTextColor($textColor): self { return $this->setData(self::TEXT_COLOR, $textColor); }
    public function getPriority(): int { return (int) $this->getData(self::PRIORITY); }
    public function setPriority($priority): self { return $this->setData(self::PRIORITY, $priority); }
    public function getPosition(): string { return (string) $this->getData(self::POSITION); }
    public function setPosition($position): self { return $this->setData(self::POSITION, $position); }
    public function getIcon(): ?string { return $this->getData(self::ICON); }
    public function setIcon($icon): self { return $this->setData(self::ICON, $icon); }
    public function getStartDate(): ?string { return $this->getData(self::START_DATE); }
    public function setStartDate($startDate): self { return $this->setData(self::START_DATE, $startDate); }
    public function getEndDate(): ?string { return $this->getData(self::END_DATE); }
    public function setEndDate($endDate): self { return $this->setData(self::END_DATE, $endDate); }
    public function getIsActive(): bool { return (bool) $this->getData(self::IS_ACTIVE); }
    public function setIsActive($isActive): self { return $this->setData(self::IS_ACTIVE, $isActive); }
    public function getCreatedAt(): string { return (string) $this->getData(self::CREATED_AT); }
    public function getUpdatedAt(): string { return (string) $this->getData(self::UPDATED_AT); }
    public function getProductIds(): ?array { $v = $this->getData(self::PRODUCT_IDS); return is_array($v) ? $v : null; }
    public function setProductIds($productIds): self { return $this->setData(self::PRODUCT_IDS, $productIds); }
    public function getExtensionAttributes(): \AlpineCommerce\ProductLabels\Api\Data\ProductLabelExtensionInterface { return $this->_getExtensionAttributes(); }
    public function setExtensionAttributes($extensionAttributes): self { return $this->_setExtensionAttributes($extensionAttributes); }
}
