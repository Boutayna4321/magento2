<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Block\Adminhtml;

use Magento\Backend\Block\Template;
use AlpineCommerce\Rma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;

class Index extends Template
{
    public function __construct(
        private readonly RmaCollectionFactory $rmaCollectionFactory
    ) {
    }

    public function getRmas()
    {
        $collection = $this->rmaCollectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(20);
        return $collection;
    }
}
