<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\AnswerSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface AnswerRepositoryInterface
{
    /**
     * @param AnswerInterface $answer
     * @return AnswerInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(AnswerInterface $answer): AnswerInterface;

    /**
     * @param int $id
     * @return AnswerInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $id): AnswerInterface;

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return AnswerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): AnswerSearchResultsInterface;

    /**
     * @param AnswerInterface $answer
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(AnswerInterface $answer): bool;

    /**
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $id): bool;
}
