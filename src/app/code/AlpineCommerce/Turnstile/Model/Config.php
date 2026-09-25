<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Model\Config\Source\FailureMode;
use AlpineCommerce\Turnstile\Model\Config\Source\Theme;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

/**
 * Per-store Turnstile configuration.
 */
class Config
{
    public const XML_PATH_ENABLED = 'alpinecommerce_turnstile/general/enabled';
    public const XML_PATH_SITE_KEY = 'alpinecommerce_turnstile/general/site_key';
    public const XML_PATH_SECRET_KEY = 'alpinecommerce_turnstile/general/secret_key';
    public const XML_PATH_THEME = 'alpinecommerce_turnstile/general/theme';
    public const XML_PATH_TIMEOUT = 'alpinecommerce_turnstile/general/timeout';
    public const XML_PATH_FAILURE_MODE = 'alpinecommerce_turnstile/general/failure_mode';
    public const XML_PATH_FORM_PREFIX = 'alpinecommerce_turnstile/forms/';
    public const FAILURE_MODE_SUFFIX = '_failure_mode';
    public const XML_PATH_TWIN_PREFIX = 'alpinecommerce_turnstile/api_twins/';

    private const DEFAULT_TIMEOUT = 5;
    private const MIN_TIMEOUT = 1;
    private const MAX_TIMEOUT = 30;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Active only when the module, the form and both keys are configured for the store.
     */
    public function isEnabledFor(string $formId, ?int $storeId = null): bool
    {
        if (!$this->isFlag(self::XML_PATH_ENABLED, $storeId)
            || !$this->isFlag(self::XML_PATH_FORM_PREFIX . $formId, $storeId)
        ) {
            return false;
        }

        if ($this->getSiteKey($storeId) === '' || $this->getSecretKey($storeId) === '') {
            $this->logger->warning(
                'Turnstile is enabled but the site key or secret key is missing.',
                ['form_id' => $formId, 'store_id' => $storeId]
            );
            return false;
        }

        return true;
    }

    public function getSiteKey(?int $storeId = null): string
    {
        return trim($this->getString(self::XML_PATH_SITE_KEY, $storeId));
    }

    public function getSecretKey(?int $storeId = null): string
    {
        $encrypted = $this->getString(self::XML_PATH_SECRET_KEY, $storeId);

        return $encrypted === '' ? '' : trim($this->encryptor->decrypt($encrypted));
    }

    public function getTheme(?int $storeId = null): string
    {
        $theme = $this->getString(self::XML_PATH_THEME, $storeId);

        return in_array($theme, Theme::VALUES, true) ? $theme : Theme::AUTO;
    }

    public function getTimeout(?int $storeId = null): int
    {
        $timeout = (int) $this->getString(self::XML_PATH_TIMEOUT, $storeId);

        return $timeout < self::MIN_TIMEOUT || $timeout > self::MAX_TIMEOUT ? self::DEFAULT_TIMEOUT : $timeout;
    }

    /**
     * The form's own failure mode when it is set to closed or open, otherwise the general one.
     */
    public function getFailureMode(?int $storeId = null, ?string $formId = null): string
    {
        if ($formId !== null) {
            $override = $this->getString(self::XML_PATH_FORM_PREFIX . $formId . self::FAILURE_MODE_SUFFIX, $storeId);
            if (in_array($override, [FailureMode::OPEN, FailureMode::CLOSED], true)) {
                return $override;
            }
        }

        return $this->getString(self::XML_PATH_FAILURE_MODE, $storeId) === FailureMode::OPEN
            ? FailureMode::OPEN
            : FailureMode::CLOSED;
    }

    /**
     * Whether anonymous calls to an API twin are blocked for the store view (decision D4 = B).
     * Independent of the general Turnstile switch (decision M2 = 1): blocking never calls Cloudflare.
     */
    public function isTwinBlocked(string $twinId, ?int $storeId = null): bool
    {
        return $this->isFlag(self::XML_PATH_TWIN_PREFIX . $twinId, $storeId);
    }

    private function isFlag(string $path, ?int $storeId): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    private function getString(string $path, ?int $storeId): string
    {
        return (string) $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
