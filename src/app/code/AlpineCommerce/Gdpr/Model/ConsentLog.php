<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Model;

use AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface;
use Magento\Framework\Model\AbstractModel;

class ConsentLog extends AbstractModel implements ConsentLogInterface
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(\AlpineCommerce\Gdpr\Model\ResourceModel\ConsentLog::class);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int) $this->getData('entity_id');
    }

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $value = $this->getData('customer_id');
        return $value === null ? null : (int) $value;
    }

    /**
     * @param int|null $customerId
     * @return ConsentLogInterface
     */
    public function setCustomerId(?int $customerId): ConsentLogInterface
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * @return string
     */
    public function getConsentType(): string
    {
        return (string) $this->getData('consent_type');
    }

    /**
     * @param string $consentType
     * @return ConsentLogInterface
     */
    public function setConsentType(string $consentType): ConsentLogInterface
    {
        return $this->setData('consent_type', $consentType);
    }

    /**
     * @return bool
     */
    public function isGranted(): bool
    {
        return (bool) $this->getData('status');
    }

    /**
     * @param bool $granted
     * @return ConsentLogInterface
     */
    public function setIsGranted(bool $granted): ConsentLogInterface
    {
        return $this->setData('status', (int) $granted);
    }

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string
    {
        return $this->getData('ip_address');
    }

    /**
     * @param string|null $ipAddress
     * @return ConsentLogInterface
     */
    public function setIpAddress(?string $ipAddress): ConsentLogInterface
    {
        return $this->setData('ip_address', $ipAddress);
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->getData('user_agent');
    }

    /**
     * @param string|null $userAgent
     * @return ConsentLogInterface
     */
    public function setUserAgent(?string $userAgent): ConsentLogInterface
    {
        return $this->setData('user_agent', $userAgent);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }
}
