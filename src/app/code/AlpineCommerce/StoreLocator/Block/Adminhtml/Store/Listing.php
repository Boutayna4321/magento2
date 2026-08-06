<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Block\Adminhtml\Store;

use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\Collection;
use AlpineCommerce\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory;
use Magento\Backend\Block\Template;

class Listing extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly CollectionFactory $collectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getStores(): Collection
    {
        return $this->collectionFactory->create()->setOrder('name', Collection::SORT_ORDER_ASC);
    }
}
