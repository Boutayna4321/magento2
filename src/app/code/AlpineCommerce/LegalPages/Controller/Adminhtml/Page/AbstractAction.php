<?php
declare(strict_types=1);

namespace AlpineCommerce\LegalPages\Controller\Adminhtml\Page;

use AlpineCommerce\LegalPages\Api\LegalPageRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

abstract class AbstractAction extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_LegalPages::legal';

    public function __construct(
        Context $context,
        protected readonly LegalPageRepositoryInterface $pageRepository,
        protected readonly Registry $coreRegistry,
        protected readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    protected function initPage()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_LegalPages::menu');
        $page->addBreadcrumb(__('Content'), __('Content'));
        $page->addBreadcrumb(__('Legal Pages'), __('Legal Pages'));

        return $page;
    }
}
