<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductReviews\Controller\Adminhtml\Review;

use AlpineCommerce\ProductReviews\Api\ReviewRepositoryInterface;
use AlpineCommerce\ProductReviews\Model\ReviewFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_ProductReviews::review';

    public function __construct(
        Context $context,
        private readonly ReviewRepositoryInterface $reviewRepository,
        private readonly Registry $coreRegistry,
        private readonly PageFactory $pageFactory,
        private readonly ReviewFactory $reviewFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('review_id');

        try {
            $model = $id
                ? $this->reviewRepository->getById($id)
                : $this->reviewFactory->create();
            $this->coreRegistry->register('current_review', $model);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This review no longer exists.'));
            return $this->_redirect('*/*/');
        }

        $page = $this->_initAction();
        $page->getConfig()->getTitle()->prepend($model->getId() ? __('Edit Review') : __('New Review'));

        return $page;
    }

    private function _initAction()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_ProductReviews::menu');
        $page->addBreadcrumb(__('Marketing'), __('Reviews'));
        $page->addBreadcrumb(__('Product Reviews'), __('Product Reviews'));

        return $page;
    }
}
