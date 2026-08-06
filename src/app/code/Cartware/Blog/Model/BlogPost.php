<?php
declare(strict_types=1);

namespace Cartware\Blog\Model;

use Cartware\Blog\Api\Data\BlogPostInterface;
use Magento\Framework\Model\AbstractModel;

class BlogPost extends AbstractModel implements BlogPostInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\Cartware\Blog\Model\ResourceModel\BlogPost::class);
    }

    public function getId(): int
    {
        return (int) $this->getData(self::POST_ID);
    }

    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    public function setTitle(string $title)
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    public function setContent(?string $content)
    {
        return $this->setData(self::CONTENT, $content);
    }

    public function getUrlKey(): string
    {
        return (string) $this->getData(self::URL_KEY);
    }

    public function setUrlKey(string $urlKey)
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    public function getCategoryId(): ?int
    {
        $categoryId = $this->getData(self::CATEGORY_ID);
        return $categoryId !== null ? (int) $categoryId : null;
    }

    public function setCategoryId(?int $categoryId)
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
    }

    public function getAuthor(): ?string
    {
        return $this->getData(self::AUTHOR);
    }

    public function setAuthor(?string $author)
    {
        return $this->setData(self::AUTHOR, $author);
    }

    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    public function setSortOrder(int $sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function isActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $isActive)
    {
        return $this->setData(self::IS_ACTIVE, (int) $isActive);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
