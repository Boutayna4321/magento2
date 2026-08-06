<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Api\Data;

interface LegalPageInterface
{
    public const PAGE_ID = 'page_id';
    public const TITLE = 'title';
    public const CONTENT = 'content';
    public const URL_KEY = 'url_key';
    public const TYPE = 'type';
    public const SORT_ORDER = 'sort_order';
    public const IS_ACTIVE = 'is_active';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const TYPE_CGV = 'cgv';
    public const TYPE_MENTIONS = 'mentions';
    public const TYPE_RETURNS = 'returns';
    public const TYPE_PRIVACY = 'privacy';
    public const TYPE_SHIPPING = 'shipping';

    /**
     * @return int
     */
    public function getId(): int;

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
     * @return string
     */
    public function getType(): string;

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type);

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
