<?php
declare(strict_types=1);

namespace AlpineCommerce\EuVat\Controller\Adminhtml\Validation;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_EuVat::validation_history';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_EuVat::validation');
        $page->getConfig()->getTitle()->prepend(__('EU VAT Validation History'));

        return $page;
    }
}
