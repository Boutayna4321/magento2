<?php
declare(strict_types=1);

namespace AlpineCommerce\Faq\Controller\Adminhtml\Faq;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Faq::faq';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_Faq::menu');
        $page->getConfig()->getTitle()->prepend(__('FAQ'));
        $page->addBreadcrumb(__('Content'), __('Content'));

        return $page;
    }
}
