<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api;

use AlpineCommerce\Rma\Api\Data\RmaInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface RmaRepositoryInterface
{
    public function save(RmaInterface $rma): RmaInterface;
    public function getById(int $rmaId): RmaInterface;
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
    public function delete(RmaInterface $rma): bool;
    public function deleteById(int $rmaId): bool;
}
