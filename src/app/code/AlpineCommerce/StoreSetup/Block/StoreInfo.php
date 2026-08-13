<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreSetup\Block;

use AlpineCommerce\StoreSetup\Service\Config;
use Magento\Framework\View\Element\Template;

class StoreInfo extends Template
{
    private readonly Config $config;

    public function __construct(
        Template\Context $context,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function getStoreName(): string
    {
        return $this->config->getStoreName();
    }

    public function getStoreId(): int
    {
        return $this->config->getStoreId();
    }

    public function getStoreUrl(): string
    {
        return $this->config->getStoreUrl();
    }

    public function isDisplayStoreInfo(): bool
    {
        return $this->config->isDisplayStoreInfo();
    }
}
