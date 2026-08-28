<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use AlpineCommerce\Rma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;

class Index extends Action
{
    public function __construct(
        private readonly Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly RmaCollectionFactory $rmaCollectionFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_Rma::rma');
        $resultPage->addBreadcrumb(__('RMA'), __('RMA'));
        $resultPage->getConfig()->getTitle()->prepend(__('RMA Requests'));

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_Rma::rma');
    }
}
