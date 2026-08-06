<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use AlpineCommerce\ProductLabels\Api\ProductLabelRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Delete extends Action
{
    public function __construct(Context $context, private readonly ProductLabelRepositoryInterface $labelRepository) { parent::__construct($context); }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $labelId = (int) $this->getRequest()->getParam("entity_id");
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($labelId) {
            try {
                $label = $this->labelRepository->getById($labelId);
                $this->labelRepository->delete($label);
                $this->messageManager->addSuccessMessage(__("Product label deleted successfully."));
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__("Product label no longer exists."));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__("Could not delete: %1", $e->getMessage()));
            }
        }
        return $resultRedirect->setPath("*/*/");
    }

    protected function _isAllowed(): bool { return $this->_authorization->isAllowed("AlpineCommerce_ProductLabels::labels_delete"); }
}
