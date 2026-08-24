<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Controller\Adminhtml\Creditmemo;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public function __construct(
        private readonly Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly CreditmemoCollectionFactory $creditmemoCollectionFactory
    ) {
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_CreditMemo::creditmemo');
        $resultPage->addBreadcrumb(__('Auto Credit Memo'), __('Auto Credit Memo'));
        $resultPage->getConfig()->getTitle()->prepend(__('Auto Credit Memos'));

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_CreditMemo::creditmemo');
    }
}
