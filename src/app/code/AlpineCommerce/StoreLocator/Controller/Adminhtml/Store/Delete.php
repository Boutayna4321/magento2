<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

class Delete extends AbstractStore implements HttpPostActionInterface
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly StoreInfoRepositoryInterface $storeRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');

        try {
            if (!$id) {
                throw new LocalizedException(__('The store ID is missing.'));
            }
            $this->storeRepository->delete($this->storeRepository->getById($id));
            $this->messageManager->addSuccessMessage(__('The store has been deleted.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage(__('The store could not be deleted.'));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
