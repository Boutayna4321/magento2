<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Api;

use AlpineCommerce\EuVat\Api\Data\VatValidationInterface as VatValidationDataInterface;

interface VatValidationInterface
{
    /**
     * Validate a VAT number against the VIES web service.
     *
     * @param string $countryId Two-letter ISO country code (e.g. "FR").
     * @param string $vatNumber VAT number without the country prefix.
     * @return VatValidationDataInterface
     */
    public function validate(string $countryId, string $vatNumber): VatValidationDataInterface;

    /**
     * Retrieve a previously stored VAT validation by country and number.
     *
     * @param string $countryId Two-letter ISO country code (e.g. "FR").
     * @param string $vatNumber VAT number without the country prefix.
     * @return VatValidationDataInterface|null
     */
    public function getByCountryAndNumber(string $countryId, string $vatNumber): ?VatValidationDataInterface;
}
