<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Api;

use AlpineCommerce\LegalPages\Api\Data\LegalPageInterface;
use AlpineCommerce\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface LegalPageRepositoryInterface
{
    /**
     * @param \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface $page
     * @return \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface
     */
    public function save(LegalPageInterface $page): LegalPageInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): LegalPageInterface;

    /**
     * @param string $type
     * @return \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface|null
     */
    public function getByType(string $type): ?LegalPageInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\LegalPages\Api\Data\LegalPageSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LegalPageSearchResultsInterface;

    /**
     * @param \AlpineCommerce\LegalPages\Api\Data\LegalPageInterface $page
     * @return bool
     */
    public function delete(LegalPageInterface $page): bool;
}
