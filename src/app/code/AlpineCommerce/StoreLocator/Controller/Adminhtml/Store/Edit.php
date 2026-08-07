<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use AlpineCommerce\StoreLocator\Api\Data\StoreInterfaceFactory;
use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends AbstractAction
{
    public function __construct(
        Context $context,
        private readonly StoreRepositoryInterface $storeRepository,
        private readonly StoreInterfaceFactory $storeFactory,
        private readonly Registry $coreRegistry,
        PageFactory $pageFactory
    ) {
        parent::__construct($context, $pageFactory);
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('entity_id');

        try {
            $store = $id ? $this->storeRepository->getById($id) : $this->storeFactory->create();
            $this->coreRegistry->register('alphacommerce_storelocator_store', $store);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This store no longer exists.'));
            return $this->_redirect('*/*/index');
        }

        $page = $this->initPage();
        $page->getConfig()->getTitle()->prepend($id ? __('Edit Store') : __('New Store'));

        return $page;
    }
}
