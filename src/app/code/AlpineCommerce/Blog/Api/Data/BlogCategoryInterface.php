<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api\Data;

interface BlogCategoryInterface
{
    public const CATEGORY_ID = 'category_id';
    public const NAME = 'name';
    public const URL_KEY = 'url_key';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name);

    /**
     * @return string
     */
    public function getUrlKey(): string;

    /**
     * @param string $urlKey
     * @return $this
     */
    public function setUrlKey(string $urlKey);

    /**
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder);

    /**
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive);

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
