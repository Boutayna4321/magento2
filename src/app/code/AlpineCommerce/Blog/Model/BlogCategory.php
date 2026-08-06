<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

use AlpineCommerce\Blog\Api\Data\BlogCategoryInterface;
use Magento\Framework\Model\AbstractModel;

class BlogCategory extends AbstractModel implements BlogCategoryInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Blog\Model\ResourceModel\BlogCategory::class);
    }

    public function getId(): int
    {
        return (int) $this->getData(self::CATEGORY_ID);
    }

    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    public function setName(string $name)
    {
        return $this->setData(self::NAME, $name);
    }

    public function getUrlKey(): string
    {
        return (string) $this->getData(self::URL_KEY);
    }

    public function setUrlKey(string $urlKey)
    {
        return $this->setData(self::URL_KEY, $urlKey);
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
