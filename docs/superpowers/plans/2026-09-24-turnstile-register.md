# Turnstile on Create Account + generic form protection — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (native, chosen by the user). Steps use checkbox (`- [ ]`) syntax.

**Goal:** Protect Create Account with Turnstile and make every classic form protectable by configuration only (admin + DI), with no hard Hyvä dependency.

**Architecture:** A generic `FormPredispatchObserver` configured per form through `virtualType`s, pluggable "keep the input" strategies, POST-only token reading, per-form failure mode in the admin.

**Tech Stack:** Magento 2.4.8, PHP 8.3, PHPUnit 10.5, Hyvä 1.5.2 (optional), Docker.

**Spec:** `docs/superpowers/specs/2026-09-24-turnstile-register-design.md` (extends `…-turnstile-contact-design.md`)

## Global Constraints

- No change in `src/vendor/`; no Hyvä template override; `Magento_ReCaptcha*` untouched; no cart/checkout change.
- Secret only server-side, encrypted; never log token, secret, password or POST body.
- Everything configurable from the admin; module usable in any project (no hard Hyvä dependency).
- `bin/magento` / `composer` as `www-data`; SQL with `--default-character-set=utf8mb4`.
- Pre-checks abort before writing (`set -e`, explicit `exit 1`).
- Announce and wait for approval before any command that changes the site/environment.
- One local commit per task, limited to the task; check `git status` and `git diff --cached` before each commit; no push before the user's final validation.
- Test command (repo root): `docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --do-not-cache-result --no-extensions app/code/AlpineCommerce/Turnstile/Test/Unit`
- XML validation: Magento XSDs via `UrnResolver` (`xmllint` is not installed).

## Review Focus

1. **Password kept after a failed registration** → `CustomerSessionFormDataPersisterTest::testPasswordsAndTokenAreNeverKept` (Task 3).
2. **Token sent in the query string** (`?cf-turnstile-response=…`) must be ignored → `FormGuardTest::testQueryStringTokenIsIgnored` (Task 2).
3. **Response that is not HTTP** must still block the controller → `FormGuardTest::testNonHttpResponseStillBlocks` (Task 2).
4. **Event without `request`** → observer falls back to the injected request, never skips → `FormPredispatchObserverTest::testFallsBackToInjectedRequest` (Task 3).
5. **Invalid form id** (e.g. `customer.create`) must fail loudly, not silently disable protection → `ConfigTest::testInvalidFormIdThrows` (Task 1).

---

### Task 1: Per-form failure mode and form-id check

**Files:**
- Create: `src/app/code/AlpineCommerce/Turnstile/Model/Config/Source/FailureModeOverride.php`
- Modify: `Model/Config.php`, `Model/Validator.php`, `etc/adminhtml/system.xml`, `etc/config.xml`
- Test: `Test/Unit/Model/ConfigTest.php`, `Test/Unit/Model/ValidatorTest.php`

**Interfaces:**
- Produces: `Config::getFailureMode(?int $storeId = null, ?string $formId = null): string`, `Config::assertValidFormId(string $formId): void` (throws `\InvalidArgumentException`), `Config::XML_PATH_FORM_FAILURE_MODE_SUFFIX = '_failure_mode'`, `FailureModeOverride` options `''`, `closed`, `open`.

- [ ] **Step 1: Failing tests** — append to `ConfigTest`:

```php
    public function testFormFailureModeOverridesGeneral(): void
    {
        $config = $this->config([
            Config::XML_PATH_FAILURE_MODE => 'closed',
            Config::XML_PATH_FORM_PREFIX . 'customer_create' . Config::XML_PATH_FORM_FAILURE_MODE_SUFFIX => 'open',
        ]);
        $this->assertSame('open', $config->getFailureMode(6, 'customer_create'));
        $this->assertSame('closed', $config->getFailureMode(6, 'contact'));
    }

    public function testEmptyFormFailureModeUsesGeneral(): void
    {
        $config = $this->config([
            Config::XML_PATH_FAILURE_MODE => 'open',
            Config::XML_PATH_FORM_PREFIX . 'contact' . Config::XML_PATH_FORM_FAILURE_MODE_SUFFIX => '',
        ]);
        $this->assertSame('open', $config->getFailureMode(6, 'contact'));
    }

    public function testInvalidFormIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->config(self::ENABLED)->isEnabledFor('customer.create', 6);
    }
```

and to `ValidatorTest`:

```php
    public function testFailureModeIsResolvedForTheForm(): void
    {
        $this->config->expects($this->once())->method('getFailureMode')->with(6, 'customer_create')->willReturn('closed');
        $this->client->method('verify')->willThrowException(new SiteVerifyUnavailableException('timeout'));

        $this->assertFalse($this->validator->validate(self::TOKEN, null, 'customer_create', 6)->isValid());
    }
```

- [ ] **Step 2: Run** → 4 failures/errors (constant undefined, no exception, `with()` mismatch).
- [ ] **Step 3: Implement**

`Model/Config/Source/FailureModeOverride.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FailureModeOverride implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => '', 'label' => __('Use general setting')],
            ['value' => FailureMode::CLOSED, 'label' => __('Closed (reject the form)')],
            ['value' => FailureMode::OPEN, 'label' => __('Open (accept without verification)')],
        ];
    }
}
```

`Model/Config.php` — add constant, `assertValidFormId()`, call it first in `isEnabledFor()`, new `getFailureMode()`:

```php
    public const XML_PATH_FORM_FAILURE_MODE_SUFFIX = '_failure_mode';
    private const FORM_ID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    public function assertValidFormId(string $formId): void
    {
        if (!preg_match(self::FORM_ID_PATTERN, $formId)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid Turnstile form id "%s": 1-32 characters among A-Z a-z 0-9 _ -.', $formId)
            );
        }
    }

    public function getFailureMode(?int $storeId = null, ?string $formId = null): string
    {
        if ($formId !== null) {
            $this->assertValidFormId($formId);
            $override = $this->getString(
                self::XML_PATH_FORM_PREFIX . $formId . self::XML_PATH_FORM_FAILURE_MODE_SUFFIX,
                $storeId
            );
            if ($override === FailureMode::OPEN || $override === FailureMode::CLOSED) {
                return $override;
            }
        }

        return $this->getString(self::XML_PATH_FAILURE_MODE, $storeId) === FailureMode::OPEN
            ? FailureMode::OPEN
            : FailureMode::CLOSED;
    }
```

`Model/Validator.php` — in `unavailable()`: `$this->config->getFailureMode($storeId, $formId)`.

`etc/config.xml` — under `<forms>`: `<customer_create>1</customer_create>` (failure-mode overrides have no default = general).

`etc/adminhtml/system.xml` — group `forms`, after `contact`:

```xml
                <field id="contact_failure_mode" translate="label" type="select" sortOrder="15" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Contact Us — If Cloudflare Is Unavailable</label>
                    <source_model>AlpineCommerce\Turnstile\Model\Config\Source\FailureModeOverride</source_model>
                    <depends><field id="contact">1</field></depends>
                </field>
                <field id="customer_create" translate="label" type="select" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Create Account</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                </field>
                <field id="customer_create_failure_mode" translate="label" type="select" sortOrder="25" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Create Account — If Cloudflare Is Unavailable</label>
                    <source_model>AlpineCommerce\Turnstile\Model\Config\Source\FailureModeOverride</source_model>
                    <depends><field id="customer_create">1</field></depends>
                </field>
```

- [ ] **Step 4: Run tests** → all green; XSD check of `config.xml` and `system.xml`.
- [ ] **Step 5: Commit** `feat(turnstile): per-form failure mode and form id check`.

---

### Task 2: FormGuard — POST-only token, never fail open on response type

**Files:** Modify `Model/FormGuard.php`; Test `Test/Unit/Model/FormGuardTest.php`.

**Interfaces:** `FormGuard::guard(string $formId, RequestInterface $request, ResponseInterface $response, string $redirectUrl): bool` (response type widened).

- [ ] **Step 1: Tests** — in `FormGuardTest` switch the request mock to `Magento\Framework\App\Request\Http` and the token helper to `getPostValue`:

```php
    private Http&MockObject $request;   // use Magento\Framework\App\Request\Http;
    …
        $this->request = $this->createMock(Http::class);
    …
    private function token(mixed $value): void
    {
        $this->request->method('getPostValue')->with(FormGuard::TOKEN_FIELD)->willReturn($value);
    }
```

and add:

```php
    public function testQueryStringTokenIsIgnored(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->request->method('getParam')->willReturn('from-query');
        $this->request->method('getPostValue')->with(FormGuard::TOKEN_FIELD)->willReturn(null);
        $this->validator->expects($this->once())->method('validate')
            ->with('', $this->anything(), 'contact', 6)
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));

        $this->assertFalse($this->guard->guard('contact', $this->request, $this->response, 'https://shop.test/contact/index/'));
    }

    public function testNonHttpResponseStillBlocks(): void
    {
        $this->config->method('isEnabledFor')->willReturn(true);
        $this->token(null);
        $this->validator->method('validate')
            ->willReturn(ValidationResult::failure(ValidationResult::ERROR_USER, ['missing-input-response']));
        $this->actionFlag->expects($this->once())->method('set')->with('', 'no-dispatch', true);

        $response = $this->createMock(\Magento\Framework\App\ResponseInterface::class);
        $this->assertFalse($this->guard->guard('contact', $this->request, $response, 'https://shop.test/contact/index/'));
    }
```

- [ ] **Step 2: Run** → the two new tests fail (token read with `getParam`; type error on `ResponseInterface`).
- [ ] **Step 3: Implement** in `FormGuard`:

```php
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
…
    public function guard(string $formId, RequestInterface $request, ResponseInterface $response, string $redirectUrl): bool
    {
        …
        $rawToken = $request instanceof HttpRequest ? $request->getPostValue(self::TOKEN_FIELD) : null;
        $token = is_string($rawToken) ? trim($rawToken) : '';
        …
        $this->messageManager->addErrorMessage((string) $this->messageFor($result));
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);
        if ($response instanceof HttpInterface) {
            $response->setRedirect($redirectUrl);
        }

        return false;
    }
```

- [ ] **Step 4: Run** → green. **Step 5: Commit** `fix(turnstile): read the token from the POST body only`.

---

### Task 3: Generic observer and input-keeping strategies (contact migrated)

**Files:**
- Create: `Model/FormDataFilter.php`, `Model/FormDataPersister/FormDataPersisterInterface.php`, `Model/FormDataPersister/DataPersistorFormDataPersister.php`, `Model/FormDataPersister/CustomerSessionFormDataPersister.php`, `Observer/FormPredispatchObserver.php`, `etc/frontend/di.xml`
- Modify: `etc/frontend/events.xml`
- Delete: `Observer/ContactFormObserver.php`, `Test/Unit/Observer/ContactFormObserverTest.php`
- Test: `Test/Unit/Model/FormDataFilterTest.php`, `Test/Unit/Model/FormDataPersister/DataPersistorFormDataPersisterTest.php`, `Test/Unit/Model/FormDataPersister/CustomerSessionFormDataPersisterTest.php`, `Test/Unit/Observer/FormPredispatchObserverTest.php`

**Interfaces:**
- `FormDataFilter::filter(RequestInterface $request, array $excludedFields = []): array`
- `FormDataPersisterInterface::persist(RequestInterface $request): void`
- `DataPersistorFormDataPersister(DataPersistorInterface, FormDataFilter, string $key, array $excludedFields = [])`
- `CustomerSessionFormDataPersister(Magento\Customer\Model\Session, FormDataFilter, array $excludedFields = [])`
- `FormPredispatchObserver(FormGuard, FormDataPersisterInterface $persister, UrlInterface, RequestInterface, ResponseInterface, string $formId, string $redirectPath, array $redirectParams = [])`

- [ ] **Step 1: Tests**

`Test/Unit/Model/FormDataFilterTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model;

use AlpineCommerce\Turnstile\Model\FormDataFilter;
use AlpineCommerce\Turnstile\Model\FormGuard;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;

class FormDataFilterTest extends TestCase
{
    public function testKeepsPostBodyWithoutTokenAndExcludedFields(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->with()->willReturn([
            'firstname' => 'Jeanne',
            'password' => 'secret',
            FormGuard::TOKEN_FIELD => 'XXXX.DUMMY.TOKEN.XXXX',
        ]);

        $this->assertSame(['firstname' => 'Jeanne'], (new FormDataFilter())->filter($request, ['password']));
    }

    public function testNonHttpRequestGivesNothing(): void
    {
        $this->assertSame([], (new FormDataFilter())->filter($this->createMock(RequestInterface::class)));
    }
}
```

`Test/Unit/Model/FormDataPersister/DataPersistorFormDataPersisterTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataFilter;
use AlpineCommerce\Turnstile\Model\FormDataPersister\DataPersistorFormDataPersister;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class DataPersistorFormDataPersisterTest extends TestCase
{
    public function testStoresFilteredDataUnderKey(): void
    {
        $request = $this->createMock(Http::class);
        $filter = $this->createMock(FormDataFilter::class);
        $filter->expects($this->once())->method('filter')->with($request, ['x'])->willReturn(['name' => 'Jeanne']);
        $persistor = $this->createMock(DataPersistorInterface::class);
        $persistor->expects($this->once())->method('set')->with('contact_us', ['name' => 'Jeanne']);

        (new DataPersistorFormDataPersister($persistor, $filter, 'contact_us', ['x']))->persist($request);
    }
}
```

`Test/Unit/Model/FormDataPersister/CustomerSessionFormDataPersisterTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataFilter;
use AlpineCommerce\Turnstile\Model\FormDataPersister\CustomerSessionFormDataPersister;
use AlpineCommerce\Turnstile\Model\FormGuard;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\TestCase;

class CustomerSessionFormDataPersisterTest extends TestCase
{
    public function testPasswordsAndTokenAreNeverKept(): void
    {
        $request = $this->createMock(Http::class);
        $request->method('getPostValue')->with()->willReturn([
            'firstname' => 'Jeanne',
            'email' => 'jeanne@example.com',
            'password' => 'S3cret!',
            'password_confirmation' => 'S3cret!',
            FormGuard::TOKEN_FIELD => 'XXXX.DUMMY.TOKEN.XXXX',
        ]);
        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->onlyMethods(['__call'])->getMock();
        $session->expects($this->once())->method('__call')
            ->with('setCustomerFormData', [['firstname' => 'Jeanne', 'email' => 'jeanne@example.com']]);

        (new CustomerSessionFormDataPersister($session, new FormDataFilter(), ['password', 'password_confirmation']))
            ->persist($request);
    }
}
```

`Test/Unit/Observer/FormPredispatchObserverTest.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Test\Unit\Observer;

use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;
use AlpineCommerce\Turnstile\Model\FormGuard;
use AlpineCommerce\Turnstile\Observer\FormPredispatchObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FormPredispatchObserverTest extends TestCase
{
    private FormGuard&MockObject $formGuard;
    private FormDataPersisterInterface&MockObject $persister;
    private RequestInterface&MockObject $injectedRequest;
    private ResponseInterface&MockObject $response;
    private FormPredispatchObserver $observer;

    protected function setUp(): void
    {
        $this->formGuard = $this->createMock(FormGuard::class);
        $this->persister = $this->createMock(FormDataPersisterInterface::class);
        $url = $this->createMock(UrlInterface::class);
        $url->method('getUrl')->with('customer/account/create', ['_secure' => true])
            ->willReturn('https://shop.test/customer/account/create/');
        $this->injectedRequest = $this->createMock(RequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);

        $this->observer = new FormPredispatchObserver(
            $this->formGuard,
            $this->persister,
            $url,
            $this->injectedRequest,
            $this->response,
            'customer_create',
            'customer/account/create',
            ['_secure' => true]
        );
    }

    public function testUsesEventRequestAndConfiguredRedirect(): void
    {
        $eventRequest = $this->createMock(RequestInterface::class);
        $this->formGuard->expects($this->once())->method('guard')
            ->with('customer_create', $eventRequest, $this->response, 'https://shop.test/customer/account/create/')
            ->willReturn(true);
        $this->persister->expects($this->never())->method('persist');

        $this->observer->execute(new Observer(['event' => new Event(['request' => $eventRequest])]));
    }

    public function testFallsBackToInjectedRequest(): void
    {
        $this->formGuard->expects($this->once())->method('guard')
            ->with('customer_create', $this->injectedRequest, $this->anything(), $this->anything())
            ->willReturn(true);

        $this->observer->execute(new Observer(['event' => new Event([])]));
    }

    public function testFailureKeepsInput(): void
    {
        $eventRequest = $this->createMock(RequestInterface::class);
        $this->formGuard->method('guard')->willReturn(false);
        $this->persister->expects($this->once())->method('persist')->with($eventRequest);

        $this->observer->execute(new Observer(['event' => new Event(['request' => $eventRequest])]));
    }
}
```

- [ ] **Step 2: Run** → errors: classes not found.
- [ ] **Step 3: Implement**

`Model/FormDataFilter.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

/**
 * POST body without the Turnstile token and without the given fields (e.g. passwords).
 */
class FormDataFilter
{
    /**
     * @param string[] $excludedFields
     * @return array<string, mixed>
     */
    public function filter(RequestInterface $request, array $excludedFields = []): array
    {
        $data = $request instanceof HttpRequest ? $request->getPostValue() : [];
        if (!is_array($data)) {
            return [];
        }

        foreach (array_merge([FormGuard::TOKEN_FIELD], array_values($excludedFields)) as $field) {
            unset($data[$field]);
        }

        return $data;
    }
}
```

`Model/FormDataPersister/FormDataPersisterInterface.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use Magento\Framework\App\RequestInterface;

/**
 * Keeps the customer's input after a rejected submission so the form is shown pre-filled.
 *
 * @api
 */
interface FormDataPersisterInterface
{
    public function persist(RequestInterface $request): void;
}
```

`Model/FormDataPersister/DataPersistorFormDataPersister.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataFilter;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;

class DataPersistorFormDataPersister implements FormDataPersisterInterface
{
    /**
     * @param string[] $excludedFields
     */
    public function __construct(
        private readonly DataPersistorInterface $dataPersistor,
        private readonly FormDataFilter $filter,
        private readonly string $key,
        private readonly array $excludedFields = []
    ) {
    }

    public function persist(RequestInterface $request): void
    {
        $this->dataPersistor->set($this->key, $this->filter->filter($request, $this->excludedFields));
    }
}
```

`Model/FormDataPersister/CustomerSessionFormDataPersister.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Model\FormDataPersister;

use AlpineCommerce\Turnstile\Model\FormDataFilter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;

/**
 * Same storage as Magento\Customer\Controller\Account\CreatePost, read back by Register::getFormData().
 */
class CustomerSessionFormDataPersister implements FormDataPersisterInterface
{
    /**
     * @param string[] $excludedFields
     */
    public function __construct(
        private readonly Session $customerSession,
        private readonly FormDataFilter $filter,
        private readonly array $excludedFields = []
    ) {
    }

    public function persist(RequestInterface $request): void
    {
        $this->customerSession->setCustomerFormData($this->filter->filter($request, $this->excludedFields));
    }
}
```

`Observer/FormPredispatchObserver.php`:

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Turnstile\Observer;

use AlpineCommerce\Turnstile\Model\FormDataPersister\FormDataPersisterInterface;
use AlpineCommerce\Turnstile\Model\FormGuard;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\UrlInterface;

/**
 * Generic predispatch guard, configured per form with a virtualType in etc/frontend/di.xml.
 * Never skips the guard: the request falls back to the shared request, the response is the shared response.
 */
class FormPredispatchObserver implements ObserverInterface
{
    /**
     * @param array<string, mixed> $redirectParams
     */
    public function __construct(
        private readonly FormGuard $formGuard,
        private readonly FormDataPersisterInterface $persister,
        private readonly UrlInterface $url,
        private readonly RequestInterface $request,
        private readonly ResponseInterface $response,
        private readonly string $formId,
        private readonly string $redirectPath,
        private readonly array $redirectParams = []
    ) {
    }

    public function execute(Observer $observer): void
    {
        $request = $observer->getEvent()->getData('request');
        if (!$request instanceof RequestInterface) {
            $request = $this->request;
        }

        $passed = $this->formGuard->guard(
            $this->formId,
            $request,
            $this->response,
            $this->url->getUrl($this->redirectPath, $this->redirectParams)
        );

        if (!$passed) {
            $this->persister->persist($request);
        }
    }
}
```

`etc/frontend/di.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <!-- Contact Us -->
    <virtualType name="AlpineCommerce\Turnstile\Model\FormDataPersister\Contact"
                 type="AlpineCommerce\Turnstile\Model\FormDataPersister\DataPersistorFormDataPersister">
        <arguments>
            <argument name="key" xsi:type="string">contact_us</argument>
        </arguments>
    </virtualType>
    <virtualType name="AlpineCommerce\Turnstile\Observer\ContactForm"
                 type="AlpineCommerce\Turnstile\Observer\FormPredispatchObserver">
        <arguments>
            <argument name="persister" xsi:type="object">AlpineCommerce\Turnstile\Model\FormDataPersister\Contact</argument>
            <argument name="formId" xsi:type="string">contact</argument>
            <argument name="redirectPath" xsi:type="string">contact/index</argument>
        </arguments>
    </virtualType>

    <!-- Create Account -->
    <virtualType name="AlpineCommerce\Turnstile\Model\FormDataPersister\CustomerCreate"
                 type="AlpineCommerce\Turnstile\Model\FormDataPersister\CustomerSessionFormDataPersister">
        <arguments>
            <argument name="excludedFields" xsi:type="array">
                <item name="password" xsi:type="string">password</item>
                <item name="password_confirmation" xsi:type="string">password_confirmation</item>
            </argument>
        </arguments>
    </virtualType>
    <virtualType name="AlpineCommerce\Turnstile\Observer\CustomerCreate"
                 type="AlpineCommerce\Turnstile\Observer\FormPredispatchObserver">
        <arguments>
            <argument name="persister" xsi:type="object">AlpineCommerce\Turnstile\Model\FormDataPersister\CustomerCreate</argument>
            <argument name="formId" xsi:type="string">customer_create</argument>
            <argument name="redirectPath" xsi:type="string">customer/account/create</argument>
            <argument name="redirectParams" xsi:type="array">
                <item name="_secure" xsi:type="boolean">true</item>
            </argument>
        </arguments>
    </virtualType>
</config>
```

`etc/frontend/events.xml` — contact observer instance becomes `AlpineCommerce\Turnstile\Observer\ContactForm` (Create Account event is added in Task 4). Delete `Observer/ContactFormObserver.php` and its test (`git rm`).

- [ ] **Step 4: Run tests** → green; XSD check of `frontend/di.xml` and `frontend/events.xml`.
- [ ] **Step 5: Commit** `refactor(turnstile): generic predispatch observer configured per form`.

---

### Task 4: Create Account wiring

**Files:** Create `view/frontend/layout/customer_account_create.xml`; Modify `etc/frontend/events.xml`.

- [ ] **Step 1:** layout (same block as contact, `name="turnstile.customer_create"`, `form_id = customer_create`) inside `<referenceContainer name="form.additional.info">`.
- [ ] **Step 2:** `events.xml`:

```xml
    <event name="controller_action_predispatch_customer_account_createpost">
        <observer name="alpinecommerce_turnstile_customer_create" instance="AlpineCommerce\Turnstile\Observer\CustomerCreate"/>
    </event>
```

- [ ] **Step 3:** XSD check (layout + events), full test suite green.
- [ ] **Step 4: Commit** `feat(turnstile): protect Create Account`.

---

### Task 5: Portability — no hard Hyvä dependency

**Files:** Modify `etc/module.xml`, `composer.json`.

- [ ] `module.xml` sequence: `Magento_Store`, `Magento_Config`, `Magento_Contact`, `Magento_Customer` (remove `Hyva_Theme`).
- [ ] `composer.json`: add `"magento/module-customer": "*"`; move Hyvä to `"suggest": {"hyva-themes/magento2-theme-module": "Registers the inline guard script with Hyvä CSP"}`.
- [ ] Check `widget.phtml` has no other Hyvä-only call (`$hyvaCsp` guarded by `isset`). XSD check, suite green.
- [ ] **Commit** `chore(turnstile): make Hyva optional`.

---

### Task 6: Deploy and end-to-end checks (each state change announced, approval required)

- [ ] `cache:clean config layout full_page` (no `setup:upgrade`: no schema, and it would empty `pub/static`).
- [ ] Read-only: `/french/customer/account/create/` has one widget inside the form with `data-action="customer_create"`, `data-language="fr"`; default store has none; contact page still has its widget.
- [ ] POST `customer/account/createpost` without token → 302 to `customer/account/create`, message "Veuillez compléter…", firstname kept, password field empty.
- [ ] Failing secret `2x…` (then restore `1x…`) → "La vérification de sécurité a échoué…", input kept without password.
- [ ] Valid POST with dummy token → account created with e-mail `turnstile.test+<timestamp>@example.com`; **ask approval** to delete this test customer afterwards (guarded SQL by e-mail, count must be 1).
- [ ] Contact regression: valid + missing token.
- [ ] Per-form failure mode: admin fields render (field-render script), `config:set forms/customer_create_failure_mode open` + `/etc/hosts` outage test only if the user asks (general mode already verified).
- [ ] `turnstile.log`: no token/secret/password; cart, checkout, admin 200.

### Task 7: Documentation and review

- [ ] Update `docs/modules/TURNSTILE.md` (generic observer, "adding a form" = layout + events + di + admin, Create Account, per-form failure mode, Hyvä optional, deferred minors fixed).
- [ ] Spec status → Implemented. Commit docs.
- [ ] Independent whole-branch review (fresh reviewer), fix Critical/Important in TDD, then present for push approval.
