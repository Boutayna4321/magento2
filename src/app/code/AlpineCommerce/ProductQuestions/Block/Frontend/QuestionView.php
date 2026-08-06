<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Block\Frontend;

use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface;
use AlpineCommerce\ProductQuestions\Api\Data\QuestionInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class QuestionView extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly Registry $coreRegistry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getQuestion(): QuestionInterface
    {
        return $this->coreRegistry->registry('current_question');
    }

    /**
     * @return AnswerInterface[]
     */
    public function getAnswers(): array
    {
        $question = $this->getQuestion();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(AnswerInterface::QUESTION_ID, $question->getId())
            ->create();

        return $this->answerRepository->getList($searchCriteria)->getItems();
    }
}
