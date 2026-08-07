<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ProductLabelInterface extends ExtensibleDataInterface
{
    public const ENTITY_ID = 'entity_id';
    public const NAME = 'name';
    public const CODE = 'code';
    public const COLOR = 'color';
    public const TEXT_COLOR = 'text_color';
    public const PRIORITY = 'priority';
    public const POSITION = 'position';
    public const ICON = 'icon';
    public const START_DATE = 'start_date';
    public const END_DATE = 'end_date';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const PRODUCT_IDS = 'product_ids';

    /**
     * @return int|null
     */
    public function getEntityId(): ?int;

    /**
     * @param int $entityId
     * @return $this
     */
    public function setEntityId(int $entityId): self;

    /**
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * @return string|null
     */
    public function getCode(): ?string;

    /**
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): self;

    /**
     * @return string|null
     */
    public function getColor(): ?string;

    /**
     * @param string|null $color
     * @return $this
     */
    public function setColor(?string $color): self;

    /**
     * @return string|null
     */
    public function getTextColor(): ?string;

    /**
     * @param string|null $textColor
     * @return $this
     */
    public function setTextColor(?string $textColor): self;

    /**
     * @return int
     */
    public function getPriority(): int;

    /**
     * @param int $priority
     * @return $this
     */
    public function setPriority(int $priority): self;

    /**
     * @return string
     */
    public function getPosition(): string;

    /**
     * @param string $position
     * @return $this
     */
    public function setPosition(string $position): self;

    /**
     * @return string|null
     */
    public function getIcon(): ?string;

    /**
     * @param string|null $icon
     * @return $this
     */
    public function setIcon(?string $icon): self;

    /**
     * @return string|null
     */
    public function getStartDate(): ?string;

    /**
     * @param string|null $startDate
     * @return $this
     */
    public function setStartDate(?string $startDate): self;

    /**
     * @return string|null
     */
    public function getEndDate(): ?string;

    /**
     * @param string|null $endDate
     * @return $this
     */
    public function setEndDate(?string $endDate): self;

    /**
     * @return bool
     */
    public function getIsActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * @return int[]|null
     */
    public function getProductIds(): ?array;

    /**
     * @param int[]|null $productIds
     * @return $this
     */
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
