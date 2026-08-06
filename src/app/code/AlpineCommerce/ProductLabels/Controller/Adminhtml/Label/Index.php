<?php
declare(strict_types=1);

namespace AlpineCommerce\ProductLabels\Controller\Adminhtml\Label;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public function __construct(Context $context, private readonly PageFactory $resultPageFactory) { parent::__construct($context); }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu("AlpineCommerce_ProductLabels::labels");
        $resultPage->addBreadcrumb(__("Product Labels"), __("Product Labels"));
        $resultPage->getConfig()->getTitle()->prepend(__("Product Labels"));
        return $resultPage;
    }

    protected function _isAllowed(): bool { return $this->_authorization->isAllowed("AlpineCommerce_ProductLabels::labels"); }
}
