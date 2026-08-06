<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api;

use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface QuestionRepositoryInterface
{
    /**
     * @param QuestionInterface $question
     * @return QuestionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(QuestionInterface $question): QuestionInterface;

    /**
     * @param int $id
     * @return QuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): QuestionInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return QuestionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface;

    /**
     * @param QuestionInterface $question
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
