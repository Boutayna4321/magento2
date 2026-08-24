<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;

class Index extends Template
{
    public function __construct(
        private readonly CreditmemoCollectionFactory $creditmemoCollectionFactory
    ) {
    }

    public function getCreditMemos()
    {
        $collection = $this->creditmemoCollectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(20);
        return $collection;
    }
}
