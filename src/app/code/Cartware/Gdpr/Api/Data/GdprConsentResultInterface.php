<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Api\Data;

/**
 * Result of a GDPR consent logging operation.
 */
interface GdprConsentResultInterface
{
    /**
     * @return bool
     */
    public function getSuccess(): bool;

    /**
     * @return string
     */
    public function getMessage(): string;
}
