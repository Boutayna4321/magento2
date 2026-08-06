<?php
declare(strict_types=1);

namespace AlpineCommerce\Training\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    private readonly StoreManagerInterface $storeManager;
    private readonly LoggerInterface $logger;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function getStoreName(): string
    {
        return $this->storeManager->getStore()->getName();
    }

    public function getStoreId(): int
    {
        return $this->storeManager->getStore()->getId();
    }

    public function getStoreCode(): string
    {
        return $this->storeManager->getStore()->getCode();
    }

    public function getStoreUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    public function getCurrencyCode(): ?string
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    public function isModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag('training/general/enabled');
    }

    public function isDisplayStoreInfo(): bool
    {
        return $this->scopeConfig->isSetFlag('training/general/display_store_info');
    }

    public function getDefaultLocale(): ?string
    {
        return $this->scopeConfig->getValue('general/locale/code');
    }

    public function getDefaultCountry(): ?string
    {
        return $this->scopeConfig->getValue('general/country/default');
    }

    public function getDefaultCurrency(): ?string
    {
        return $this->scopeConfig->getValue('currency/options/default');
    }

    public function getAllowedCurrencies(): ?string
    {
        return $this->scopeConfig->getValue('currency/options/allow');
    }

    public function logError(string $message): void
    {
        $this->logger->error('Training Helper: ' . $message);
    }

    public function isDefaultStore(): bool
    {
        return $this->getStoreId() == 1;
    }

    public function getAllStores(): array
    {
        return $this->storeManager->getStores(true);
    }
}
