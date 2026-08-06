<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api;

/**
 * Exports all personal data of a customer (GDPR Art. 15).
 */
interface GdprExportInterface
{
    /**
     * Collect all personal data stored for a customer.
     *
     * @param int $customerId
     * @return array<string, mixed>
     */
    public function export(int $customerId): array;
}
