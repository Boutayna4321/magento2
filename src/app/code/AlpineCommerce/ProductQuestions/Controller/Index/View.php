<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Index;

use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;

class View extends Action
{
    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $questionId = (int) $this->getRequest()->getParam('id');

        try {
            $question = $this->questionRepository->getById($questionId);
        } catch (\Exception $e) {
            $result = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $result->forward('noroute');
            return $result;
        }

        $this->coreRegistry->register('current_question', $question);

        /** @var Page $page */
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->set($question->getQuestion());

        return $page;
    }
}
