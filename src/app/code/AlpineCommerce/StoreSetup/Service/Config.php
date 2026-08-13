<?php
declare(strict_types=1);

namespace AlpineCommerce\StoreSetup\Service;

use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Psr\Log\LoggerInterface;

class Config
{
    private readonly StoreManagerInterface $storeManager;
    private readonly LoggerInterface $logger;
    private readonly ScopeConfigInterface $scopeConfig;

    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
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
        return $this->scopeConfig->isSetFlag('storesetup/general/enabled');
    }

    public function isDisplayStoreInfo(): bool
    {
        return $this->scopeConfig->isSetFlag('storesetup/general/display_store_info');
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
        $this->logger->error('StoreSetup Service: ' . $message);
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
