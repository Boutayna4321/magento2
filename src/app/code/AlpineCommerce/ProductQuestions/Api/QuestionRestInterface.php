<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Api;

use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionSearchResultsInterface;

interface QuestionRestInterface
{
    /**
     * @param int $productId
     * @param int $page
     * @param int $pageSize
     * @return QuestionSearchResultsInterface
     */
    public function getQuestions(int $productId, int $page = 1, int $pageSize = 20): QuestionSearchResultsInterface;

    /**
     * @param int $questionId
     * @return QuestionInterface
     */
    public function getQuestion(int $questionId): QuestionInterface;

    /**
     * @param int $productId
     * @param string $question
     * @return QuestionInterface
     */
    public function addQuestion(int $productId, string $question): QuestionInterface;

    /**
     * @param int $questionId
     * @param string $question
     * @return QuestionInterface
     */
    public function editQuestion(int $questionId, string $question): QuestionInterface;

    /**
     * @param int $questionId
     * @return bool
     */
    public function deleteQuestion(int $questionId): bool;

    /**
     * @param int $questionId
     * @param int $helpful
     * @return bool
     */
    public function voteHelpful(int $questionId, int $helpful): bool;

    /**
     * @param int $questionId
     * @param string $answer
     * @return AnswerInterface
     */
    public function answerQuestion(int $questionId, string $answer): AnswerInterface;
}
