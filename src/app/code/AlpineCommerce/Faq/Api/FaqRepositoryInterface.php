<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Api;

use AlpineCommerce\Faq\Api\Data\FaqInterface;
use AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * FAQ repository.
 */
interface FaqRepositoryInterface
{
    /**
     * @param FaqInterface $faq
     * @return FaqInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(FaqInterface $faq): FaqInterface;

    /**
     * @param int $id
     * @return FaqInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): FaqInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return FaqSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): FaqSearchResultsInterface;

    /**
     * @param FaqInterface $faq
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(FaqInterface $faq): bool;
}
