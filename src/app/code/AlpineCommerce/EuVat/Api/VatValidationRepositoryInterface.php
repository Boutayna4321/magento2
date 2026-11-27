<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Api;

use AlpineCommerce\EuVat\Api\Data\VatValidationInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Service contract for VAT validation operations.
 */
interface VatValidationRepositoryInterface
{
    /**
     * Save a VAT validation record.
     *
     * @param \AlpineCommerce\EuVat\Api\Data\VatValidationInterface $vatValidation
     * @return \AlpineCommerce\EuVat\Api\Data\VatValidationInterface
     */
    public function save(VatValidationInterface $vatValidation): VatValidationInterface;

    /**
     * Retrieve a VAT validation by ID.
     *
     * @param int $id
     * @return \AlpineCommerce\EuVat\Api\Data\VatValidationInterface
     */
    public function getById(int $id): VatValidationInterface;

    /**
     * Retrieve a list of VAT validations matching search criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\EuVat\Api\Data\VatValidationSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \AlpineCommerce\EuVat\Api\Data\VatValidationSearchResultsInterface;

    /**
     * Delete a VAT validation record.
     *
     * @param \AlpineCommerce\EuVat\Api\Data\VatValidationInterface $vatValidation
     * @return bool
     */
    public function delete(VatValidationInterface $vatValidation): bool;
}
