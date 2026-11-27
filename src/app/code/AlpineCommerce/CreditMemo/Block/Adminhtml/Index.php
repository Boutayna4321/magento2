<?php
declare(strict_types=1);

namespace AlpineCommerce\CreditMemo\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;

class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly CreditmemoCollectionFactory $creditmemoCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getCreditMemos()
    {
        $collection = $this->creditmemoCollectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(20);
        return $collection;
    }
}
