<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductQuestions\Controller\Adminhtml\Question;

use AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface;
use AlpineCommerce\ProductQuestions\Model\QuestionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductQuestions::question';

    public function __construct(
        Context $context,
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly Registry $coreRegistry,
        private readonly PageFactory $pageFactory,
        private readonly QuestionFactory $questionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('question_id');

        try {
            $model = $id
                ? $this->questionRepository->getById($id)
                : $this->questionFactory->create();
            $this->coreRegistry->register('current_question', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This question no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->_initAction();
        $page->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Question') : __('New Question'));

        return $page;
    }

    private function _initAction()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_ProductQuestions::menu');
        $page->addBreadcrumb(__('Marketing'), __('Questions'));
        $page->addBreadcrumb(__('Product Questions'), __('Product Questions'));

        return $page;
    }
}
