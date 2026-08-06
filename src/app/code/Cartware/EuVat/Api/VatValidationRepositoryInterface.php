<?php
declare(strict_types=1);

namespace Cartware\EuVat\Api;

use Cartware\EuVat\Api\Data\VatValidationInterface;

/**
 * Persists VAT validation results.
 */
interface VatValidationRepositoryInterface
{
    /**
     * @param VatValidationInterface $validation
     * @return VatValidationInterface
     */
    public function save(VatValidationInterface $validation): VatValidationInterface;

    /**
     * @param int $id
     * @return VatValidationInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): VatValidationInterface;
}
