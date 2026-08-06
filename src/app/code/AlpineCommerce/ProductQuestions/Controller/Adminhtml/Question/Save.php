<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Question;

use AlpineCommerce\ProductQuestions\Api\AnswerRepositoryInterface;
use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\AnswerFactory;
use AlpineCommerce\ProductQuestions\Model\QuestionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Request\DataPersistorInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly AnswerRepositoryInterface $answerRepository,
        private readonly AnswerFactory $answerFactory,
        private readonly QuestionFactory $questionFactory,
        private readonly AuthSession $authSession
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $this->_redirect('*/*/index');
        }

        $id = (int) $this->getRequest()->getParam('question_id');

        try {
            $model = $id
                ? $this->questionRepository->getById($id)
                : $this->questionFactory->create();

            if (isset($data['question_id']) && (int) $data['question_id'] !== $id) {
                $this->dataPersistor->set('alphacommerce_product_question', $data);
                $this->messageManager->addErrorMessage(__('The question was not found.'));
                return $this->_redirect('*/*/edit', ['question_id' => $id]);
            }

            $officialAnswer = $data['official_answer'] ?? '';
            unset($data['official_answer']);

            $model->setData($data);
            $this->questionRepository->save($model);

            if (!empty($officialAnswer)) {
                $this->saveOfficialAnswer((int) $model->getId(), $officialAnswer);
            }

            $this->messageManager->addSuccessMessage(__('The question has been saved.'));
            $this->dataPersistor->clear('alphacommerce_product_question');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to save the question: %1', $e->getMessage()));
            $this->dataPersistor->set('alphacommerce_product_question', $data);
            return $this->_redirect('*/*/edit', ['question_id' => $id]);
        }

        if ($this->getRequest()->getParam('back')) {
            return $this->_redirect('*/*/edit', ['question_id' => $model->getId()]);
        }

        return $this->_redirect('*/*/index');
    }

    private function saveOfficialAnswer(int $questionId, string $answerText): void
    {
        $existingAnswer = $this->findExistingOfficialAnswer($questionId);

        /** @var \AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface $answer */
        $answer = $existingAnswer
            ? $existingAnswer
            : $this->answerFactory->create();

        $answer->setQuestionId($questionId);
        $answer->setCustomerId(null);
        $answer->setAdminUserId((int) $this->authSession->getUserId());
        $answer->setAnswer($answerText);
        $answer->setIsOfficial(true);

        $this->answerRepository->save($answer);
    }

    private function findExistingOfficialAnswer(int $questionId): ?\AlpineCommerce\ProductQuestions\Api\Data\AnswerInterface
    {
        $collection = $this->answerFactory->create()->getCollection();
        $collection->addFieldToFilter('question_id', $questionId);
        $collection->addFieldToFilter('is_official', 1);

        $item = $collection->getFirstItem();
        return $item && $item->getId() ? $item : null;
    }
}
