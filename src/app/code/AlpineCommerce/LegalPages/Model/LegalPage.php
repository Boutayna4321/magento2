<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Model;

use AlpineCommerce\LegalPages\Api\Data\LegalPageInterface;
use Magento\Framework\Model\AbstractModel;

class LegalPage extends AbstractModel implements LegalPageInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\LegalPages\Model\ResourceModel\LegalPage::class);
    }

    public function getId(): int
    {
        return (int) $this->getData(self::PAGE_ID);
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

    public function getType(): string
    {
        return (string) $this->getData(self::TYPE);
    }

    public function setType(string $type)
    {
        return $this->setData(self::TYPE, $type);
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
