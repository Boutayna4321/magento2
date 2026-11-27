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
     * @param \AlpineCommerce\Faq\Api\Data\FaqInterface $faq
     * @return \AlpineCommerce\Faq\Api\Data\FaqInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(FaqInterface $faq): FaqInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\Faq\Api\Data\FaqInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): FaqInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\Faq\Api\Data\FaqSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): FaqSearchResultsInterface;

    /**
     * @param \AlpineCommerce\Faq\Api\Data\FaqInterface $faq
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(FaqInterface $faq): bool;
}
