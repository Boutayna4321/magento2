<?php
declare(strict_types=1);

namespace Cartware\LegalPages\Api;

use Cartware\LegalPages\Api\Data\LegalPageInterface;
use Cartware\LegalPages\Api\Data\LegalPageSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface LegalPageRepositoryInterface
{
    /**
     * @param LegalPageInterface $page
     * @return LegalPageInterface
     */
    public function save(LegalPageInterface $page): LegalPageInterface;

    /**
     * @param int $id
     * @return LegalPageInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): LegalPageInterface;

    /**
     * @param string $type
     * @return LegalPageInterface|null
     */
    public function getByType(string $type): ?LegalPageInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return LegalPageSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): LegalPageSearchResultsInterface;

    /**
     * @param LegalPageInterface $page
     * @return bool
     */
    public function delete(LegalPageInterface $page): bool;
}
