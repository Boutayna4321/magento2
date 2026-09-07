<?php
declare(strict_types=1);

namespace AlpineCommerce\Hreflang\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'AlpineCommerce_Hreflang::settings';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AlpineCommerce_Hreflang::settings');
        $page->getConfig()->getTitle()->prepend(__('Hreflang Settings'));

        return $page;
    }
}
