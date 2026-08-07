<?php
declare(strict_types=1);

namespace AlpineCommerce\StorePickup\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_StorePickup::store';

    public function __construct(
        Context $context,
        protected readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    protected function initPage(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_StorePickup::main');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Store Pickup'), __('Store Pickup'));

        return $page;
    }
}
