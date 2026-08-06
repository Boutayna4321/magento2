<?php
declare(strict_types=1);

namespace Cartware\Gdpr\Api\Data;

/**
 * The exported personal data of a customer.
 */
interface GdprExportResultInterface
{
    /**
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @return string
     */
    public function getPayload(): string;

    /**
     * @return string
     */
    public function getExportedAt(): string;
}
