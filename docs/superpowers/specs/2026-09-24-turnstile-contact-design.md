# Cloudflare Turnstile on the Contact Us form — Design

- **Date:** 2026-09-24
- **Status:** Implemented (2026-09-24)
- **Module:** `AlpineCommerce_Turnstile` (`src/app/code/AlpineCommerce/Turnstile`)
- **Platform:** Magento 2.4.8, Hyvä default theme 1.5.2, child theme `AlpineCommerce/hyva`

## 1. Goal and scope

Protect the storefront **Contact Us** form (`/contact`, POST `contact/index/post`) with
Cloudflare Turnstile. This is a project requirement, not a response to an active spam problem.

In scope:

- Turnstile widget on the Contact Us form, server-side validation of its token.
- Per-store configuration in the admin.
- Architecture that lets other forms be protected later by adding only a layout file,
  an observer and a config flag.

Out of scope (for now):

- Any other form (login, register, newsletter, reviews, checkout, ...).
- Hostname checking of the Siteverify response (see §11).
- Pre-clearance mode, invisible widget, idempotency keys / retries.

## 2. Non-negotiable principles

1. No modification in `vendor/`.
2. No override of the existing Hyvä template `Magento_Contact::form.phtml`.
3. `Magento_ReCaptchaContact` stays untouched.
4. Server-side validation is mandatory; the client-side check is UX only.
5. The secret key exists only server-side and is stored encrypted in configuration.
6. No token and no secret is ever written to a log.
7. Full Page Cache compatible: the rendered markup contains nothing user-specific.
8. No modification of the checkout.
9. Reusable architecture for adding other forms later.

## 3. Audit of the current flow

**Render (GET `/contact`)**

- `Magento_Contact` layout `contact_index_index.xml` declares block `contactForm`
  (`Magento\Contact\Block\ContactForm`) with an empty container `form.additional.info`.
- Hyvä template `vendor/hyva-themes/magento2-default-theme/Magento_Contact/templates/form.phtml`
  renders that container inside the `<form>` (line 94: `getChildHtml('form.additional.info')`).
  The child theme `AlpineCommerce/hyva` does not override this template.
- The same template also calls the optional view model `viewModelRecaptcha`
  (lines 95, 105, 116), injected by Hyvä's `Magento_ReCaptchaContact/layout/contact_index_index.xml`.
- Submission: Alpine intercepts the submit (`@submit.prevent="submitForm()"`), runs reCAPTCHA JS
  if present, then calls `$form.submit()` when `errors === 0`. `$form.submit()` does not fire
  a new `submit` event.

**Submit (POST `contact/index/post`)**

- Event `controller_action_predispatch_contact_index_post` runs
  `Magento\ReCaptchaContact\Observer\ContactFormObserver` before the controller. On failure,
  reCAPTCHA's `RequestHandler` adds an error message, sets `Action::FLAG_NO_DISPATCH` and
  redirects to `*/*/index`.
- `Magento\Contact\Controller\Index\Post` validates the fields and sends the e-mail. On error it
  stores the submitted values in `DataPersistor` key `contact_us`; the Hyvä template pre-fills
  the fields from it through `Magento\Contact\Helper\Data::getPostValue()`.

**Current state**

- `Magento_ReCaptchaContact` is enabled, but `recaptcha_frontend/type_for/contact` is empty:
  no captcha is active on the form.
- CSP storefront mode is `report_only` (Magento 2.4.8 default). Hyvä provides `HyvaCsp`.

**Conclusion:** Turnstile follows the same pattern as `Magento_ReCaptchaContact` — a block in
`form.additional.info` and a predispatch observer — without touching the template or vendor code.

## 4. Architecture

```
AlpineCommerce/Turnstile
├── Api/ValidatorInterface.php          public contract
├── Model/ValidationResult.php          value object
├── Model/Config.php                    per-store configuration reader
├── Model/SiteVerifyClient.php          the only HTTP call to Cloudflare
├── Model/Validator.php                 business rules (implements ValidatorInterface)
├── Model/FormGuard.php                 generic request guard for any form
├── Observer/ContactFormObserver.php    Contact-specific wiring
└── ViewModel/Widget.php                data for the widget template
```

| Unit | Responsibility | Depends on |
|---|---|---|
| `Api\ValidatorInterface` | `validate(string $token, ?string $remoteIp, string $formId, ?int $storeId): ValidationResult` | — |
| `Model\ValidationResult` | Immutable: `isValid()`, `getErrorType()` (`none`, `user`, `config`, `unavailable`), `getErrorCodes()` | — |
| `Model\Config` | Reads config for a store: `isEnabledFor(formId, storeId)`, `getSiteKey()`, `getSecretKey()` (decrypted), `getTheme()`, `getTimeout()`, `getFailureMode()`; logs a warning when enabled without keys | `ScopeConfigInterface`, `EncryptorInterface`, logger |
| `Model\SiteVerifyClient` | POST `secret`, `response`, `remoteip` (form-urlencoded) with timeout; returns decoded array; throws `SiteVerifyUnavailableException` on network error, timeout, HTTP ≠ 200, invalid JSON | `Magento\Framework\HTTP\ClientFactory`, URL injected via `di.xml` |
| `Model\Validator` | Empty / oversized token check, calls client, interprets `success`, `error-codes`, `action`, applies `failure_mode` | Client, Config, logger |
| `Model\FormGuard` | `guard(string $formId, RequestInterface, HttpResponseInterface, string $redirectUrl): bool` — on failure: error message, `FLAG_NO_DISPATCH`, redirect; returns whether the request passed | Validator, Config, `ManagerInterface`, `ActionFlag`, `RemoteAddress` |
| `Observer\ContactFormObserver` | Calls `FormGuard('contact')`; on failure stores the post data in `DataPersistor('contact_us')` | FormGuard, `DataPersistorInterface`, `UrlInterface` |
| `ViewModel\Widget` | `isEnabled(formId)`, `getSiteKey()`, `getLanguage()`, `getTheme()`, `getScriptUrl()` | Config, `ResolverInterface` (locale) |

Adding another form later = one layout file injecting the widget block with its `form_id`, one
observer on that form's predispatch event calling `FormGuard`, one `forms/<form_id>` config flag.

## 5. Token generation and transmission

- `view/frontend/layout/contact_index_index.xml` (in the module) adds block `turnstile.contact`
  (`Magento\Framework\View\Element\Template`, template `AlpineCommerce_Turnstile::widget.phtml`,
  arguments `form_id = contact`, view model `ViewModel\Widget`) into
  `contactForm` → `form.additional.info`, i.e. **inside the `<form>`**.
- If the form is not enabled for the current store, the template renders nothing.
- Otherwise the template renders:
  - `<div class="cf-turnstile" data-sitekey="…" data-action="contact" data-language="…" data-theme="…"></div>`
  - `<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>`
    (implicit rendering).
  - A small inline script, registered through `HyvaCsp`, adding a **capture-phase `click`
    listener** on the form's submit button: if the hidden `cf-turnstile-response` field is empty,
    it prevents the submission and shows an inline message. UX only.
- The browser obtains the token from Cloudflare; Turnstile writes it into the hidden input
  `cf-turnstile-response` inside the form. `$form.submit()` sends it with the other fields.
- `data-action`: the form id (`contact`); Cloudflare allows ≤ 32 chars, `[A-Za-z0-9_-]`.
- `data-refresh-expired` stays at the default `auto` (tokens expire after 300 s).
- **FPC:** the markup depends only on the store (site key, language, theme), never on the
  customer or session. The token is always generated client-side.

## 6. Server-side validation

Entry point: `etc/frontend/events.xml` →
`controller_action_predispatch_contact_index_post` → `Observer\ContactFormObserver`.
It runs before `Magento\Contact\Controller\Index\Post`, so no e-mail is sent when validation fails.

Sequence:

1. `Config::isEnabledFor('contact', storeId)` is false → return, nothing happens.
2. Read `cf-turnstile-response` from the POST body and the client IP from `RemoteAddress`.
3. `Validator::validate()`:
   1. Empty token → `user` error, no HTTP call.
   2. Token longer than 2048 chars → `user` error, no HTTP call.
   3. `SiteVerifyClient` POST to `https://challenges.cloudflare.com/turnstile/v0/siteverify`
      with `secret`, `response`, `remoteip`.
   4. Interpret the response (see §8).
   5. On `success === true`, require `action === 'contact'`; otherwise `user` error.
      Exception: Cloudflare's official test secrets answer **without** `action` and flag the
      answer with `metadata.result_with_testing_key: true` (observed 2026-09-24). Only such an
      answer may omit `action`; an answer with a different `action` is always rejected.
4. On failure `FormGuard`: translated error message, `Action::FLAG_NO_DISPATCH`, redirect to
   `contact/index`. The observer stores the POST data in `DataPersistor('contact_us')` so the
   customer finds the form pre-filled.

## 7. Per-store configuration

Admin: **Stores › Configuration › AlpineCommerce › Cloudflare Turnstile** (existing tab
`alpinecommerce`). ACL resource `AlpineCommerce_Turnstile::config`.
All fields: `showInDefault="1" showInWebsite="1" showInStore="1"`.

| Path | Type | Default |
|---|---|---|
| `alpinecommerce_turnstile/general/enabled` | Yes/No | `0` |
| `alpinecommerce_turnstile/general/site_key` | text | empty |
| `alpinecommerce_turnstile/general/secret_key` | `obscure`, backend `Magento\Config\Model\Config\Backend\Encrypted` | empty |
| `alpinecommerce_turnstile/general/theme` | `auto` / `light` / `dark` | `auto` |
| `alpinecommerce_turnstile/general/timeout` | integer seconds, validated `1..30` | `5` |
| `alpinecommerce_turnstile/general/failure_mode` | `closed` / `open` | **`closed`** |
| `alpinecommerce_turnstile/forms/contact` | Yes/No | `1` |

- A form is active only if `enabled`, `forms/<form_id>`, site key and secret key are all set.
  Enabled but a key missing → widget not rendered, validation skipped, `warning` logged.
- The admin comment on `enabled` states that reCAPTCHA must not also be enabled for the same form.
- `secret_key` is never rendered in the frontend, never exposed by the view model.
- **Widget language** from the store's `general/locale/code`: take the primary language subtag
  (`fr_FR` → `fr`, `de_CH` → `de`, `pt_PT` → `pt`, `sv_SE` → `sv`); if it is not in Cloudflare's
  supported 2-letter list, use `auto`. Supported list (Cloudflare docs, 2026-09-24): ar, bg, zh,
  hr, cs, da, nl, en, fa, fi, fr, de, el, he, hi, hu, id, it, ja, ko, lt, ms, nb, pl, pt, ro,
  ru, sr, sk, sl, es, sv, tl, th, tr, uk, vi.

## 8. Error handling and fallback

| Case | Error type | Behavior | Customer message |
|---|---|---|---|
| Token missing or empty | `user` | reject, no HTTP call | "Please complete the security check." |
| Token > 2048 chars | `user` | reject, no HTTP call | "The security check failed. Please try again." |
| `success=false` with `invalid-input-response`, `timeout-or-duplicate`, `missing-input-response` | `user` | reject | "The security check failed. Please try again." |
| `success=true` but `action` ≠ form id | `user` | reject, `warning` logged | "The security check failed. Please try again." |
| `missing-input-secret`, `invalid-input-secret`, `bad-request` | `config` | reject, `critical` logged | "We could not verify your request. Please try again later." |
| `internal-error`, network error, timeout, body that is not a Siteverify JSON answer (any HTTP status) | `unavailable` | `failure_mode=closed` (default): reject, `error` logged. `open`: accept, `warning` logged | closed: "The security check is temporarily unavailable. Please try again later." |

- Siteverify answers `invalid-input-secret` with **HTTP 400 and a JSON body** (observed 2026-09-24):
  any status carrying a Siteverify JSON answer is interpreted normally, so a wrong secret is a
  `config` error and is rejected **even in `open` mode**.
- Log file `var/log/turnstile.log` through a virtual Monolog logger declared in `di.xml`.
- Log context contains only: form id, store id, error type, Cloudflare error codes, HTTP status.
  **Never** the token, the secret, the POST body or the full Siteverify request.
- If the Turnstile script cannot load in the browser, no token is produced and the request is
  rejected even in `open` mode (the server cannot distinguish a blocked script from a bot).
- Customer messages are translated in the module's `i18n/{fr_FR,de_DE,es_ES,it_IT}.csv`
  (append-only rules of the project, no `key == value` line).

## 9. CSP

`etc/csp_whitelist.xml`: `script-src` and `frame-src` → `challenges.cloudflare.com`
(Cloudflare CSP reference). No `connect-src` needed (pre-clearance is not used). The inline
guard script is registered with `HyvaCsp::registerInlineScript()` so it keeps working if the
storefront CSP leaves report-only mode.

## 10. Testing

### Official Cloudflare test keys

Verified on 2026-09-24 against
<https://developers.cloudflare.com/turnstile/troubleshooting/testing/>.

| Site key | Behavior |
|---|---|
| `1x00000000000000000000AA` | Always passes, visible |
| `2x00000000000000000000AB` | Always fails, visible |
| `1x00000000000000000000BB` | Always passes, invisible |
| `2x00000000000000000000BB` | Always fails, invisible |
| `3x00000000000000000000FF` | Forces an interactive challenge, visible |

| Secret key | Behavior |
|---|---|
| `1x0000000000000000000000000000000AA` | Always passes validation |
| `2x0000000000000000000000000000000AA` | Always fails (`invalid-input-response`) |
| `3x0000000000000000000000000000000AA` | "Token already spent" (`timeout-or-duplicate`) |

Test site keys produce the dummy token `XXXX.DUMMY.TOKEN.XXXX`. Production secrets reject it, so
tests must pair a test site key with a test secret key.

Observed Siteverify answer for secret `1x…AA` and the dummy token (2026-09-24):
`{"success":true,"error-codes":[],"hostname":"example.com","metadata":{"result_with_testing_key":true}}`
— no `action` field (see §6 step 3.5).

### Unit tests (`Test/Unit`, `dev/tests/unit/phpunit.xml.dist`)

- `Model\ValidatorTest`: success; empty token; oversized token; each error-code family;
  action mismatch; `unavailable` in `closed` and `open` modes; no token/secret in log context.
- `Model\SiteVerifyClientTest`: request body fields; valid JSON; invalid JSON, HTTP ≠ 200 and
  transport exception → `SiteVerifyUnavailableException`.
- `Model\ConfigTest`: active only when all four conditions hold; secret decrypted;
  timeout bounds.
- `ViewModel\WidgetTest`: locale → language mapping and `auto` fallback.
- `Model\FormGuardTest`: disabled form → no validation; message per error type; array token treated
  as empty; missing client IP → `null`.
- `Observer\ContactFormObserverTest`: no controller → no-op; failure → `FLAG_NO_DISPATCH`,
  redirect and `DataPersistor` set; success → no side effect.

### Manual end-to-end (Docker, commands as `www-data`)

Configuration set only on store view `french` with
`bin/magento config:set --scope=stores --scope-code=french …` (secret through `config:set` so the
`Encrypted` backend applies).

1. Site `1x…AA` + secret `1x…AA`: widget shown in French, message sent, success message.
2. Secret `2x…AA`: error message, fields pre-filled, no e-mail.
3. Secret `3x…AA`: error message (duplicate token).
4. `curl` POST without `cf-turnstile-response` (valid form key): rejected.
5. `challenges.cloudflare.com` temporarily mapped to `127.0.0.1` in the PHP container's `/etc/hosts`
   (restored afterwards; the browser still loads the widget): rejected in `closed`; accepted and logged in `open`.
6. Isolation: `default` store view shows no widget; `german`/`spanish`/`italian` show the right
   language when enabled; cart and checkout unchanged; no CSP violation in the console.
7. `var/log/turnstile.log` contains no token and no secret.

## 11. Deliberate limitations / future work

- **Hostname check** of the Siteverify response is not enforced: test keys do not return the
  store's hostname and store domains differ per environment. Can be added as an optional
  per-store "expected hostnames" setting.
- No retry with `idempotency_key`; a single call with timeout is enough for a contact form.
- Other forms: follow §4 "Adding another form".

## 12. Files

**Created** — all under `src/app/code/AlpineCommerce/Turnstile/`:

- `registration.php`, `composer.json`
- `etc/module.xml` (sequence `Magento_Store`, `Magento_Config`, `Magento_Contact`, `Hyva_Theme`)
- `etc/config.xml`, `etc/acl.xml`, `etc/adminhtml/system.xml`
- `etc/di.xml` (preference, virtual logger, Siteverify URL argument, `secret_key` declared sensitive)
- `etc/frontend/events.xml`
- `etc/csp_whitelist.xml`
- `Api/ValidatorInterface.php`
- `Exception/SiteVerifyUnavailableException.php`
- `Model/ValidationResult.php`, `Model/Config.php`, `Model/SiteVerifyClient.php`,
  `Model/Validator.php`, `Model/FormGuard.php`
- `Model/Config/Source/Theme.php`, `Model/Config/Source/FailureMode.php`
- `Observer/ContactFormObserver.php`
- `ViewModel/Widget.php`
- `view/frontend/layout/contact_index_index.xml`
- `view/frontend/templates/widget.phtml`
- `i18n/fr_FR.csv`, `i18n/de_DE.csv`, `i18n/es_ES.csv`, `i18n/it_IT.csv`
- `Test/Unit/Model/ValidatorTest.php`, `Test/Unit/Model/SiteVerifyClientTest.php`,
  `Test/Unit/Model/ConfigTest.php`, `Test/Unit/Model/FormGuardTest.php`, `Test/Unit/ViewModel/WidgetTest.php`,
  `Test/Unit/Observer/ContactFormObserverTest.php`

**Created — documentation:** `docs/modules/TURNSTILE.md` (same format as the other module docs);
rows added to the module tables of `README.md` and `docs/README.md`.

**Modified:** `src/app/etc/config.php` (module registration via `module:enable`).

**Not touched:** `vendor/`, Hyvä templates, `Magento_ReCaptcha*`, theme `AlpineCommerce/hyva`,
checkout.

**Deployment (as `www-data`):** `bin/magento module:enable AlpineCommerce_Turnstile`,
`bin/magento setup:upgrade`, `bin/magento cache:clean`. No `di:compile` (developer mode).
