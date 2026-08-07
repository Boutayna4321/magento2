<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Api\Data;

/**
 * FAQ entity.
 */
interface FaqInterface
{
    public const FAQ_ID = 'faq_id';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const URL_KEY = 'url_key';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';

    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): FaqInterface;

    /**
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content): FaqInterface;

    /**
     * @return string
     */
    public function getUrlKey(): string;

    /**
     * @param string $urlKey
     * @return $this
     */
    public function setUrlKey(string $urlKey): FaqInterface;

    /**
     * @return int
     */
    public function getSortOrder(): int;

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder(int $sortOrder): FaqInterface;

    /**
     * @return bool
     */
    public function isActive(): bool;

    /**
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): FaqInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
