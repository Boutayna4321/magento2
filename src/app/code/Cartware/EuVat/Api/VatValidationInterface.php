<?php
declare(strict_types=1);

namespace Cartware\EuVat\Api;

use Cartware\EuVat\Api\Data\VatValidationInterface as VatValidationResultInterface;

/**
 * EU VAT validation service.
 */
interface VatValidationInterface
{
    /**
     * Validate a European VAT number against the VIES web service.
     *
     * @param string $countryCode
     * @param string $vatNumber
     * @return VatValidationResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate(string $countryCode, string $vatNumber): VatValidationResultInterface;
}
