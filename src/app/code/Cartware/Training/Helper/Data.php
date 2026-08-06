<?php
namespace Cartware\Training\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Helper class for Training module
 *
 * WHAT IT DOES:
 * - Centralized utility functions for the module
 * - Access to store manager, logger, and shared methods
 * - Reads module config from etc/config.xml via ScopeConfigInterface
 * - Used by observers, plugins, blocks, and controllers
 *
 * HOW TO USE:
 * - Inject via constructor: Cartware\Training\Helper\Data
 * - Call methods: $this->helper->getStoreName()
 */
class Data extends AbstractHelper
{
    private $storeManager;
    private $logger;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Get current store name
     */
    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }

    /**
     * Get current store ID
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get store code
     */
    public function getStoreCode()
    {
        return $this->storeManager->getStore()->getCode();
    }

    /**
     * Get store base URL
     */
    public function getStoreUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get current currency code from store config
     */
    public function getCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Check if module is enabled via config.xml / system.xml
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->isSetFlag('training/general/enabled');
    }

    /**
     * Check if store info block should be displayed
     */
    public function isDisplayStoreInfo()
    {
        return $this->scopeConfig->isSetFlag('training/general/display_store_info');
    }

    /**
     * Get default locale for current store from config
     */
    public function getDefaultLocale()
    {
        return $this->scopeConfig->getValue('general/locale/code');
    }

    /**
     * Get default country for current store from config
     */
    public function getDefaultCountry()
    {
        return $this->scopeConfig->getValue('general/country/default');
    }

    /**
     * Get default currency for current store from config
     */
    public function getDefaultCurrency()
    {
        return $this->scopeConfig->getValue('currency/options/default');
    }

    /**
     * Get allowed currencies for current store from config
     */
    public function getAllowedCurrencies()
    {
        return $this->scopeConfig->getValue('currency/options/allow');
    }

    /**
     * Log error message
     */
    public function logError($message)
    {
        $this->logger->error('Training Helper: ' . $message);
    }

    /**
     * Check if current store is the default store
     */
    public function isDefaultStore()
    {
        return $this->getStoreId() == 1;
    }

    /**
     * Get all active stores
     */
    public function getAllStores()
    {
        return $this->storeManager->getStores(true);
    }
}
