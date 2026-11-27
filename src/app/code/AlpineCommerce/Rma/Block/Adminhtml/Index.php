<?php
declare(strict_types=1);

namespace AlpineCommerce\Rma\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use AlpineCommerce\Rma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;

class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly RmaCollectionFactory $rmaCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getRmas()
    {
        $collection = $this->rmaCollectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(20);
        return $collection;
    }
}
