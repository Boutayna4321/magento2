<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly ProductLabelRepositoryInterface $labelRepository,
        private readonly Registry $coreRegistry
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $labelId = (int) $this->getRequest()->getParam('entity_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_ProductLabels::labels');

        if ($labelId) {
            try {
                $label = $this->labelRepository->getById($labelId);
                $resultPage->getConfig()->getTitle()->prepend(__('Edit Product Label') . ' - ' . $label->getName());
                $resultPage->addBreadcrumb(__('Edit Product Label'), __('Edit Product Label'));
                $this->coreRegistry->register('productlabels_label', $label);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This product label no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Product Label'));
            $resultPage->addBreadcrumb(__('New Product Label'), __('New Product Label'));
        }

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_ProductLabels::labels_save');
    }
}
