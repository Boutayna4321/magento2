<?php
declare(strict_types=1);

namespace AlpineCommerce\AutoInvoice\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_AutoInvoice::main';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): \Magento\Framework\View\Result\Page
    {
        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->prepend(__('Auto Invoice'));

        return $page;
    }
}
