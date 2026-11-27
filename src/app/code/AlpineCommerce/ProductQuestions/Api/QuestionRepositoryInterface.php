<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api;

use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface QuestionRepositoryInterface
{
    /**
     * @param \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface $question
     * @return \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(QuestionInterface $question): QuestionInterface;

    /**
     * @param int $id
     * @return \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): QuestionInterface;

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface;

    /**
     * @param \AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface $question
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(QuestionInterface $question): bool;

    /**
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $id): bool;
}
