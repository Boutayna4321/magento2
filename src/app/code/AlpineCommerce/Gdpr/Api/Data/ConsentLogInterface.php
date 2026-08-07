<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api\Data;

/**
 * A single GDPR consent event.
 */
interface ConsentLogInterface
{
    /**
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): ConsentLogInterface;

    /**
     * @return string
     */
    public function getConsentType(): string;

    /**
     * @param string $consentType
     * @return $this
     */
    public function setConsentType(string $consentType): ConsentLogInterface;

    /**
     * @return bool
     */
    public function isGranted(): bool;

    /**
     * @param bool $granted
     * @return $this
     */
    public function setIsGranted(bool $granted): ConsentLogInterface;

    /**
     * @return string|null
     */
    public function getIpAddress(): ?string;

    /**
     * @param string|null $ipAddress
     * @return $this
     */
    public function setIpAddress(?string $ipAddress): ConsentLogInterface;

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string;

    /**
     * @param string|null $userAgent
     * @return $this
     */
    public function setUserAgent(?string $userAgent): ConsentLogInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;
}
