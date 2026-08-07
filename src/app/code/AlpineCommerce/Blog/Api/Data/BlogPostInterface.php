<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api\Data;

interface BlogPostInterface
{
    public const POST_ID = 'post_id';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const URL_KEY = 'url_key';
    public const CATEGORY_ID = 'category_id';
    public const AUTHOR = 'author';
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
    public function getTitle(): string;

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title);

    /**
     * @return string|null
     */
    public function getContent(): ?string;

    /**
     * @param string|null $content
     * @return $this
     */
    public function setContent(?string $content);

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
     * @return int|null
     */
    public function getCategoryId(): ?int;

    /**
     * @param int|null $categoryId
     * @return $this
     */
    public function setCategoryId(?int $categoryId);

    /**
     * @return string|null
     */
    public function getAuthor(): ?string;

    /**
     * @param string|null $author
     * @return $this
     */
    public function setAuthor(?string $author);

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
