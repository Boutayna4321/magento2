# Cloudflare Turnstile on Contact Us — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Protect the storefront Contact Us form with Cloudflare Turnstile through a new, reusable module `AlpineCommerce_Turnstile`.

**Architecture:** A widget block is injected into the existing `form.additional.info` container of `contactForm` (no template override). A predispatch observer on `contact/index/post` calls a generic `FormGuard`, which uses a `Validator` backed by a `SiteVerifyClient` (the only HTTP call to Cloudflare). All settings are per store view; the secret is encrypted.

**Tech Stack:** Magento 2.4.8 (PHP 8.3, developer mode), Hyvä default theme 1.5.2 (Alpine.js, `HyvaCsp`), PHPUnit 10.5, Docker (`magento2-php`, web root `/var/www/html` = `src/`).

**Spec:** `docs/superpowers/specs/2026-09-24-turnstile-contact-design.md`

## Global Constraints

- No modification in `src/vendor/`.
- No override of `Magento_Contact::form.phtml` (Hyvä) and no change to theme `AlpineCommerce/hyva`.
- `Magento_ReCaptchaContact` stays untouched; no checkout change.
- Server-side validation is mandatory; the client-side check is UX only.
- Secret key: server-side only, backend model `Magento\Config\Model\Config\Backend\Encrypted`, declared sensitive.
- Never put a token, a secret or the raw POST body into a log record (message or context).
- Widget markup depends only on the store (FPC compatible).
- `failure_mode` default: `closed`.
- Every `bin/magento` / `composer` command: `docker exec -u www-data magento2-php …` (`-w /var/www/html` for composer).
- Every SQL query: `--default-character-set=utf8mb4`; read-only queries as `-u mysql` in `magento2-mysql`.
- Translation CSVs: checked with Python `csv`, never `grep`; no `key == value` line; German "Sie", Spanish/Italian informal "tu"; only customer-facing texts.
- Any pre-check before a write must abort the command on failure (`set -e`, explicit `exit 1` / `assert`).
- Stage files explicitly (`git add <paths>`); the working tree contains unrelated uncommitted work that must not be committed. Commit on `main`; push only in Task 8.
- Unit test command (from repo root):
  `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
  (Existing `HealthCheck` unit tests already error; they are unrelated and out of scope.)

## Review Focus

1. **Token sent as an array** (`cf-turnstile-response[]=x`): must be treated as empty, no PHP warning, rejected → `FormGuardTest::testArrayTokenIsTreatedAsEmpty` (Task 4).
2. **Siteverify `success: true` without `action`** (or `success: "true"` as a string): must be rejected, never accepted by loose comparison → `ValidatorTest::testSuccessWithoutActionIsRejected`, `testNonBooleanSuccessIsRejected` (Task 3).
3. **Keys that are only whitespace** in config: must count as missing, widget not rendered → `ConfigTest::testWhitespaceKeysCountAsMissing` (Task 1).
4. **Client IP unavailable** (`RemoteAddress` returns `false`): `remoteip` omitted, validation still runs → `FormGuardTest::testMissingRemoteAddressPassesNull` + `SiteVerifyClientTest::testRemoteIpOmittedWhenNull` (Tasks 2 and 4).
5. **Failed submission keeps the customer's text but not the token**: `DataPersistor('contact_us')` receives the params without `cf-turnstile-response` → `ContactFormObserverTest::testFailureStoresParamsWithoutToken` (Task 4).

---

## File map

All under `src/app/code/AlpineCommerce/Turnstile/` unless stated otherwise.

| File | Responsibility | Task |
|---|---|---|
| `registration.php`, `composer.json`, `etc/module.xml` | Module declaration | 1 |
| `etc/config.xml`, `etc/acl.xml`, `etc/adminhtml/system.xml` | Defaults, ACL, admin fields | 1 |
| `Model/Config/Source/Theme.php`, `Model/Config/Source/FailureMode.php` | Admin option lists + constants | 1 |
| `Model/Config.php` | Per-store config reader | 1 |
| `Exception/SiteVerifyUnavailableException.php` | Transport-level failure | 2 |
| `Model/SiteVerifyClient.php` | HTTP POST to Siteverify | 2 |
| `Model/ValidationResult.php`, `Api/ValidatorInterface.php`, `Model/Validator.php` | Business rules | 3 |
| `etc/di.xml` | Preference, logger, Siteverify URL, sensitive path | 3 |
| `Model/FormGuard.php`, `Observer/ContactFormObserver.php`, `etc/frontend/events.xml` | Request guard + Contact wiring | 4 |
| `ViewModel/Widget.php`, `view/frontend/layout/contact_index_index.xml`, `view/frontend/templates/widget.phtml`, `etc/csp_whitelist.xml` | Frontend widget | 5 |
| `i18n/{fr_FR,de_DE,es_ES,it_IT}.csv` | Customer-facing translations | 6 |
| `src/app/etc/config.php` (modified) | Module enabled | 7 |
| `docs/modules/TURNSTILE.md`, `README.md`, `docs/README.md` (modified) | Documentation | 8 |

---

### Task 1: Module skeleton, admin configuration and `Config` reader

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/registration.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/composer.json`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/module.xml`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/config.xml`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/acl.xml`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/adminhtml/system.xml`
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/Config/Source/Theme.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/Config/Source/FailureMode.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/Config.php`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/ConfigTest.php`

**Interfaces:**
- Produces:
  - `Theme::AUTO|LIGHT|DARK` (`'auto'|'light'|'dark'`), `Theme::VALUES`
  - `FailureMode::CLOSED|OPEN` (`'closed'|'open'`)
  - `Config::isEnabledFor(string $formId, ?int $storeId = null): bool`
  - `Config::getSiteKey(?int $storeId = null): string`
  - `Config::getSecretKey(?int $storeId = null): string` (decrypted)
  - `Config::getTheme(?int $storeId = null): string`
  - `Config::getTimeout(?int $storeId = null): int` (1..30, default 5)
  - `Config::getFailureMode(?int $storeId = null): string`

- [ ] **Step 1: Create the module declaration files**

`registration.php`:

```php
<?php
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'AlpineCommerce_Turnstile',
    __DIR__
);
```

`composer.json`:

```json
{
    "name": "alpinecommerce/module-turnstile",
    "description": "Cloudflare Turnstile protection for storefront forms (Contact Us)",
    "type": "magento2-module",
    "license": "proprietary",
    "require": {
        "php": ">=8.1",
        "magento/framework": "*",
        "magento/module-contact": "*",
        "magento/module-store": "*",
        "magento/module-config": "*",
        "hyva-themes/magento2-theme-module": "*"
    },
    "autoload": {
        "files": ["registration.php"],
        "psr-4": {"AlpineCommerce\\Turnstile\\": ""}
    }
}
```

`etc/module.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:module/etc/module.xsd">
    <module name="AlpineCommerce_Turnstile">
        <sequence>
            <module name="Magento_Store"/>
            <module name="Magento_Config"/>
            <module name="Magento_Contact"/>
            <module name="Hyva_Theme"/>
        </sequence>
    </module>
</config>
```

- [ ] **Step 2: Create defaults, ACL and admin fields**

`etc/config.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <alpinecommerce_turnstile>
            <general>
                <enabled>0</enabled>
                <theme>auto</theme>
                <timeout>5</timeout>
                <failure_mode>closed</failure_mode>
            </general>
            <forms>
                <contact>1</contact>
            </forms>
        </alpinecommerce_turnstile>
    </default>
</config>
```

`etc/acl.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
    <acl>
        <resources>
            <resource id="Magento_Backend::admin">
                <resource id="Magento_Backend::stores">
                    <resource id="Magento_Backend::stores_settings">
                        <resource id="Magento_Config::config">
                            <resource id="AlpineCommerce_Turnstile::config" title="Cloudflare Turnstile" translate="title"/>
                        </resource>
                    </resource>
                </resource>
            </resource>
        </resources>
    </acl>
</config>
```

`etc/adminhtml/system.xml` (the `alpinecommerce` tab is redeclared identically to `CustomerCare` so this module does not depend on it; Magento merges identical tabs):

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <tab id="alpinecommerce" sortOrder="300" translate="label">
            <label>AlpineCommerce</label>
        </tab>
        <section id="alpinecommerce_turnstile" translate="label" sortOrder="90" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>Cloudflare Turnstile</label>
            <tab>alpinecommerce</tab>
            <resource>AlpineCommerce_Turnstile::config</resource>
            <group id="general" translate="label" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>General</label>
                <field id="enabled" translate="label comment" type="select" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Enable Turnstile</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                    <comment>Do not enable Google reCAPTCHA on the same form (Security > Google reCAPTCHA Storefront).</comment>
                </field>
                <field id="site_key" translate="label" type="text" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Site Key</label>
                </field>
                <field id="secret_key" translate="label" type="obscure" sortOrder="30" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Secret Key</label>
                    <backend_model>Magento\Config\Model\Config\Backend\Encrypted</backend_model>
                </field>
                <field id="theme" translate="label" type="select" sortOrder="40" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Widget Theme</label>
                    <source_model>AlpineCommerce\Turnstile\Model\Config\Source\Theme</source_model>
                </field>
                <field id="timeout" translate="label comment" type="text" sortOrder="50" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Siteverify Timeout (seconds)</label>
                    <validate>required-entry validate-digits validate-digits-range digits-range-1-30</validate>
                    <comment>Between 1 and 30.</comment>
                </field>
                <field id="failure_mode" translate="label comment" type="select" sortOrder="60" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>If Cloudflare Is Unavailable</label>
                    <source_model>AlpineCommerce\Turnstile\Model\Config\Source\FailureMode</source_model>
                    <comment>Closed: reject the form. Open: accept it without verification and log a warning.</comment>
                </field>
            </group>
            <group id="forms" translate="label" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>Protected Forms</label>
                <field id="contact" translate="label" type="select" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Contact Us</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
            </group>
        </section>
    </system>
</config>
```

- [ ] **Step 3: Create the source models**

`Model/Config/Source/Theme.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Theme implements OptionSourceInterface
{
    public const AUTO = 'auto';
    public const LIGHT = 'light';
    public const DARK = 'dark';
    public const VALUES = [self::AUTO, self::LIGHT, self::DARK];

    public function toOptionArray(): array
    {
        return [
            ['value' => self::AUTO, 'label' => __('Auto')],
            ['value' => self::LIGHT, 'label' => __('Light')],
            ['value' => self::DARK, 'label' => __('Dark')],
        ];
    }
}
```

`Model/Config/Source/FailureMode.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FailureMode implements OptionSourceInterface
{
    public const CLOSED = 'closed';
    public const OPEN = 'open';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::CLOSED, 'label' => __('Closed (reject the form)')],
            ['value' => self::OPEN, 'label' => __('Open (accept without verification)')],
        ];
    }
}
```

- [ ] **Step 4: Write the failing `ConfigTest`**

`Test/Unit/Model/ConfigTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ConfigTest extends TestCase
{
    private const ENABLED = [
        Config::XML_PATH_ENABLED => '1',
        Config::XML_PATH_FORM_PREFIX . 'contact' => '1',
        Config::XML_PATH_SITE_KEY => 'site-key',
        Config::XML_PATH_SECRET_KEY => 'encrypted-secret',
    ];

    private AbstractLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
    }

    private function config(array $values): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn (string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn (string $path) => (bool) ($values[$path] ?? false)
        );
        $encryptor = $this->createMock(EncryptorInterface::class);
        $encryptor->method('decrypt')->willReturnCallback(
            static fn (string $value) => $value === 'encrypted-secret' ? 'plain-secret' : ''
        );

        return new Config($scopeConfig, $encryptor, $this->logger);
    }

    public function testEnabledWhenAllConditionsHold(): void
    {
        $this->assertTrue($this->config(self::ENABLED)->isEnabledFor('contact', 6));
    }

    public function testDisabledGlobally(): void
    {
        $values = [Config::XML_PATH_ENABLED => '0'] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testDisabledForForm(): void
    {
        $values = [Config::XML_PATH_FORM_PREFIX . 'contact' => '0'] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testUnknownFormIsDisabled(): void
    {
        $this->assertFalse($this->config(self::ENABLED)->isEnabledFor('newsletter', 6));
    }

    public function testMissingSecretDisablesAndLogsWarning(): void
    {
        $values = [Config::XML_PATH_SECRET_KEY => ''] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
        $this->assertSame('warning', $this->logger->records[0]['level']);
        $this->assertSame(['form_id' => 'contact', 'store_id' => 6], $this->logger->records[0]['context']);
    }

    public function testWhitespaceKeysCountAsMissing(): void
    {
        $values = [Config::XML_PATH_SITE_KEY => "   \t"] + self::ENABLED;
        $this->assertFalse($this->config($values)->isEnabledFor('contact', 6));
    }

    public function testSecretIsDecrypted(): void
    {
        $this->assertSame('plain-secret', $this->config(self::ENABLED)->getSecretKey(6));
    }

    public function testTimeoutDefaultsWhenOutOfRange(): void
    {
        $this->assertSame(5, $this->config([Config::XML_PATH_TIMEOUT => '0'])->getTimeout());
        $this->assertSame(5, $this->config([Config::XML_PATH_TIMEOUT => '31'])->getTimeout());
        $this->assertSame(10, $this->config([Config::XML_PATH_TIMEOUT => '10'])->getTimeout());
    }

    public function testFailureModeDefaultsToClosed(): void
    {
        $this->assertSame('closed', $this->config([])->getFailureMode());
        $this->assertSame('closed', $this->config([Config::XML_PATH_FAILURE_MODE => 'bogus'])->getFailureMode());
        $this->assertSame('open', $this->config([Config::XML_PATH_FAILURE_MODE => 'open'])->getFailureMode());
    }

    public function testThemeFallsBackToAuto(): void
    {
        $this->assertSame('auto', $this->config([Config::XML_PATH_THEME => 'neon'])->getTheme());
        $this->assertSame('dark', $this->config([Config::XML_PATH_THEME => 'dark'])->getTheme());
    }
}
```

- [ ] **Step 5: Run the test to verify it fails**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: errors `Class "AlpineCommerce\Turnstile\Model\Config" not found`.

- [ ] **Step 6: Implement `Model/Config.php`**

```php
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

    public function getFailureMode(?int $storeId = null): string
    {
        return $this->getString(self::XML_PATH_FAILURE_MODE, $storeId) === FailureMode::OPEN
            ? FailureMode::OPEN
            : FailureMode::CLOSED;
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
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: `OK (10 tests, …)`.

- [ ] **Step 8: Lint the XML files**

Run: `for f in src/app/code/AlpineCommerce/Turnstile/etc/*.xml src/app/code/AlpineCommerce/Turnstile/etc/adminhtml/*.xml; do xmllint --noout "$f" || exit 1; done && echo XML-OK`
Expected: `XML-OK`.

- [ ] **Step 9: Commit**

```bash
git add src/app/code/AlpineCommerce/Turnstile
git commit -m "feat(turnstile): add module skeleton and per-store configuration"
```

---

### Task 2: `SiteVerifyClient`

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/Exception/SiteVerifyUnavailableException.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/SiteVerifyClient.php`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/SiteVerifyClientTest.php`

**Interfaces:**
- Produces:
  - `SiteVerifyUnavailableException extends \RuntimeException`, `getHttpStatus(): ?int`
  - `SiteVerifyClient::DEFAULT_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify'`
  - `SiteVerifyClient::verify(string $secret, string $token, ?string $remoteIp, int $timeout): array` — decoded JSON containing at least `success`; throws `SiteVerifyUnavailableException` otherwise.

- [ ] **Step 1: Write the failing test**

`Test/Unit/Model/SiteVerifyClientTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\SiteVerifyClient;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SiteVerifyClientTest extends TestCase
{
    private const URL = 'https://verify.test/siteverify';

    private Curl&MockObject $curl;
    private SiteVerifyClient $client;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $factory = $this->createMock(ClientFactory::class);
        $factory->method('create')->willReturn($this->curl);
        $this->client = new SiteVerifyClient($factory, new Json(), self::URL);
    }

    public function testPostsFieldsAndReturnsDecodedBody(): void
    {
        $this->curl->expects($this->once())->method('setTimeout')->with(7);
        $this->curl->expects($this->once())->method('post')->with(
            self::URL,
            ['secret' => 's3cr3t', 'response' => 'tok', 'remoteip' => '203.0.113.5']
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"success":true,"action":"contact"}');

        $this->assertSame(
            ['success' => true, 'action' => 'contact'],
            $this->client->verify('s3cr3t', 'tok', '203.0.113.5', 7)
        );
    }

    public function testRemoteIpOmittedWhenNull(): void
    {
        $this->curl->expects($this->once())->method('post')->with(
            self::URL,
            ['secret' => 's3cr3t', 'response' => 'tok']
        );
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"success":false}');

        $this->client->verify('s3cr3t', 'tok', null, 5);
    }

    public function testTransportErrorThrowsWithoutLeakingSecret(): void
    {
        $this->curl->method('post')->willThrowException(new \Exception('Connection refused'));

        try {
            $this->client->verify('s3cr3t', 'tok', null, 5);
            $this->fail('Exception expected');
        } catch (SiteVerifyUnavailableException $e) {
            $this->assertStringNotContainsString('s3cr3t', $e->getMessage());
            $this->assertStringNotContainsString('tok', $e->getMessage());
            $this->assertNull($e->getHttpStatus());
        }
    }

    public function testNon200Throws(): void
    {
        $this->curl->method('getStatus')->willReturn(503);
        $this->curl->method('getBody')->willReturn('Service Unavailable');

        try {
            $this->client->verify('s3cr3t', 'tok', null, 5);
            $this->fail('Exception expected');
        } catch (SiteVerifyUnavailableException $e) {
            $this->assertSame(503, $e->getHttpStatus());
        }
    }

    public function testInvalidJsonThrows(): void
    {
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('<html>');

        $this->expectException(SiteVerifyUnavailableException::class);
        $this->client->verify('s3cr3t', 'tok', null, 5);
    }

    public function testJsonWithoutSuccessThrows(): void
    {
        $this->curl->method('getStatus')->willReturn(200);
        $this->curl->method('getBody')->willReturn('{"foo":1}');

        $this->expectException(SiteVerifyUnavailableException::class);
        $this->client->verify('s3cr3t', 'tok', null, 5);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit/Model/SiteVerifyClientTest.php`
Expected: errors `Class "AlpineCommerce\Turnstile\Model\SiteVerifyClient" not found`.

- [ ] **Step 3: Implement the exception and the client**

`Exception/SiteVerifyUnavailableException.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Exception;

/**
 * Siteverify could not be reached or returned an unusable answer.
 * Messages never contain the secret or the token.
 */
class SiteVerifyUnavailableException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly ?int $httpStatus = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }
}
```

`Model/SiteVerifyClient.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use Magento\Framework\HTTP\ClientFactory;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * The only place that talks to Cloudflare Siteverify.
 */
class SiteVerifyClient
{
    public const DEFAULT_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly ClientFactory $clientFactory,
        private readonly Json $json,
        private readonly string $verifyUrl = self::DEFAULT_URL
    ) {
    }

    /**
     * @return array<string, mixed> Decoded Siteverify response (always contains "success")
     * @throws SiteVerifyUnavailableException
     */
    public function verify(string $secret, string $token, ?string $remoteIp, int $timeout): array
    {
        $params = ['secret' => $secret, 'response' => $token];
        if ($remoteIp !== null && $remoteIp !== '') {
            $params['remoteip'] = $remoteIp;
        }

        $client = $this->clientFactory->create();
        $client->setTimeout($timeout);

        try {
            $client->post($this->verifyUrl, $params);
        } catch (\Exception $e) {
            throw new SiteVerifyUnavailableException('Siteverify transport error: ' . $e->getMessage(), null, $e);
        }

        $status = (int) $client->getStatus();
        if ($status !== 200) {
            throw new SiteVerifyUnavailableException('Siteverify returned HTTP ' . $status, $status);
        }

        try {
            $data = $this->json->unserialize((string) $client->getBody());
        } catch (\InvalidArgumentException $e) {
            throw new SiteVerifyUnavailableException('Siteverify returned invalid JSON', $status, $e);
        }

        if (!is_array($data) || !array_key_exists('success', $data)) {
            throw new SiteVerifyUnavailableException('Siteverify response has no "success" field', $status);
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: `OK (16 tests, …)`.

- [ ] **Step 5: Commit**

```bash
git add src/app/code/AlpineCommerce/Turnstile/Exception src/app/code/AlpineCommerce/Turnstile/Model/SiteVerifyClient.php src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/SiteVerifyClientTest.php
git commit -m "feat(turnstile): add Siteverify HTTP client"
```

---

### Task 3: `Validator`, `ValidationResult`, public interface and `di.xml`

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/ValidationResult.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Api/ValidatorInterface.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/Validator.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/di.xml`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/ValidatorTest.php`

**Interfaces:**
- Consumes: `Config::getSecretKey()`, `Config::getTimeout()`, `Config::getFailureMode()`, `FailureMode::OPEN`, `SiteVerifyClient::verify()`, `SiteVerifyUnavailableException::getHttpStatus()`.
- Produces:
  - `ValidationResult::success(): self`, `ValidationResult::failure(string $errorType, array $errorCodes = []): self`
  - `ValidationResult::isValid(): bool`, `getErrorType(): string`, `getErrorCodes(): string[]`
  - constants `ValidationResult::ERROR_NONE|ERROR_USER|ERROR_CONFIG|ERROR_UNAVAILABLE` (`'none'|'user'|'config'|'unavailable'`)
  - `ValidatorInterface::validate(string $token, ?string $remoteIp, string $formId, ?int $storeId = null): ValidationResult`
  - Local error codes: `missing-input-response` (empty token), `token-too-long`, `action-mismatch`.
  - Virtual type logger `AlpineCommerce\Turnstile\Logger` → `var/log/turnstile.log`.

- [ ] **Step 1: Create `ValidationResult` and `ValidatorInterface`**

`Model/ValidationResult.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

/**
 * Immutable outcome of a Turnstile validation.
 */
final class ValidationResult
{
    public const ERROR_NONE = 'none';
    public const ERROR_USER = 'user';
    public const ERROR_CONFIG = 'config';
    public const ERROR_UNAVAILABLE = 'unavailable';

    /**
     * @param string[] $errorCodes
     */
    private function __construct(
        private readonly bool $valid,
        private readonly string $errorType,
        private readonly array $errorCodes
    ) {
    }

    public static function success(): self
    {
        return new self(true, self::ERROR_NONE, []);
    }

    /**
     * @param string[] $errorCodes
     */
    public static function failure(string $errorType, array $errorCodes = []): self
    {
        return new self(false, $errorType, array_values($errorCodes));
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * @return string[]
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }
}
```

`Api/ValidatorInterface.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Api;

use AlpineCommerce\Turnstile\Model\ValidationResult;

/**
 * Validates a Turnstile token server-side for a given form and store.
 *
 * @api
 */
interface ValidatorInterface
{
    public function validate(string $token, ?string $remoteIp, string $formId, ?int $storeId = null): ValidationResult;
}
```

- [ ] **Step 2: Write the failing `ValidatorTest`**

`Test/Unit/Model/ValidatorTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\SiteVerifyClient;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use AlpineCommerce\Turnstile\Model\Validator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ValidatorTest extends TestCase
{
    private const TOKEN = 'XXXX.DUMMY.TOKEN.XXXX';
    private const SECRET = '1x0000000000000000000000000000000AA';

    private SiteVerifyClient&MockObject $client;
    private Config&MockObject $config;
    private AbstractLogger $logger;
    private Validator $validator;

    protected function setUp(): void
    {
        $this->client = $this->createMock(SiteVerifyClient::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('getSecretKey')->willReturn(self::SECRET);
        $this->config->method('getTimeout')->willReturn(5);
        $this->logger = new class extends AbstractLogger {
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
        $this->validator = new Validator($this->client, $this->config, $this->logger);
    }

    private function failureMode(string $mode): void
    {
        $this->config->method('getFailureMode')->willReturn($mode);
    }

    public function testSuccessWithMatchingAction(): void
    {
        $this->client->expects($this->once())->method('verify')
            ->with(self::SECRET, self::TOKEN, '203.0.113.5', 5)
            ->willReturn(['success' => true, 'action' => 'contact']);

        $this->assertTrue($this->validator->validate(self::TOKEN, '203.0.113.5', 'contact', 6)->isValid());
    }

    public function testEmptyTokenIsUserErrorWithoutHttpCall(): void
    {
        $this->client->expects($this->never())->method('verify');

        $result = $this->validator->validate('', null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
        $this->assertSame(['missing-input-response'], $result->getErrorCodes());
    }

    public function testOversizedTokenIsUserErrorWithoutHttpCall(): void
    {
        $this->client->expects($this->never())->method('verify');

        $result = $this->validator->validate(str_repeat('a', 2049), null, 'contact', 6);
        $this->assertSame(['token-too-long'], $result->getErrorCodes());
    }

    public function testInvalidTokenIsUserError(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertFalse($result->isValid());
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
    }

    public function testDuplicateTokenIsUserError(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['timeout-or-duplicate']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_USER, $result->getErrorType());
        $this->assertSame(['timeout-or-duplicate'], $result->getErrorCodes());
    }

    public function testActionMismatchIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => true, 'action' => 'newsletter']);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(['action-mismatch'], $result->getErrorCodes());
        $this->assertSame('warning', $this->logger->records[0]['level']);
    }

    public function testSuccessWithoutActionIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => true]);

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testNonBooleanSuccessIsRejected(): void
    {
        $this->client->method('verify')->willReturn(['success' => 'true', 'action' => 'contact']);

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
    }

    public function testInvalidSecretIsConfigErrorAndCritical(): void
    {
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['invalid-input-secret']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_CONFIG, $result->getErrorType());
        $this->assertSame('critical', $this->logger->records[0]['level']);
    }

    public function testInternalErrorClosedIsUnavailable(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willReturn(['success' => false, 'error-codes' => ['internal-error']]);

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_UNAVAILABLE, $result->getErrorType());
        $this->assertSame('error', $this->logger->records[0]['level']);
    }

    public function testTransportFailureClosedIsUnavailable(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('HTTP 503', 503));

        $result = $this->validator->validate(self::TOKEN, null, 'contact', 6);
        $this->assertSame(ValidationResult::ERROR_UNAVAILABLE, $result->getErrorType());
        $this->assertSame(503, $this->logger->records[0]['context']['http_status']);
    }

    public function testTransportFailureOpenIsAcceptedWithWarning(): void
    {
        $this->failureMode('open');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));

        $this->assertTrue($this->validator->validate(self::TOKEN, null, 'contact', 6)->isValid());
        $this->assertSame('warning', $this->logger->records[0]['level']);
    }

    public function testLogsNeverContainTokenOrSecret(): void
    {
        $this->failureMode('closed');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));
        $this->validator->validate(self::TOKEN, '203.0.113.5', 'contact', 6);

        $dump = json_encode($this->logger->records);
        $this->assertNotEmpty($this->logger->records);
        $this->assertStringNotContainsString(self::TOKEN, $dump);
        $this->assertStringNotContainsString(self::SECRET, $dump);
    }
}
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit/Model/ValidatorTest.php`
Expected: errors `Class "AlpineCommerce\Turnstile\Model\Validator" not found`.

- [ ] **Step 4: Implement `Model/Validator.php`**

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Exception\SiteVerifyUnavailableException;
use AlpineCommerce\Turnstile\Model\Config\Source\FailureMode;
use Psr\Log\LoggerInterface;

/**
 * Server-side Turnstile rules. Log records never contain the token or the secret.
 */
class Validator implements ValidatorInterface
{
    public const MAX_TOKEN_LENGTH = 2048;

    private const CONFIG_ERRORS = ['missing-input-secret', 'invalid-input-secret', 'bad-request'];
    private const UNAVAILABLE_ERRORS = ['internal-error'];

    public function __construct(
        private readonly SiteVerifyClient $client,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function validate(string $token, ?string $remoteIp, string $formId, ?int $storeId = null): ValidationResult
    {
        if ($token === '') {
            return ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']);
        }
        if (strlen($token) > self::MAX_TOKEN_LENGTH) {
            return ValidationResult::failure(ValidationResult::ERROR_USER, ['token-too-long']);
        }

        try {
            $response = $this->client->verify(
                $this->config->getSecretKey($storeId),
                $token,
                $remoteIp,
                $this->config->getTimeout($storeId)
            );
        } catch (SiteVerifyUnavailableException $e) {
            return $this->unavailable($formId, $storeId, [], $e->getHttpStatus(), $e->getMessage());
        }

        $errorCodes = array_values(array_filter((array) ($response['error-codes'] ?? []), 'is_string'));

        if (($response['success'] ?? null) === true) {
            if (($response['action'] ?? null) !== $formId) {
                $this->logger->warning('Turnstile action mismatch.', [
                    'form_id' => $formId,
                    'store_id' => $storeId,
                    'received_action' => is_string($response['action'] ?? null) ? $response['action'] : null,
                ]);
                return ValidationResult::failure(ValidationResult::ERROR_USER, ['action-mismatch']);
            }
            return ValidationResult::success();
        }

        if (array_intersect($errorCodes, self::CONFIG_ERRORS)) {
            $this->logger->critical('Turnstile configuration error.', [
                'form_id' => $formId,
                'store_id' => $storeId,
                'error_codes' => $errorCodes,
            ]);
            return ValidationResult::failure(ValidationResult::ERROR_CONFIG, $errorCodes);
        }

        if (array_intersect($errorCodes, self::UNAVAILABLE_ERRORS)) {
            return $this->unavailable($formId, $storeId, $errorCodes, 200, 'Siteverify internal error');
        }

        return ValidationResult::failure(ValidationResult::ERROR_USER, $errorCodes);
    }

    /**
     * @param string[] $errorCodes
     */
    private function unavailable(
        string $formId,
        ?int $storeId,
        array $errorCodes,
        ?int $httpStatus,
        string $reason
    ): ValidationResult {
        $context = [
            'form_id' => $formId,
            'store_id' => $storeId,
            'error_codes' => $errorCodes,
            'http_status' => $httpStatus,
            'reason' => $reason,
        ];

        if ($this->config->getFailureMode($storeId) === FailureMode::OPEN) {
            $this->logger->warning('Turnstile unavailable: request accepted (failure mode open).', $context);
            return ValidationResult::success();
        }

        $this->logger->error('Turnstile unavailable: request rejected (failure mode closed).', $context);
        return ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE, $errorCodes);
    }
}
```

- [ ] **Step 5: Create `etc/di.xml`**

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="AlpineCommerce\Turnstile\Api\ValidatorInterface" type="AlpineCommerce\Turnstile\Model\Validator"/>

    <virtualType name="AlpineCommerce\Turnstile\Logger\Handler" type="Magento\Framework\Logger\Handler\Base">
        <arguments>
            <argument name="fileName" xsi:type="string">/var/log/turnstile.log</argument>
        </arguments>
    </virtualType>
    <virtualType name="AlpineCommerce\Turnstile\Logger" type="Magento\Framework\Logger\Monolog">
        <arguments>
            <argument name="name" xsi:type="string">turnstile</argument>
            <argument name="handlers" xsi:type="array">
                <item name="turnstile" xsi:type="object">AlpineCommerce\Turnstile\Logger\Handler</item>
            </argument>
        </arguments>
    </virtualType>

    <type name="AlpineCommerce\Turnstile\Model\Validator">
        <arguments>
            <argument name="logger" xsi:type="object">AlpineCommerce\Turnstile\Logger</argument>
        </arguments>
    </type>
    <type name="AlpineCommerce\Turnstile\Model\Config">
        <arguments>
            <argument name="logger" xsi:type="object">AlpineCommerce\Turnstile\Logger</argument>
        </arguments>
    </type>
    <type name="AlpineCommerce\Turnstile\Model\SiteVerifyClient">
        <arguments>
            <argument name="verifyUrl" xsi:type="string">https://challenges.cloudflare.com/turnstile/v0/siteverify</argument>
        </arguments>
    </type>

    <type name="Magento\Config\Model\Config\TypePool">
        <arguments>
            <argument name="sensitive" xsi:type="array">
                <item name="alpinecommerce_turnstile/general/secret_key" xsi:type="string">1</item>
            </argument>
        </arguments>
    </type>
</config>
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: `OK (29 tests, …)`.

- [ ] **Step 7: Lint and commit**

```bash
xmllint --noout src/app/code/AlpineCommerce/Turnstile/etc/di.xml
git add src/app/code/AlpineCommerce/Turnstile/Api src/app/code/AlpineCommerce/Turnstile/Model/ValidationResult.php src/app/code/AlpineCommerce/Turnstile/Model/Validator.php src/app/code/AlpineCommerce/Turnstile/etc/di.xml src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/ValidatorTest.php
git commit -m "feat(turnstile): add server-side validator with closed/open failure modes"
```

---

### Task 4: `FormGuard` and Contact Us observer

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/FormGuard.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/Observer/ContactFormObserver.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/frontend/events.xml`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/FormGuardTest.php`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/Observer/ContactFormObserverTest.php`

**Interfaces:**
- Consumes: `ValidatorInterface::validate()`, `ValidationResult::*`, `Config::isEnabledFor()`.
- Produces:
  - `FormGuard::TOKEN_FIELD = 'cf-turnstile-response'`
  - `FormGuard::guard(string $formId, RequestInterface $request, HttpInterface $response, string $redirectUrl): bool`
  - `ContactFormObserver::FORM_ID = 'contact'`
  - Customer messages (exact English keys, reused in Task 6):
    - `Please complete the security check.`
    - `The security check failed. Please try again.`
    - `We could not verify your request. Please try again later.`
    - `The security check is temporarily unavailable. Please try again later.`

- [ ] **Step 1: Write the failing `FormGuardTest`**

`Test/Unit/Model/FormGuardTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Model\ValidationResult;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormGuardTest extends TestCase
{
    private ValidatorInterface&MockObject $validator;
    private Config&MockObject $config;
    private RemoteAddress&MockObject $remoteAddress;
    private ManagerInterface&MockObject $messageManager;
    private ActionFlag&MockObject $actionFlag;
    private RequestInterface&MockObject $request;
    private HttpInterface&MockObject $response;
    private FormGuard $guard;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->config = $this->createMock(Config::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->messageManager = $this->createMock(ManagerInterface::class);
        $this->actionFlag = $this->createMock(ActionFlag::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(HttpInterface::class);

        $this->guard = new FormGuard(
            $this->validator,
            $this->config,
            $storeManager,
            $this->remoteAddress,
            $this->messageManager,
            $this->actionFlag
        );
    }

    private function token(mixed $value): void
    {
        $this->request->method('getParam')->with(FormGuard::TOKEN_FIELD)->willReturn($value);
    }

    private function expectRejection(string $message): void
    {
        $this->messageManager->expects($this->once())->method('addErrorMessage')->with($message);
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);
        $this->response->expects($this->once())->method('setRedirect')->with('https://shop.test/contact/index/');
    }

    public function testDisabledFormPassesWithoutValidation(): void
    {
        $this->config->method('isEnabledFor')->with('contact', 6)->willReturn(false);
        $this->validator->expects($this->never())->method('validate');

        $this->assertTrue($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testValidTokenPasses(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(' tok ');
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.5');
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', '203.0.113.5', 'contact', 6)
            ->willReturn(ValidationResult::success());
        $this->messageManager->expects($this->never())->method('addErrorMessage');

        $this->assertTrue($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testMissingTokenIsRejectedWithCompleteMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(null);
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->expectRejection('Please complete the security check.');

        $this->assertFalse($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testArrayTokenIsTreatedAsEmpty(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(['x']);
        $this->validator->expects($this->once())->method('validate')
            ->with('', $this->anything(), 'contact', 6)
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));

        $this->assertFalse($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testMissingRemoteAddressPassesNull(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->remoteAddress->method('getRemoteAddress')->willReturn(false);
        $this->validator->expects($this->once())->method('validate')
            ->with('tok', null, 'contact', 6)
            ->willReturn(ValidationResult::success());

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testInvalidTokenMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['invalid-input-response']));
        $this->expectRejection('The security check failed. Please try again.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testConfigErrorMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_CONFIG, ['invalid-input-secret']));
        $this->expectRejection('We could not verify your request. Please try again later.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }

    public function testUnavailableMessage(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token('tok');
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_UNAVAILABLE));
        $this->expectRejection('The security check is temporarily unavailable. Please try again later.');

        $this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/');
    }
}
```

- [ ] **Step 2: Write the failing `ContactFormObserverTest`**

`Test/Unit/Observer/ContactFormObserverTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Observer;

use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Observer\ContactFormObserver;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContactFormObserverTest extends TestCase
{
    private FormGuard&MockObject $formGuard;
    private DataPersistorInterface&MockObject $dataPersistor;
    private RequestInterface&MockObject $request;
    private HttpInterface&MockObject $response;
    private ContactFormObserver $observer;

    protected function setUp(): void
    {
        $this->formGuard = $this->createMock(FormGuard::class);
        $this->dataPersistor = $this->createMock(DataPersistorInterface::class);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->with('contact/index')->willReturn('https://shop.test/contact/index/');
        $this->request = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(HttpInterface::class);

        $this->observer = new ContactFormObserver($this->formGuard, $this->dataPersistor, $url);
    }

    private function eventFor(mixed $action): Observer
    {
        return new Observer(['event' => new Event(['controller_action' => $action])]);
    }

    private function action(): AbstractAction&MockObject
    {
        $action = $this->createMock(AbstractAction::class);
        $action->method('getRequest')->willReturn($this->request);
        $action->method('getResponse')->willReturn($this->response);

        return $action;
    }

    public function testIgnoresEventsWithoutController(): void
    {
        $this->formGuard->expects($this->never())->method('guard');

        $this->observer->execute($this->eventFor(null));
    }

    public function testPassingRequestLeavesDataPersistorAlone(): void
    {
        $this->formGuard->expects($this->once())->method('guard')
            ->with('contact', $this->request, $this->response, 'https://shop.test/contact/index/')
            ->willReturn(true);
        $this->dataPersistor->expects($this->never())->method('set');

        $this->observer->execute($this->eventFor($this->action()));
    }

    public function testFailureStoresParamsWithoutToken(): void
    {
        $this->formGuard->method('guard')->willReturn(false);
        $this->request->method('getParams')->willReturn([
            'name' => 'Jeanne',
            'comment' => 'Bonjour',
            FormGuard::TOKEN_FIELD => 'XXXX.DUMMY.TOKEN.XXXX',
        ]);
        $this->dataPersistor->expects($this->once())->method('set')
            ->with('contact_us', ['name' => 'Jeanne', 'comment' => 'Bonjour']);

        $this->observer->execute($this->eventFor($this->action()));
    }
}
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: errors `Class "AlpineCommerce\Turnstile\Model\FormGuard" not found` and `…\Observer\ContactFormObserver" not found`.

- [ ] **Step 4: Implement `Model/FormGuard.php`**

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use AlpineCommerce\Turnstile\Api\ValidatorInterface;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Reusable guard: validates the Turnstile token of a POST request for a form id.
 * On failure it adds an error message, stops the controller and redirects.
 */
class FormGuard
{
    public const TOKEN_FIELD = 'cf-turnstile-response';

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly RemoteAddress $remoteAddress,
        private readonly ManagerInterface $messageManager,
        private readonly ActionFlag $actionFlag
    ) {
    }

    /**
     * @return bool true when the request may continue to the controller
     */
    public function guard(
        string $formId,
        RequestInterface $request,
        HttpInterface $response,
        string $redirectUrl
    ): bool {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isEnabledFor($formId, $storeId)) {
            return true;
        }

        $rawToken = $request->getParam(self::TOKEN_FIELD);
        $token = is_string($rawToken) ? trim($rawToken) : '';
        $remoteIp = $this->remoteAddress->getRemoteAddress();

        $result = $this->validator->validate(
            $token,
            is_string($remoteIp) && $remoteIp !== '' ? $remoteIp : null,
            $formId,
            $storeId
        );
        if ($result->isValid()) {
            return true;
        }

        $this->messageManager->addErrorMessage((string) $this->messageFor($result));
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        $response->setRedirect($redirectUrl);

        return false;
    }

    private function messageFor(ValidationResult $result): Phrase
    {
        return match ($result->getErrorType()) {
            ValidationResult::ERROR_CONFIG => __('We could not verify your request. Please try again later.'),
            ValidationResult::ERROR_UNAVAILABLE => __('The security check is temporarily unavailable. Please try again later.'),
            default => in_array('missing-input-response', $result->getErrorCodes(), true)
                ? __('Please complete the security check.')
                : __('The security check failed. Please try again.'),
        };
    }
}
```

- [ ] **Step 5: Implement `Observer/ContactFormObserver.php` and `etc/frontend/events.xml`**

`Observer/ContactFormObserver.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Observer;

use AlpineCommerce\Turnstile\Model\FormGuard;
use Magento\Framework\App\Action\AbstractAction;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

/**
 * Runs before contact/index/post: no e-mail is sent when Turnstile fails.
 */
class ContactFormObserver implements ObserverInterface
{
    public const FORM_ID = 'contact';

    public function __construct(
        private readonly FormGuard $formGuard,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly UrlInterface $url
    ) {
    }

    public function execute(Observer $observer): void
    {
        $action = $observer->getEvent()->getData('controller_action');
        if (!$action instanceof AbstractAction) {
            return;
        }
        $response = $action->getResponse();
        if (!$response instanceof HttpInterface) {
            return;
        }

        $request = $action->getRequest();
        $passed = $this->formGuard->guard(self::FORM_ID, $request, $response, $this->url->getUrl('contact/index'));

        if (!$passed) {
            // Keep the customer's input for the pre-filled form, never the token.
            $params = $request->getParams();
            unset($params[FormGuard::TOKEN_FIELD]);
            $this->dataPersistor->set('contact_us', $params);
        }
    }
}
```

`etc/frontend/events.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
    <event name="controller_action_predispatch_contact_index_post">
        <observer name="alpinecommerce_turnstile_contact" instance="AlpineCommerce\Turnstile\Observer\ContactFormObserver"/>
    </event>
</config>
```

- [ ] **Step 6: Run the tests to verify they pass**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: `OK (40 tests, …)`.

- [ ] **Step 7: Lint and commit**

```bash
xmllint --noout src/app/code/AlpineCommerce/Turnstile/etc/frontend/events.xml
git add src/app/code/AlpineCommerce/Turnstile/Model/FormGuard.php src/app/code/AlpineCommerce/Turnstile/Observer src/app/code/AlpineCommerce/Turnstile/etc/frontend src/app/code/AlpineCommerce/Turnstile/Test/Unit/Model/FormGuardTest.php src/app/code/AlpineCommerce/Turnstile/Test/Unit/Observer
git commit -m "feat(turnstile): guard contact form submissions server-side"
```

---

### Task 5: Frontend widget (view model, layout, template, CSP)

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/ViewModel/Widget.php`
- Create: `src/app/code/AlpineCommerce/Turnstile/view/frontend/layout/contact_index_index.xml`
- Create: `src/app/code/AlpineCommerce/Turnstile/view/frontend/templates/widget.phtml`
- Create: `src/app/code/AlpineCommerce/Turnstile/etc/csp_whitelist.xml`
- Test: `src/app/code/AlpineCommerce/Turnstile/Test/Unit/ViewModel/WidgetTest.php`

**Interfaces:**
- Consumes: `Config::isEnabledFor()`, `getSiteKey()`, `getTheme()`; `FormGuard::TOKEN_FIELD`.
- Produces: `Widget::isEnabled(string $formId): bool`, `getSiteKey(): string`, `getTheme(): string`, `getLanguage(): string`, `getScriptUrl(): string`; block `turnstile.contact` with arguments `form_id`, `view_model`.

- [ ] **Step 1: Write the failing `WidgetTest`**

`Test/Unit/ViewModel/WidgetTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\ViewModel;

use AlpineCommerce\Turnstile\Model\Config;
use AlpineCommerce\Turnstile\ViewModel\Widget;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WidgetTest extends TestCase
{
    private Config&MockObject $config;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
    }

    private function widget(string $locale): Widget
    {
        $resolver = $this->createMock(ResolverInterface::class);
        $resolver->method('getLocale')->willReturn($locale);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(6);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        return new Widget($this->config, $resolver, $storeManager);
    }

    public static function locales(): array
    {
        return [
            'french' => ['fr_FR', 'fr'],
            'swiss german' => ['de_CH', 'de'],
            'portuguese' => ['pt_PT', 'pt'],
            'swedish' => ['sv_SE', 'sv'],
            'british' => ['en_GB', 'en'],
            'unsupported' => ['xx_YY', 'auto'],
            'empty' => ['', 'auto'],
        ];
    }

    #[DataProvider('locales')]
    public function testLanguageFromStoreLocale(string $locale, string $expected): void
    {
        $this->assertSame($expected, $this->widget($locale)->getLanguage());
    }

    public function testDelegatesToConfigWithCurrentStore(): void
    {
        $this->config->expects($this->once())->method('isEnabledFor')->with('contact', 6)->willReturn(true);
        $this->config->method('getSiteKey')->with(6)->willReturn('site-key');
        $this->config->method('getTheme')->with(6)->willReturn('dark');
        $widget = $this->widget('fr_FR');

        $this->assertTrue($widget->isEnabled('contact'));
        $this->assertSame('site-key', $widget->getSiteKey());
        $this->assertSame('dark', $widget->getTheme());
        $this->assertSame('https://challenges.cloudflare.com/turnstile/v0/api.js', $widget->getScriptUrl());
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit/ViewModel/WidgetTest.php`
Expected: errors `Class "AlpineCommerce\Turnstile\ViewModel\Widget" not found`.

- [ ] **Step 3: Implement `ViewModel/Widget.php`**

```php
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
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`
Expected: `OK (48 tests, …)`.

- [ ] **Step 5: Create the layout, template and CSP whitelist**

`view/frontend/layout/contact_index_index.xml` (`form.additional.info` is declared only once, inside `contactForm`, by `Magento_Contact`):

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="form.additional.info">
            <block class="Magento\Framework\View\Element\Template"
                   name="turnstile.contact"
                   template="AlpineCommerce_Turnstile::widget.phtml">
                <arguments>
                    <argument name="form_id" xsi:type="string">contact</argument>
                    <argument name="view_model" xsi:type="object">AlpineCommerce\Turnstile\ViewModel\Widget</argument>
                </arguments>
            </block>
        </referenceContainer>
    </body>
</page>
```

`view/frontend/templates/widget.phtml`:

```php
<?php
declare(strict_types=1);

use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\ViewModel\Widget;
use Hyva\Theme\ViewModel\HyvaCsp;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template;

/** @var Template $block */
/** @var Escaper $escaper */
/** @var HyvaCsp $hyvaCsp */

/** @var Widget|null $widget */
$widget = $block->getData('view_model');
$formId = (string) $block->getData('form_id');

if (!$widget instanceof Widget || $formId === '' || !$widget->isEnabled($formId)) {
    return;
}

$elementId = 'turnstile-' . $formId;
?>
<div class="field turnstile mt-4">
    <div id="<?= $escaper->escapeHtmlAttr($elementId) ?>"
         class="cf-turnstile"
         data-sitekey="<?= $escaper->escapeHtmlAttr($widget->getSiteKey()) ?>"
         data-action="<?= $escaper->escapeHtmlAttr($formId) ?>"
         data-language="<?= $escaper->escapeHtmlAttr($widget->getLanguage()) ?>"
         data-theme="<?= $escaper->escapeHtmlAttr($widget->getTheme()) ?>"></div>
    <p id="<?= $escaper->escapeHtmlAttr($elementId) ?>-error" class="message error mt-2" role="alert" hidden>
        <?= $escaper->escapeHtml(__('Please complete the security check.')) ?>
    </p>
</div>
<script src="<?= $escaper->escapeUrl($widget->getScriptUrl()) ?>" async defer></script>
<script>
    (() => {
        const widget = document.getElementById('<?= $escaper->escapeJs($elementId) ?>');
        const form = widget ? widget.closest('form') : null;
        if (!form) {
            return;
        }
        const error = document.getElementById('<?= $escaper->escapeJs($elementId) ?>-error');
        // UX only: the server always validates the token.
        form.addEventListener('click', (event) => {
            if (!event.target.closest('button[type="submit"], input[type="submit"]')) {
                return;
            }
            const field = form.querySelector('input[name="<?= $escaper->escapeJs(FormGuard::TOKEN_FIELD) ?>"]');
            if (field && field.value) {
                error.hidden = true;
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            error.hidden = false;
        }, true);
    })();
</script>
<?php $hyvaCsp->registerInlineScript() ?>
```

`etc/csp_whitelist.xml`:

```xml
<?xml version="1.0"?>
<csp_whitelist xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
               xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Csp:etc/csp_whitelist.xsd">
    <policies>
        <policy id="script-src">
            <values>
                <value id="cloudflare-turnstile" type="host">https://challenges.cloudflare.com</value>
            </values>
        </policy>
        <policy id="frame-src">
            <values>
                <value id="cloudflare-turnstile" type="host">https://challenges.cloudflare.com</value>
            </values>
        </policy>
    </policies>
</csp_whitelist>
```

- [ ] **Step 6: Lint PHP template and XML**

Run:
```bash
set -e
docker exec -u www-data magento2-php php -l app/code/AlpineCommerce/Turnstile/view/frontend/templates/widget.phtml
xmllint --noout src/app/code/AlpineCommerce/Turnstile/view/frontend/layout/contact_index_index.xml src/app/code/AlpineCommerce/Turnstile/etc/csp_whitelist.xml
echo FRONTEND-LINT-OK
```
Expected: `No syntax errors detected …` and `FRONTEND-LINT-OK`.

- [ ] **Step 7: Commit**

```bash
git add src/app/code/AlpineCommerce/Turnstile/ViewModel src/app/code/AlpineCommerce/Turnstile/view src/app/code/AlpineCommerce/Turnstile/etc/csp_whitelist.xml src/app/code/AlpineCommerce/Turnstile/Test/Unit/ViewModel
git commit -m "feat(turnstile): render widget in the Hyva contact form"
```

---

### Task 6: Translations (fr_FR, de_DE, es_ES, it_IT)

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/i18n/fr_FR.csv`, `de_DE.csv`, `es_ES.csv`, `it_IT.csv`

**Interfaces:**
- Consumes: the four customer message keys from Task 4 (exact strings).

- [ ] **Step 1: Confirm the keys exist in the module code**

Run: `docker exec -u www-data magento2-php bin/magento i18n:collect-phrases app/code/AlpineCommerce/Turnstile`
Expected: output contains the four customer keys (plus admin labels, which are not translated).

- [ ] **Step 2: Write the four CSVs with guarded checks**

Run (aborts before writing if any check fails; files must not exist yet):

```bash
python3 - <<'PY'
import csv, io, os, re, sys

base = 'src/app/code/AlpineCommerce/Turnstile/i18n'
keys = [
    'Please complete the security check.',
    'The security check failed. Please try again.',
    'We could not verify your request. Please try again later.',
    'The security check is temporarily unavailable. Please try again later.',
]
translations = {
    'fr_FR': [
        'Veuillez compléter la vérification de sécurité.',
        'La vérification de sécurité a échoué. Veuillez réessayer.',
        "Nous n'avons pas pu vérifier votre demande. Veuillez réessayer plus tard.",
        'La vérification de sécurité est temporairement indisponible. Veuillez réessayer plus tard.',
    ],
    'de_DE': [
        'Bitte schließen Sie die Sicherheitsüberprüfung ab.',
        'Die Sicherheitsüberprüfung ist fehlgeschlagen. Bitte versuchen Sie es erneut.',
        'Wir konnten Ihre Anfrage nicht überprüfen. Bitte versuchen Sie es später erneut.',
        'Die Sicherheitsüberprüfung ist vorübergehend nicht verfügbar. Bitte versuchen Sie es später erneut.',
    ],
    'es_ES': [
        'Completa la verificación de seguridad.',
        'La verificación de seguridad ha fallado. Inténtalo de nuevo.',
        'No hemos podido verificar tu solicitud. Inténtalo de nuevo más tarde.',
        'La verificación de seguridad no está disponible temporalmente. Inténtalo de nuevo más tarde.',
    ],
    'it_IT': [
        'Completa la verifica di sicurezza.',
        'La verifica di sicurezza non è riuscita. Riprova.',
        'Non siamo riusciti a verificare la tua richiesta. Riprova più tardi.',
        'La verifica di sicurezza è temporaneamente non disponibile. Riprova più tardi.',
    ],
}

placeholder = re.compile(r'%\d+|<[^>]+>')
for locale, values in translations.items():
    path = f'{base}/{locale}.csv'
    assert not os.path.exists(path), f'{path} already exists'
    assert len(values) == len(keys), locale
    assert len(set(keys)) == len(keys), 'duplicate key'
    for k, v in zip(keys, values):
        assert k != v, f'{locale}: key == value for {k!r}'
        assert placeholder.findall(k) == placeholder.findall(v), f'{locale}: placeholders differ for {k!r}'

os.makedirs(base, exist_ok=True)
for locale, values in translations.items():
    buf = io.StringIO()
    csv.writer(buf, quoting=csv.QUOTE_ALL, lineterminator='\n').writerows(zip(keys, values))
    with open(f'{base}/{locale}.csv', 'w', encoding='utf-8', newline='') as fh:
        fh.write(buf.getvalue())

for locale in translations:
    with open(f'{base}/{locale}.csv', encoding='utf-8', newline='') as fh:
        rows = list(csv.reader(fh))
    assert [r[0] for r in rows] == keys and all(len(r) == 2 for r in rows), locale
print('I18N-OK')
PY
```

Expected: `I18N-OK`.

- [ ] **Step 3: Commit**

```bash
git add src/app/code/AlpineCommerce/Turnstile/i18n
git commit -m "feat(turnstile): add fr/de/es/it translations for customer messages"
```

---

### Task 7: Enable the module and verify end-to-end with Cloudflare test keys

**Files:**
- Modify: `src/app/etc/config.php` (by `module:enable`)

Test keys (verified 2026-09-24, <https://developers.cloudflare.com/turnstile/troubleshooting/testing/>):
site `1x00000000000000000000AA` (passes, visible); secrets `1x0000000000000000000000000000000AA` (passes),
`2x0000000000000000000000000000000AA` (fails), `3x0000000000000000000000000000000AA` (token already spent).

Before each command that changes state, state it to the user, the files it touches and its blast radius, and get approval (project debugging workflow).

- [ ] **Step 1: Enable the module (touches `app/etc/config.php`, `setup_module` table, `generated/`)**

```bash
set -e
docker exec -u www-data magento2-php bin/magento module:enable AlpineCommerce_Turnstile
docker exec -u www-data magento2-php bin/magento setup:upgrade
docker exec -u www-data magento2-php bin/magento cache:clean
docker exec -u www-data magento2-php bin/magento module:status AlpineCommerce_Turnstile
curl -s -o /dev/null -w 'HTTP %{http_code}\n' http://localhost:8080/
```

Expected: `Module is enabled`, `HTTP 200`. Nothing is active yet (`enabled = 0`).

- [ ] **Step 2: Check isolation while disabled**

```bash
set -e
n=$(curl -s http://localhost:8080/french/contact/ | grep -c 'cf-turnstile' || true)
test "$n" -eq 0 || { echo "widget rendered while disabled"; exit 1; }
echo DISABLED-OK
```

Expected: `DISABLED-OK`.

- [ ] **Step 3: Configure the `french` store view with the passing test keys**

```bash
set -e
M="docker exec -u www-data magento2-php bin/magento"
$M config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/enabled 1
$M config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/site_key 1x00000000000000000000AA
$M config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/secret_key 1x0000000000000000000000000000000AA
$M cache:clean config full_page
```

Verify the secret is stored encrypted (read-only SQL):

```bash
P=$(grep -E '^MYSQL_PASSWORD=' .env | cut -d= -f2-)
docker exec -u mysql -e MYSQL_PWD="$P" magento2-mysql mysql --default-character-set=utf8mb4 -umagento magento2 -N -e \
  "SELECT scope, scope_id, LEFT(value, 6) FROM core_config_data WHERE path='alpinecommerce_turnstile/general/secret_key'"
```

Expected: one row `stores 6 0:3:…` (not the plain key).

- [ ] **Step 4: Widget markup, FPC and CSP**

```bash
set -e
curl -s http://localhost:8080/french/contact/ -o /tmp/fr.html
grep -o 'data-language="[a-z]*"' /tmp/fr.html
grep -c 'data-action="contact"' /tmp/fr.html
n=$(curl -s http://localhost:8080/contact/ | grep -c 'cf-turnstile' || true); test "$n" -eq 0 && echo DEFAULT-STORE-NO-WIDGET
curl -s -D - -o /dev/null http://localhost:8080/french/contact/ | grep -i -E 'x-magento-cache-debug|content-security-policy' | grep -o -E 'HIT|MISS|challenges.cloudflare.com' | sort -u
```

Expected: `data-language="fr"`, count `1`, `DEFAULT-STORE-NO-WIDGET`, `HIT` on the second request and `challenges.cloudflare.com` in the CSP header.
(Use the scratchpad directory instead of `/tmp` for `fr.html` when executing.)

- [ ] **Step 5: Browser scenarios (Claude in Chrome, `http://localhost:8080/french/contact/`)**

1. Widget visible in French, shows success automatically (test site key). Fill name/email/comment, submit → success message, or the controller's own mail error if mail is not configured in Docker; either proves the guard let the request through.
2. Reload before the widget finishes / remove the hidden field with devtools → inline error "Veuillez compléter la vérification de sécurité.", no submission.
3. Console: no CSP violation for `challenges.cloudflare.com`.

- [ ] **Step 6: Server-side rejection scenarios**

```bash
set -e
M="docker exec -u www-data magento2-php bin/magento"
# a) POST without token
J=$(mktemp); curl -s -c "$J" http://localhost:8080/french/contact/ -o /tmp/c.html
FK=$(grep -o 'name="form_key" type="hidden" value="[^"]*"' /tmp/c.html | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$J" -c "$J" -o /dev/null -w '%{http_code} %{redirect_url}\n' \
  --data-urlencode "form_key=$FK" --data-urlencode 'name=Test' --data-urlencode 'email=test@example.com' \
  --data-urlencode 'comment=Bonjour' http://localhost:8080/french/contact/index/post/
```

Expected: `302 …/french/contact/index/`, and after following the redirect the `mage-messages` cookie in `$J` (URL-decoded) contains "Veuillez compléter la vérification de sécurité.".

Then in the browser with each secret (set via `config:set … secret_key <value>` + `cache:clean config full_page`):
- b) `2x0000000000000000000000000000000AA` → "La vérification de sécurité a échoué. Veuillez réessayer.", fields still filled.
- c) `3x0000000000000000000000000000000AA` → same message (duplicate token).
- Restore `1x0000000000000000000000000000000AA` afterwards.

- [ ] **Step 7: Cloudflare unreachable (`closed`, then `open`)**

Needs root in the PHP container to edit `/etc/hosts` (bind-mounted by Docker; restore it afterwards). Ask the user before running.

```bash
set -e
docker exec -u root magento2-php sh -c 'cp /etc/hosts /tmp/hosts.turnstile.bak && echo "127.0.0.1 challenges.cloudflare.com" >> /etc/hosts'
```

- Browser submit (the widget still loads on the host browser) → "La vérification de sécurité est temporairement indisponible…", `var/log/turnstile.log` has an `ERROR` line with `http_status: null`.
- `config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/failure_mode open` + `cache:clean config` → submit passes, log has a `WARNING` line.
- Restore:

```bash
set -e
docker exec -u root magento2-php sh -c 'cat /tmp/hosts.turnstile.bak > /etc/hosts && rm /tmp/hosts.turnstile.bak'
docker exec -u www-data magento2-php bin/magento config:delete --scope=stores --scope-code=french alpinecommerce_turnstile/general/failure_mode
docker exec -u www-data magento2-php bin/magento cache:clean config
docker exec magento2-php getent hosts challenges.cloudflare.com
```

Expected: `getent` resolves to a public Cloudflare address again.

- [ ] **Step 8: Logs contain no token or secret; regressions**

```bash
set -e
n=$(grep -c -E 'DUMMY\.TOKEN|0000000000000000000000000000000AA' src/var/log/turnstile.log || true)
test "$n" -eq 0 && echo LOG-CLEAN
for u in /french/ /french/checkout/cart/ /contact/; do curl -s -o /dev/null -w "$u %{http_code}\n" "http://localhost:8080$u"; done
```

Expected: `LOG-CLEAN`; all pages `200`. Browser: add a product to the cart and open checkout on `french` — unchanged. `german`/`spanish`/`italian`: enable temporarily and confirm `data-language` `de`/`es`/`it`, then remove with `config:delete`.

- [ ] **Step 9: Decide the final dev configuration**

Ask the user whether to keep the test keys active on `french` in this dev environment or disable (`config:set … enabled 0`). Real keys are set later in the admin, per store.

- [ ] **Step 10: Commit**

```bash
git add src/app/etc/config.php
git diff --cached --stat   # must show only config.php (one added line for AlpineCommerce_Turnstile)
git commit -m "chore(turnstile): enable AlpineCommerce_Turnstile"
```

If `git diff --cached` shows unrelated changes in `config.php`, stop and ask the user.

---

### Task 8: Documentation and push

**Files:**
- Create: `docs/modules/TURNSTILE.md`
- Modify: `README.md` (module table, after the Hreflang row)
- Modify: `docs/README.md` (module index, after the Hreflang row)
- Modify: `docs/superpowers/specs/2026-09-24-turnstile-contact-design.md` (status → Implemented)

- [ ] **Step 1: Write `docs/modules/TURNSTILE.md`** following the `HREFLANG.md` layout:

```markdown
# AlpineCommerce_Turnstile Module — Cloudflare Turnstile

> **Status**: ✅ Stable (v1.0.0) — Contact Us form

## 1. Responsibility

Server-side verified Cloudflare Turnstile challenge on storefront forms. First form: **Contact Us**.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Widget** | Injected in `contactForm` → `form.additional.info` (no Hyvä template override) |
| **Server validation** | Predispatch observer on `contact/index/post`, Siteverify API, `action` check |
| **Per-store config** | Enable, site key, encrypted secret, theme, timeout, failure mode, per-form flag |
| **Failure mode** | `closed` (default): reject when Cloudflare is unreachable; `open`: accept and log |
| **Logging** | `var/log/turnstile.log`, never tokens or secrets |
| **i18n** | fr_FR, de_DE, es_ES, it_IT |

## 3. Configuration

Stores › Configuration › AlpineCommerce › Cloudflare Turnstile. Paths `alpinecommerce_turnstile/general/*`
and `alpinecommerce_turnstile/forms/contact`. Keys come from the Cloudflare dashboard (Turnstile → widget).
Do not enable Google reCAPTCHA on the same form.

## 4. Adding another form

1. Layout for the form's page: add a `Magento\Framework\View\Element\Template` block with template
   `AlpineCommerce_Turnstile::widget.phtml`, arguments `form_id` and `view_model`, inside the `<form>`.
2. Observer on `controller_action_predispatch_<route>_<controller>_<action>` calling
   `FormGuard::guard('<form_id>', …)`.
3. `system.xml` + `config.xml`: `alpinecommerce_turnstile/forms/<form_id>`.

## 5. Testing

Unit: `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/AlpineCommerce/Turnstile/Test/Unit`.
Manual: Cloudflare test keys (<https://developers.cloudflare.com/turnstile/troubleshooting/testing/>).

## 6. Design

`docs/superpowers/specs/2026-09-24-turnstile-contact-design.md`
```

- [ ] **Step 2: Add the index rows**

`README.md` (after the Hreflang row): `| Security | Turnstile | [TURNSTILE.md](docs/modules/TURNSTILE.md) | Complete |`
`docs/README.md` (after the Hreflang row): `| Turnstile | [TURNSTILE.md](modules/TURNSTILE.md) |`

- [ ] **Step 3: Mark the spec as implemented** — change `**Status:** Approved design, awaiting spec review` to `**Status:** Implemented`.

- [ ] **Step 4: Commit and push to `main`**

```bash
set -e
git add docs/modules/TURNSTILE.md README.md docs/README.md docs/superpowers/specs/2026-09-24-turnstile-contact-design.md docs/superpowers/plans/2026-09-24-turnstile-contact.md
git diff --cached --name-only
git commit -m "docs(turnstile): document the Turnstile module"
git fetch origin
git merge --ff-only origin/main
git push origin main
```

`README.md` / `docs/README.md` may already have unrelated local edits: check `git diff README.md docs/README.md` first and stage only the Turnstile rows (`git add -p` is interactive and not available — if other edits exist, stop and ask the user).
