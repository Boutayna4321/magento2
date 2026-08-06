<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Answer;

use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly AnswerRepositoryInterface $answerRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $answerId = (int) $this->getRequest()->getParam('answer_id');
        $questionId = (int) $this->getRequest()->getParam('question_id');

        try {
            $this->answerRepository->deleteById($answerId);
            $this->messageManager->addSuccessMessage(__('The answer has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the answer.'));
        }

        return $this->_redirect('*/*/edit', ['question_id' => $questionId]);
    }
}
