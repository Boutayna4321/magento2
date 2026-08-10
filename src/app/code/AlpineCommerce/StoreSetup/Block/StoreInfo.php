<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreSetup\Block;

use AlpineCommerce\StoreSetup\Helper\Data;
use Magento\Framework\View\Element\Template;

class StoreInfo extends Template
{
    private readonly Data $helper;

    public function __construct(
        Template\Context $context,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    public function getStoreName(): string
    {
        return $this->helper->getStoreName();
    }

    public function getStoreId(): int
    {
        return $this->helper->getStoreId();
    }

    public function getStoreUrl(): string
    {
        return $this->helper->getStoreUrl();
    }

    public function isDisplayStoreInfo(): bool
    {
        return $this->helper->isDisplayStoreInfo();
    }
}
