<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Controller\Adminhtml\Partialinvoice;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public function __construct(
        private readonly Context $context,
        private readonly PageFactory $resultPageFactory,
        private readonly InvoiceCollectionFactory $invoiceCollectionFactory
    ) {
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_PartialInvoice::partialinvoice');
        $resultPage->addBreadcrumb(__('Partial Invoice'), __('Partial Invoice'));
        $resultPage->getConfig()->getTitle()->prepend(__('Partial Invoices'));

        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('AlpineCommerce_PartialInvoice::partialinvoice');
    }
}
