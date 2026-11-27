<?php
declare(strict_types=1);

namespace AlpineCommerce\PartialInvoice\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;

class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly InvoiceCollectionFactory $invoiceCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getInvoices()
    {
        $collection = $this->invoiceCollectionFactory->create();
        $collection->addFieldToFilter('base_grand_total', ['lt' => 0]); // heuristic: partial invoices
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(20);
        return $collection;
    }
}
