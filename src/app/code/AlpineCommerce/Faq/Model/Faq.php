<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Model;

use AlpineCommerce\Faq\Api\Data\FaqInterface;
use Magento\Framework\Model\AbstractModel;

class Faq extends AbstractModel implements FaqInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Faq\Model\ResourceModel\Faq::class);
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        $value = $this->getData(self::FAQ_ID);
        return $value === null ? null : (int) $value;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    /**
     * @param string $title
     * @return FaqInterface
     */
    public function setTitle(string $title): FaqInterface
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->getData(self::CONTENT);
    }

    /**
     * @param string|null $content
     * @return FaqInterface
     */
    public function setContent(?string $content): FaqInterface
    {
        return $this->setData(self::CONTENT, $content);
    }

    /**
     * @return string
     */
    public function getUrlKey(): string
    {
        return (string) $this->getData(self::URL_KEY);
    }

    /**
     * @param string $urlKey
     * @return FaqInterface
     */
    public function setUrlKey(string $urlKey): FaqInterface
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    /**
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int) $this->getData(self::SORT_ORDER);
    }

    /**
     * @param int $sortOrder
     * @return FaqInterface
     */
    public function setSortOrder(int $sortOrder): FaqInterface
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool) $this->getData(self::IS_ACTIVE);
    }

    /**
     * @param bool $isActive
     * @return FaqInterface
     */
    public function setIsActive(bool $isActive): FaqInterface
    {
        return $this->setData(self::IS_ACTIVE, (int) $isActive);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
