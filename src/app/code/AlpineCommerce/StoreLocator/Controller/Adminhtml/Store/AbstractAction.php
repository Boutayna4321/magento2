<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Controller\Adminhtml\Store;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_StoreLocator::store';

    public function __construct(
        Context $context,
        protected readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    protected function initPage(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_StoreLocator::main');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Store Locator'), __('Store Locator'));

        return $page;
    }
}
