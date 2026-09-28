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
     * @param \AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface $consentLog
     * @return \AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface
     */
    public function save(ConsentLogInterface $consentLog): ConsentLogInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\Gdpr\Api\Data\ConsentLogInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): ConsentLogInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
