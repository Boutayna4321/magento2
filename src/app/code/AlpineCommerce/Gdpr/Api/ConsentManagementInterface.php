<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api;

/**
 * Records and retrieves GDPR consent events.
 */
interface ConsentManagementInterface
{
    public const TYPE_COOKIES = 'cookies';
    public const TYPE_NEWSLETTER = 'newsletter';
    public const TYPE_TERMS = 'terms';
    public const TYPE_PRIVACY = 'privacy';

    /**
     * Log a consent event.
     *
     * @param int|null $customerId
     * @param string $consentType
     * @param bool $granted
     * @return bool
     */
    public function log(?int $customerId, string $consentType, bool $granted): bool;

    /**
     * Return the consent history of a customer.
     *
     * @param int $customerId
     * @return array<string, mixed>
     */
    public function getHistory(int $customerId): array;
}
