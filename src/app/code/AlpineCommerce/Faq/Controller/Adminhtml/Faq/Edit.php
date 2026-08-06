<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Controller\Adminhtml\Faq;

use AlpineCommerce\Faq\Api\FaqRepositoryInterface;
use AlpineCommerce\Faq\Model\FaqFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Faq::faq';

    public function __construct(
        Context $context,
        private readonly FaqRepositoryInterface $faqRepository,
        private readonly Registry $coreRegistry,
        private readonly PageFactory $pageFactory,
        private readonly FaqFactory $faqFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('faq_id');

        try {
            $model = $id ? $this->faqRepository->getById($id) : $this->faqFactory->create();
            $this->coreRegistry->register('current_faq', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This FAQ entry no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->_initAction();
        $page->getConfig()->getTitle()->prepend($model->getId() ? __('Edit FAQ') : __('New FAQ'));

        return $page;
    }

    private function _initAction()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_Faq::menu');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('FAQ'), __('FAQ'));

        return $page;
    }
}
