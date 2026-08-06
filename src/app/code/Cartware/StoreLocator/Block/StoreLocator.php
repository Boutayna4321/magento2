<?php
declare(strict_types=1);

namespace Cartware\StoreLocator\Block;

use Magento\Framework\View\Element\Template;
use Cartware\StorePickup\Model\ResourceModel\StoreInfo\CollectionFactory as StoreCollectionFactory;
use Cartware\StorePickup\Model\ResourceModel\StoreInfo\Collection;

class StoreLocator extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly StoreCollectionFactory $storeCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setStores(
            $this->storeCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
        );
        return $this;
    }

    public function getStores(): Collection
    {
        return $this->getData('stores');
    }
}
