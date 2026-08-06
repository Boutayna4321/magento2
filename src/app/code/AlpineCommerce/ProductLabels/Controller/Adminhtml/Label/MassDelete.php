<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;

class MassDelete extends Action
{
    public function __construct(Context $context, private readonly \AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface $labelRepository) { parent::__construct($context); }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $labelIds = $this->getRequest()->getParam("selected");
        if (!is_array($labelIds) || empty($labelIds)) {
            $this->messageManager->addErrorMessage(__("Please select items to delete."));
            return $this->resultRedirectFactory->create()->setPath("*/*/");
        }
        foreach ($labelIds as $labelId) {
            try {
                $label = $this->labelRepository->getById((int) $labelId);
                $this->labelRepository->delete($label);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("Could not delete label ID %1: %2", $labelId, $e->getMessage()));
            }
        }
        $this->messageManager->addSuccessMessage(__("Selected labels have been deleted."));
        return $this->resultRedirectFactory->create()->setPath("*/*/");
    }

    protected function _isAllowed(): bool { return $this->_authorization->isAllowed("AlpineCommerce_ProductLabels::labels_delete"); }
}
