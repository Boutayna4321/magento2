<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Answer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Grid extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly \AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface $answerRepository,
        private readonly \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $questionId = (int) $this->getRequest()->getParam('question_id', 0);

        if (!$questionId) {
            return $result->setData(['items' => []]);
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(\AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface::QUESTION_ID, $questionId)
            ->create();

        $answers = $this->answerRepository->getList($searchCriteria)->getItems();

        $data = [];
        foreach ($answers as $answer) {
            $data[] = [
                'answer_id' => $answer->getId(),
                'question_id' => $answer->getQuestionId(),
                'answer' => $answer->getAnswer(),
                'is_official' => $answer->getIsOfficial(),
                'admin_user_id' => $answer->getAdminUserId(),
                'customer_id' => $answer->getCustomerId(),
                'created_at' => $answer->getCreatedAt(),
            ];
        }

        return $result->setData(['items' => $data]);
    }
}
