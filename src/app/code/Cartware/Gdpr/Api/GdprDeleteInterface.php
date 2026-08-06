<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Api;

/**
 * Anonymizes or removes a customer's personal data (GDPR Art. 17).
 */
interface GdprDeleteInterface
{
    /**
     * Anonymize the personal data of a customer.
     *
     * @param int $customerId
     * @return bool
     */
    public function delete(int $customerId): bool;
}
