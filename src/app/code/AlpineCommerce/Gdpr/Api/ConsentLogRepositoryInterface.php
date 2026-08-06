<?php
declare(strict_types=1);

namespace AlpineCommerce\Gdpr\Api;

use AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Persists and retrieves GDPR consent events.
 */
interface ConsentLogRepositoryInterface
{
    /**
     * @param ConsentLogInterface $consentLog
     * @return ConsentLogInterface
     */
    public function save(ConsentLogInterface $consentLog): ConsentLogInterface;

    /**
     * @param int $id
     * @return ConsentLogInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): ConsentLogInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
