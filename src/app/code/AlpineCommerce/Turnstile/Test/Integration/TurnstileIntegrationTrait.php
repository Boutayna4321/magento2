<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Integration;

use AlpineCommerce\Turnstile\Model\FailureResponder;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\FormRegistry;
use AlpineCommerce\Turnstile\Model\SiteVerifyClient;
use AlpineCommerce\Turnstile\Model\Validator;
use AlpineCommerce\Turnstile\Observer\FormPredispatchObserver;
use AlpineCommerce\Turnstile\Test\Integration\Stub\FakeSiteVerifyClient;
use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Enables Turnstile for the default store view with the official "always passes" test site key, an
 * encrypted secret and the given forms, and replaces Cloudflare with FakeSiteVerifyClient.
 */
trait TurnstileIntegrationTrait
{
    private FakeSiteVerifyClient $siteVerify;

    /**
     * @param array<string, bool> $forms Form id => enabled
     */
    private function enableTurnstile(array $forms): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $values = [
            'alpinecommerce_turnstile/general/enabled' => '1',
            'alpinecommerce_turnstile/general/site_key' => '1x00000000000000000000AA',
            'alpinecommerce_turnstile/general/secret_key' => $objectManager->get(EncryptorInterface::class)
                ->encrypt('integration-secret'),
            'alpinecommerce_turnstile/general/failure_mode' => 'closed',
        ];
        foreach ($forms as $formId => $enabled) {
            $values['alpinecommerce_turnstile/forms/' . $formId] = $enabled ? '1' : '0';
        }
        $config = $objectManager->get(MutableScopeConfigInterface::class);
        foreach ($values as $path => $value) {
            $config->setValue($path, $value, ScopeInterface::SCOPE_STORE, 'default');
        }

        $this->siteVerify = new FakeSiteVerifyClient();
        $objectManager->addSharedInstance($this->siteVerify, SiteVerifyClient::class);
        $this->resetTurnstileServices();
    }

    private function resetTurnstile(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $objectManager->removeSharedInstance(SiteVerifyClient::class);
        $this->resetTurnstileServices();
        $objectManager->get(ReinitableConfigInterface::class)->reinit();
    }

    /**
     * Shared services hold the response, the registry and the Cloudflare client of the previous test.
     */
    private function resetTurnstileServices(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        foreach ([Validator::class, FormGuard::class, FailureResponder::class, FormRegistry::class,
                     FormPredispatchObserver::class] as $type) {
            $objectManager->removeSharedInstance($type);
        }
    }
}
