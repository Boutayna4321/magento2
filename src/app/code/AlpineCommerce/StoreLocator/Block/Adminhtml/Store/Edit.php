<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Block\Adminhtml\Store;

use AlpineCommerce\StorePickup\Api\Data\StoreInfoInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Registry;

class Edit extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getStore(): StoreInfoInterface
    {
        return $this->registry->registry('alphacommerce_store_locator_store');
    }
}
