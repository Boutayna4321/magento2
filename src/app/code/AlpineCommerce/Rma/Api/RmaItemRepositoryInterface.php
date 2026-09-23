<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Api;

use AlpineCommerce\Rma\Api\Data\RmaItemInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

/**
 * @api
 */
interface RmaItemRepositoryInterface
{
    public function save(RmaItemInterface $rmaItem): RmaItemInterface;
    public function getById(int $itemId): RmaItemInterface;
    public function getByRma(int $rmaId): array;
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
    public function delete(RmaItemInterface $rmaItem): bool;
    public function deleteById(int $itemId): bool;
}
