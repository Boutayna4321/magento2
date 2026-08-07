<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class MassStatus extends Action
{
    public function __construct(
        Context $context,
        private readonly ProductLabelRepositoryInterface $labelRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $labelIds = $this->getRequest()->getParam('selected');
        $status = (int) $this->getRequest()->getParam('status');
        if (!is_array($labelIds) || empty($labelIds)) {
            $this->messageManager->addErrorMessage(__('Please select items.'));
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        foreach ($labelIds as $labelId) {
            try {
                $label = $this->labelRepository->getById((int) $labelId);
                $label->setIsActive((bool) $status);
                $this->labelRepository->save($label);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Could not update label ID %1: %2', $labelId, $e->getMessage()));
            }
        }

        $this->messageManager->addSuccessMessage(__('Selected labels have been updated.'));
        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_ProductLabels::labels_save');
    }
}
