<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface RmaRepositoryInterface
{
    /**
     * @param \AlpineCommerce\Rma\Api\Data\RmaInterface $rma
     * @return \AlpineCommerce\Rma\Api\Data\RmaInterface
     */
    public function save(RmaInterface $rma): RmaInterface;

    /**
     * @param int $rmaId
     * @return \AlpineCommerce\Rma\Api\Data\RmaInterface
     */
    public function getById(int $rmaId): RmaInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * @param \AlpineCommerce\Rma\Api\Data\RmaInterface $rma
     * @return bool
     */
    public function delete(RmaInterface $rma): bool;

    /**
     * @param int $rmaId
     * @return bool
     */
    public function deleteById(int $rmaId): bool;
}
