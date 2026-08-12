<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;

class Delete extends AbstractAction
{
    public function __construct(
        Context $context,
        private readonly StoreRepositoryInterface $storeRepository,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('entity_id');

        try {
            $this->storeRepository->delete($this->storeRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The store has been deleted.'));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This store no longer exists.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to delete the store.'));
        }

        return $this->_redirect('*/*/index');
    }
}
