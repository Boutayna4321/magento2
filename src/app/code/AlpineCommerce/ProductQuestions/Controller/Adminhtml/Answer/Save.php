<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Answer;

use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\AnswerFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly AnswerFactory $answerFactory,
        private readonly AuthSession $authSession
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $post = $this->getRequest()->getPostValue();

        if (!isset($post['answer']) || !isset($post['question_id'])) {
            $this->messageManager->addErrorMessage(__('Missing required data.'));
            return $this->_redirect('*/*/edit', ['question_id' => $post['question_id'] ?? 0]);
        }

        $questionId = (int) $post['question_id'];
        $answerText = (string) $post['answer'];
        $answerId = (int) ($post['answer_id'] ?? 0);

        try {
            /** @var \AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface $answer */
            $answer = $answerId
                ? $this->answerRepository->getById($answerId)
                : $this->answerFactory->create();

            $answer->setQuestionId($questionId);
            $answer->setCustomerId(null);
            $answer->setAdminUserId((int) $this->authSession->getUserId());
            $answer->setAnswer($answerText);
            $answer->setIsOfficial(true);

            $this->answerRepository->save($answer);

            $this->messageManager->addSuccessMessage(__('The answer has been saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the answer: %1', $e->getMessage()));
        }

        return $this->_redirect('*/*/edit', ['question_id' => $questionId]);
    }
}
