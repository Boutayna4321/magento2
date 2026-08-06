<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api\Data;

/**
 * Result of a GDPR data deletion request.
 */
interface GdprDeleteResultInterface
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
