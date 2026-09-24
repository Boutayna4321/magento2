<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\ViewModel;

use AlpineCommerce\Turnstile\Model\Config;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Store-level data for the Turnstile widget. Exposes nothing user-specific (FPC safe)
 * and never the secret key.
 */
class Widget implements ArgumentInterface
{
    public const SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';

    /** Two-letter codes supported by Turnstile (Cloudflare docs, 2026-09-24). */
    private const SUPPORTED_LANGUAGES = [
        'ar', 'bg', 'zh', 'hr', 'cs', 'da', 'nl', 'en', 'fa', 'fi', 'fr', 'de', 'el', 'he', 'hi', 'hu',
        'id', 'it', 'ja', 'ko', 'lt', 'ms', 'nb', 'pl', 'pt', 'ro', 'ru', 'sr', 'sk', 'sl', 'es', 'sv',
        'tl', 'th', 'tr', 'uk', 'vi',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly ResolverInterface $localeResolver,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isEnabled(string $formId): bool
    {
        return $this->config->isEnabledFor($formId, $this->getStoreId());
    }

    public function getSiteKey(): string
    {
        return $this->config->getSiteKey($this->getStoreId());
    }

    public function getTheme(): string
    {
        return $this->config->getTheme($this->getStoreId());
    }

    public function getLanguage(): string
    {
        $language = strtolower(explode('_', (string) $this->localeResolver->getLocale())[0]);

        return in_array($language, self::SUPPORTED_LANGUAGES, true) ? $language : 'auto';
    }

    public function getScriptUrl(): string
    {
        return self::SCRIPT_URL;
    }

    private function getStoreId(): int
    {
        return (int) $this->storeManager->getStore()->getId();
    }
}
