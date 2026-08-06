<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterfaceFactory;
use AlpineCommerce\StorePickup\Api\StoreInfoRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;

class Edit extends AbstractStore
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        private readonly StoreInfoRepositoryInterface $storeRepository,
        private readonly StoreInfoInterfaceFactory $storeFactory,
        private readonly Registry $registry
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $store = $this->storeFactory->create();

        if ($id) {
            try {
                $store = $this->storeRepository->getById($id);
            } catch (NoSuchEntityException) {
                $this->messageManager->addErrorMessage(__('This store no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        }

        $this->registry->register('alphacommerce_store_locator_store', $store);
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $page->getConfig()->getTitle()->prepend($id ? __('Edit Store') : __('New Store'));

        return $page;
    }
}
