<?php
declare(strict_types=1);

namespace AlpineCommerce\Hreflang\Block;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Hreflang extends Template
{
    public const XML_PATH_ENABLED = 'alphacommerce_hreflang/general/enabled';
    public const XML_PATH_X_DEFAULT = 'alphacommerce_hreflang/general/x_default';

    public function __construct(
        Context $context,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getAlternateLinks(): array
    {
        $store = $this->storeManager->getStore();

        if (!$this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            (int) $store->getId()
        )) {
            return [];
        }

        $path = $this->extractPath();
        $links = [];

        foreach ($this->storeManager->getStores() as $targetStore) {
            if (!$targetStore->getIsActive()) {
                continue;
            }

            $links[] = [
                'hreflang' => $this->getHreflangCode($targetStore),
                'href' => $this->buildUrl($targetStore, $path),
            ];
        }

        if ($this->scopeConfig->isSetFlag(
            self::XML_PATH_X_DEFAULT,
            ScopeInterface::SCOPE_STORE,
            (int) $store->getId()
        )) {
            $defaultStore = $this->storeManager->getDefaultStoreView();
            if ($defaultStore) {
                $links[] = [
                    'hreflang' => 'x-default',
                    'href' => $this->buildUrl($defaultStore, $path),
                ];
            }
        }

        return $links;
    }

    private function getHreflangCode(StoreInterface $store): string
    {
        $locale = (string) $store->getConfig('general/locale/code');

        return $locale === '' ? 'x-default' : str_replace('_', '-', $locale);
    }

    private function buildUrl(StoreInterface $store, string $path): string
    {
        $baseUrl = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_WEB), '/');

        if ($this->scopeConfig->isSetFlag(
            Store::XML_PATH_STORE_IN_URL,
            ScopeInterface::SCOPE_STORE,
            (int) $store->getId()
        ) && $store->getCode() !== 'default') {
            $baseUrl .= '/' . $store->getCode();
        }

        return $baseUrl . '/' . ltrim($path, '/');
    }

    private function extractPath(): string
    {
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $currentUrl = (string) $this->_urlBuilder->getCurrentUrl();

        if ($currentUrl === '' || strpos($currentUrl, $baseUrl) !== 0) {
            return '/';
        }

        $path = substr($currentUrl, strlen($baseUrl));

        if (substr($path, 0, strlen($store->getCode()) + 1) === $store->getCode() . '/') {
            $path = substr($path, strlen($store->getCode()) + 1);
        }

        return $path === '' ? '/' : $path;
    }
}
