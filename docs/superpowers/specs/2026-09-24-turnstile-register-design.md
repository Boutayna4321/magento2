# Cloudflare Turnstile on Create Account + generic form protection — Design

- **Date:** 2026-09-24
- **Status:** Approved design (in chat), awaiting spec review
- **Module:** `AlpineCommerce_Turnstile` (extends `docs/superpowers/specs/2026-09-24-turnstile-contact-design.md`)
- **Goal set by the user:** everything configurable from the admin panel, module reusable in any Magento project.

## 1. Scope

In scope:

- Protect **Create Account** (`/customer/account/create`, POST `customer/account/createpost`).
- Replace the contact-specific observer with **one generic, DI-configured observer**, so a new classic
  (non-AJAX) form needs no new PHP class: layout + `events.xml` + a `di.xml` entry + an admin switch.
- **Per-form failure mode** in the admin (Use general setting / Closed / Open).
- Portability fixes: no hard dependency on Hyvä; token read from the POST body only; `form_id` format check;
  never fail open when the controller or response type is unexpected.

Out of scope: checkout / place order (postponed by the user), login, newsletter, AJAX forms,
the "missing key skips validation" behaviour (unchanged, still open question for the user).

## 2. Audit — Create Account (read-only, 2026-09-24)

| Point | Real code |
|---|---|
| Hyvä template | `Magento_Customer::form/register.phtml` renders `form_additional_info` (container `form.additional.info`, declared in Hyvä `customer_account_create.xml`) at line 241, **inside** `<form>`, before the submit button (line 432). Alpine submit → `$form.submit()` |
| Luma/blank | `Magento_Customer` `register.phtml` renders the same container (line 294) — layout injection works on both themes |
| Server entry point | `controller_action_predispatch_customer_account_createpost` (same event as `Magento_ReCaptchaCustomer`); failure redirect `customer/account/create` with `_secure => true` |
| Keeping input | `CreatePost` uses `customerSession->setCustomerFormData($request->getPostValue())`, read back by `Register::getFormData()` (`getCustomerFormData(true)`). Core also keeps the password — **we must not** |
| Page cache | Hyvä declares `customer_form_register` `cacheable="false"` |
| Fields | `password`, `password_confirmation` |

Verified facts used by the design: 19 core observers are declared as `virtualType` (observer instances may be
virtual types); `FrontController` and controllers share the same `ResponseInterface` instance
(`Response\Http`); `FrontController` dispatches predispatch events with `['controller_action', 'request']`.

## 3. Design

### 3.1 Generic observer

`Observer\FormPredispatchObserver` (replaces `Observer\ContactFormObserver`):

```
__construct(FormGuard, FormDataPersisterInterface $persister, UrlInterface, RequestInterface, ResponseInterface,
            string $formId, string $redirectPath, array $redirectParams = [])
execute(): request = event 'request' (fallback: injected request); guard(formId, request, injected response,
           url(redirectPath, redirectParams)); on failure → persister->persist(request)
```

It never skips the guard because of the controller type (fixes review minor 2). Each form is a `virtualType`
in `etc/frontend/di.xml`.

### 3.2 Keeping the customer's input

- `Model\FormDataFilter::filter(RequestInterface, string[] $excluded): array` — POST body only
  (`Http::getPostValue()`), removes `cf-turnstile-response` and the excluded fields.
- `Model\FormDataPersister\FormDataPersisterInterface::persist(RequestInterface): void`
- `DataPersistorFormDataPersister` (args `key`, `excludedFields`) — contact: key `contact_us`.
- `CustomerSessionFormDataPersister` (arg `excludedFields`) — register: excludes `password`,
  `password_confirmation`.

### 3.3 FormGuard changes

- Token read from the **POST body only** (`Http::getPostValue`), non-HTTP request → empty token (review minor 1).
- Accepts `ResponseInterface`; sets `FLAG_NO_DISPATCH` always on failure, redirects only if the response is
  `HttpInterface` (never fail open).

### 3.4 Configuration (admin)

Group **Protected Forms**, per form, all scopes:

| Path | Type | Default |
|---|---|---|
| `alpinecommerce_turnstile/forms/contact` | Yes/No | 1 |
| `alpinecommerce_turnstile/forms/contact_failure_mode` | Use general setting / Closed / Open | (general) |
| `alpinecommerce_turnstile/forms/customer_create` | Yes/No | 1 |
| `alpinecommerce_turnstile/forms/customer_create_failure_mode` | Use general setting / Closed / Open | (general) |

`Config::getFailureMode(?int $storeId, ?string $formId = null)`: form override if `closed`/`open`, else general.
`Validator` passes the form id. `Config::assertValidFormId()` throws `\InvalidArgumentException` for a form id
that is not `[A-Za-z0-9_-]{1,32}` (developer error, caught at development time; review minor 3).

Note: with `forms/customer_create = 1` by default, Create Account is protected as soon as Turnstile is enabled
for a store (same behaviour as contact). Admins untick it to exclude the form.

### 3.5 Create Account wiring

- `view/frontend/layout/customer_account_create.xml`: block `turnstile.customer_create` in
  `form.additional.info`, `form_id = customer_create`.
- `etc/frontend/events.xml`: `controller_action_predispatch_customer_account_createpost` →
  `AlpineCommerce\Turnstile\Observer\CustomerCreate` (virtualType).
- No new customer messages (the 4 existing messages and translations are reused).

### 3.6 Portability

- `module.xml`: sequence `Magento_Store`, `Magento_Config`, `Magento_Contact`, `Magento_Customer`
  (no `Hyva_Theme`). `composer.json`: `hyva-themes/magento2-theme-module` moves to `suggest`,
  `magento/module-customer` added. The template already works without `$hyvaCsp`.

## 4. Error handling

Unchanged from the contact design, with the per-form failure mode. Never logged: token, secret, password,
POST body.

## 5. Testing

Unit (TDD): `ConfigTest` (per-form override, invalid form id), `ValidatorTest` (form id passed to
failure mode), `FormGuardTest` (POST only, query token ignored, non-HTTP response still blocks),
`FormDataFilterTest`, both persister tests (password never kept), `FormPredispatchObserverTest`.
`ContactFormObserverTest` is replaced.

End-to-end on store view `french` with the official test keys: widget on `/french/customer/account/create/`;
POST without token / with failing secret → redirect to create, message, input kept **without password**;
valid POST → account created (test e-mail, removed afterwards with the user's approval); contact form still
works (valid + rejected); Luma template compatibility checked by layout/container inspection.

## 6. Files

Created: `Observer/FormPredispatchObserver.php`, `Model/FormDataFilter.php`,
`Model/FormDataPersister/{FormDataPersisterInterface,DataPersistorFormDataPersister,CustomerSessionFormDataPersister}.php`,
`Model/Config/Source/FailureModeOverride.php`, `etc/frontend/di.xml`,
`view/frontend/layout/customer_account_create.xml`, tests.
Modified: `Model/Config.php`, `Model/Validator.php`, `Model/FormGuard.php`, `etc/frontend/events.xml`,
`etc/adminhtml/system.xml`, `etc/config.xml`, `etc/module.xml`, `composer.json`, `docs/modules/TURNSTILE.md`.
Deleted: `Observer/ContactFormObserver.php`, `Test/Unit/Observer/ContactFormObserverTest.php`.
