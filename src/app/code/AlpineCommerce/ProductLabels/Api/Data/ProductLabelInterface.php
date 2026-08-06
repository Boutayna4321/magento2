<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ProductLabelInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = "entity_id";
    public const NAME = "name";
    public const CODE = "code";
    public const COLOR = "color";
    public const TEXT_COLOR = "text_color";
    public const PRIORITY = "priority";
    public const POSITION = "position";
    public const ICON = "icon";
    public const START_DATE = "start_date";
    public const END_DATE = "end_date";
    public const IS_ACTIVE = "is_active";
    public const CREATED_AT = "created_at";
    public const UPDATED_AT = "updated_at";
    public const PRODUCT_IDS = "product_ids";

    public function getEntityId(): ?int;
    public function setEntityId(int $entityId): self;
    public function getName(): ?string;
    public function setName(string $name): self;
    public function getCode(): ?string;
    public function setCode(string $code): self;
    public function getColor(): ?string;
    public function setColor(?string $color): self;
    public function getTextColor(): ?string;
    public function setTextColor(?string $textColor): self;
    public function getPriority(): int;
    public function setPriority(int $priority): self;
    public function getPosition(): string;
    public function setPosition(string $position): self;
    public function getIcon(): ?string;
    public function setIcon(?string $icon): self;
    public function getStartDate(): ?string;
    public function setStartDate(?string $startDate): self;
    public function getEndDate(): ?string;
    public function setEndDate(?string $endDate): self;
    public function getIsActive(): bool;
    public function setIsActive(bool $isActive): self;
    public function getCreatedAt(): string;
    public function getUpdatedAt(): string;
    public function getProductIds(): ?array;
    public function setProductIds(?array $productIds): self;

    /**
     * @return \AlpineCommerce\ProductLabels\Api\Data\ProductLabelExtensionInterface|null
     */
    public function getExtensionAttributes(): \AlpineCommerce\ProductLabels\Api\Data\ProductLabelExtensionInterface;

    /**
     * @param \AlpineCommerce\ProductLabels\Api\Data\ProductLabelExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\AlpineCommerce\ProductLabels\Api\Data\ProductLabelExtensionInterface $extensionAttributes): self;
}
