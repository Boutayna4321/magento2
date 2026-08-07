<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreLocator\Block;

use AlpineCommerce\StoreLocator\Api\StoreRepositoryInterface;
use Magento\Framework\View\Element\Template;

class StoreLocator extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly StoreRepositoryInterface $storeRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getStores()
    {
        return $this->storeRepository->getActiveStores();
    }
}
