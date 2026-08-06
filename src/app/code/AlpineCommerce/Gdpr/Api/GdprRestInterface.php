<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api;

use AlpineCommerce\Gdpr\Api\Data\GdprConsentResultInterface;
use AlpineCommerce\Gdpr\Api\Data\GdprDeleteResultInterface;
use AlpineCommerce\Gdpr\Api\Data\GdprExportResultInterface;

/**
 * GDPR REST API.
 */
interface GdprRestInterface
{
    /**
     * Record a GDPR consent event for the current customer (or anonymously).
     *
     * @param string $consentType
     * @param bool $granted
     * @return GdprConsentResultInterface
     */
    public function logConsent(string $consentType, bool $granted): GdprConsentResultInterface;

    /**
     * Export all personal data of the authenticated customer (GDPR Art. 15).
     *
     * @return GdprExportResultInterface
     */
    public function exportData(): GdprExportResultInterface;

    /**
     * Anonymize the personal data of the authenticated customer (GDPR Art. 17).
     *
     * @return GdprDeleteResultInterface
     */
    public function deleteData(): GdprDeleteResultInterface;
}
