# AlpineCommerce_Turnstile Module — Cloudflare Turnstile

> **Status**: ✅ Stable (v1.0.0) — Contact Us form

## 1. Responsibility

Protects storefront forms with **Cloudflare Turnstile**, validated **server-side** before the
controller runs. First protected form: **Contact Us** (`/contact`, POST `contact/index/post`).
The architecture is reusable: another form needs one layout file, one observer and one config flag.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Widget** | Rendered inside the Hyvä contact form through the `form.additional.info` container — no template override |
| **Server-side validation** | Predispatch observer calls Cloudflare Siteverify; no e-mail is sent when validation fails |
| **Action check** | The token must have been generated for this form (`action = contact`) |
| **Per-store configuration** | Enable, site key, encrypted secret key, theme, timeout, failure mode, per-form flag |
| **Failure mode** | `closed` (default): reject when Cloudflare is unreachable — `open`: accept and log a warning |
| **Kept input** | On failure the customer's input is kept (DataPersistor `contact_us`), never the token |
| **Widget language** | From the store locale (`fr_FR` → `fr`), `auto` if Turnstile does not support it |
| **Dedicated log** | `var/log/turnstile.log`, anomalies only, never the token or the secret |
| **CSP** | `script-src` and `frame-src` allow `https://challenges.cloudflare.com` |
| **i18n** | fr_FR, de_DE, es_ES, it_IT (customer messages only) |

## 3. Architecture

```
AlpineCommerce/Turnstile/
├── Api/
│   └── ValidatorInterface.php          # public contract (@api)
├── Exception/
│   └── SiteVerifyUnavailableException.php
├── Model/
│   ├── Config.php                      # per-store config reader (secret decrypted)
│   ├── Config/Source/Theme.php         # auto / light / dark
│   ├── Config/Source/FailureMode.php   # closed / open
│   ├── SiteVerifyClient.php            # the only HTTP call to Cloudflare
│   ├── Validator.php                   # business rules
│   ├── ValidationResult.php            # immutable result (none/user/config/unavailable)
│   └── FormGuard.php                   # reusable request guard for any form
├── Observer/
│   └── ContactFormObserver.php         # controller_action_predispatch_contact_index_post
├── ViewModel/
│   └── Widget.php                      # site key, theme, language — never the secret
├── etc/
│   ├── acl.xml                         # AlpineCommerce_Turnstile::config
│   ├── adminhtml/system.xml            # Stores > Configuration > AlpineCommerce > Cloudflare Turnstile
│   ├── config.xml                      # defaults: disabled, auto, 5 s, closed, contact = yes
│   ├── csp_whitelist.xml
│   ├── di.xml                          # preference, dedicated logger, Siteverify URL, sensitive secret
│   ├── frontend/events.xml
│   └── module.xml                      # sequence: Magento_Store, Magento_Config, Magento_Contact, Hyva_Theme
├── i18n/                               # fr_FR, de_DE, es_ES, it_IT
├── Test/Unit/                          # 54 tests
└── view/frontend/
    ├── layout/contact_index_index.xml  # block turnstile.contact → form.additional.info
    └── templates/widget.phtml          # widget + UX-only click guard
```

**Request flow**

1. `GET /contact` — `widget.phtml` renders `div.cf-turnstile` (`data-sitekey`, `data-action`,
   `data-language`, `data-theme`) and loads `https://challenges.cloudflare.com/turnstile/v0/api.js`.
2. The browser gets a token from Cloudflare; Turnstile writes it into the hidden field
   `cf-turnstile-response` inside the form.
3. `POST contact/index/post` — `ContactFormObserver` → `FormGuard::guard('contact', …)` →
   `Validator` → `SiteVerifyClient` (POST `secret`, `response`, `remoteip`).
4. On failure: translated error message, `FLAG_NO_DISPATCH` (the contact controller does not run),
   redirect to `contact/index`, input kept without the token.

**Validation rules**

| Case | Result | Customer message (en_US key) | Log |
|---|---|---|---|
| Missing or empty token | reject, no HTTP call | Please complete the security check. | — |
| Token > 2048 characters | reject, no HTTP call | The security check failed. Please try again. | — |
| `invalid-input-response`, `timeout-or-duplicate` | reject | The security check failed. Please try again. | — |
| `success: true` but other `action` | reject | The security check failed. Please try again. | WARNING |
| `invalid-input-secret`, `missing-input-secret`, `bad-request` | reject (**also in open mode**) | We could not verify your request. Please try again later. | CRITICAL |
| Unreachable, timeout, `internal-error`, body without a Siteverify answer | `closed`: reject — `open`: accept | The security check is temporarily unavailable. Please try again later. | ERROR / WARNING |

Siteverify answers some errors (e.g. `invalid-input-secret`) with **HTTP 400 and a JSON body**: any
status carrying a Siteverify answer is interpreted, only transport errors or non-Siteverify bodies
mean "unavailable". Cloudflare **test** secrets answer without `action` and with
`metadata.result_with_testing_key: true`; only such answers may omit `action`.

## 4. Database

No dedicated table (configuration in `core_config_data`, secret stored encrypted `0:3:…`).

## 5. REST API

None.

## 6. Admin

- **Stores > Configuration > AlpineCommerce > Cloudflare Turnstile** (all fields down to store view):

| Path | Field | Default |
|---|---|---|
| `alpinecommerce_turnstile/general/enabled` | Enable Turnstile | No |
| `alpinecommerce_turnstile/general/site_key` | Site Key (public) | — |
| `alpinecommerce_turnstile/general/secret_key` | Secret Key (obscure, encrypted, sensitive) | — |
| `alpinecommerce_turnstile/general/theme` | Widget Theme | auto |
| `alpinecommerce_turnstile/general/timeout` | Siteverify Timeout (1–30 s) | 5 |
| `alpinecommerce_turnstile/general/failure_mode` | If Cloudflare Is Unavailable | Closed |
| `alpinecommerce_turnstile/forms/contact` | Contact Us | Yes |

- A form is active only when **enabled + form flag + site key + secret key** are all set for the store.
- **ACL**: `AlpineCommerce_Turnstile::config`.
- Do **not** enable Google reCAPTCHA (`Magento_ReCaptchaContact`) on the same form.
- ⚠️ Browsers may autofill the **Site Key** (with the admin username) and the **Secret Key**
  (with the admin password) because the secret is a password-type field. Check both fields before
  *Save Config*, and choose the right **Scope** (the store view value wins over website/default).

**Keys**

| Environment | Site Key | Secret Key |
|---|---|---|
| Local / training | `1x00000000000000000000AA` (always passes) | `1x0000000000000000000000000000000AA` |
| Production | from dash.cloudflare.com › Turnstile › Add widget (`0x4AAAA…`), hostnames = shop domains | pair of that site key |

Official test keys work on any domain, including `localhost`. Other test secrets:
`2x0000000000000000000000000000000AA` (always fails), `3x0000000000000000000000000000000AA`
(token already spent). Source: <https://developers.cloudflare.com/turnstile/troubleshooting/testing/>.

## 7. Frontend

- Widget inside `<form id="contact">`, below the message field; language from the store locale.
- UX-only click guard: submitting before the widget is solved shows
  "Please complete the security check." and does not submit. The server always validates.
- Inline script registered with `$hyvaCsp->registerInlineScript()` when running on a Hyvä theme.
- **FPC**: the markup depends only on the store (site key, language, theme). Note that Hyvä declares
  `contactForm` `cacheable="false"`, so the contact page is not full-page cached anyway.

## 8. CLI

No dedicated command. Useful commands (always as `www-data`):

```bash
# enable on one store view with the test keys
docker exec -u www-data magento2-php bin/magento config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/enabled 1
docker exec -u www-data magento2-php bin/magento config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/site_key 1x00000000000000000000AA
docker exec -u www-data magento2-php bin/magento config:set --scope=stores --scope-code=french alpinecommerce_turnstile/general/secret_key 1x0000000000000000000000000000000AA
docker exec -u www-data magento2-php bin/magento cache:clean config

# unit tests
docker exec -u www-data magento2-php vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist --do-not-cache-result --no-extensions --testdox app/code/AlpineCommerce/Turnstile/Test/Unit

# follow the logs
tail -f src/var/log/turnstile.log src/var/log/exception.log
```

`bin/magento config:delete` does not exist in Magento 2.4.8: to remove a store-view value, tick
**Use Website / Use Default** in the admin.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Layout injection into `form.additional.info` | Same mechanism as `Magento_Captcha` / `Magento_ReCaptchaContact`; no Hyvä template override |
| Predispatch observer + `FLAG_NO_DISPATCH` | Same event as `Magento_ReCaptchaContact`; the contact controller never runs on failure |
| `FormGuard` separated from the observer | Reusable for any form; the observer only holds contact-specific wiring |
| Single `SiteVerifyClient` | One place for HTTP, timeout and error mapping; easy to mock |
| `failure_mode = closed` by default | Project requirement: no message accepted without verification |
| Config errors rejected even in `open` mode | A wrong secret must never silently disable the protection |
| Secret `Encrypted` + declared sensitive | Never in clear in the database or in `app:config:dump` |
| Anomaly-only logging, fixed context keys | Token and secret can never reach the log |

**Adding another form**

1. Layout of the form's page: add a `Magento\Framework\View\Element\Template` block with template
   `AlpineCommerce_Turnstile::widget.phtml`, arguments `form_id` and `view_model`
   (`AlpineCommerce\Turnstile\ViewModel\Widget`), in a container **inside** the `<form>`.
2. Observer on `controller_action_predispatch_<route>_<controller>_<action>` calling
   `FormGuard::guard('<form_id>', $request, $response, $redirectUrl)`.
3. `system.xml` + `config.xml`: `alpinecommerce_turnstile/forms/<form_id>`.

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| 1 | Siteverify `hostname` not checked (test keys return `example.com`, domains differ per environment) | Deliberate — possible future per-store "expected hostnames" setting |
| 2 | Only the Contact Us form is protected | Deliberate (first step) |
| 3 | Successful or ordinary rejected submissions are not logged | Deliberate (anomaly-only log) |
| 4 | Behind a proxy/CDN the client IP sent as `remoteip` is the proxy's unless Magento is configured for forwarded headers | Optional parameter, validation still works |

## 11. Magento concepts taught

- Layout containers and injecting a block into a third-party form
- Controller predispatch events, `ActionFlag::FLAG_NO_DISPATCH`, DataPersistor
- System configuration per store view, `obscure` fields, `Encrypted` backend, sensitive config paths
- Virtual types for a dedicated Monolog logger
- `csp_whitelist.xml` and Hyvä `HyvaCsp`
- Store emulation and locale-based translations (`i18n` CSV load order: module < pack < theme < DB)

## 12. Validation & status

- **Status**: ✅ Stable (v1.0.0)
- **Unit tests**: 54 tests / 96 assertions (Config, SiteVerifyClient, Validator, FormGuard,
  ContactFormObserver, Widget)
- **End-to-end (Docker, store view `french`, official test keys)**: valid submission accepted;
  missing, invalid and already-spent tokens rejected with the customer's input kept; invalid secret
  rejected in closed **and** open mode (CRITICAL log); Cloudflare unreachable rejected in closed mode
  and accepted in open mode; widget absent on other store views; widget language and messages
  checked for fr / de / es / it; no token or secret in `turnstile.log`; cart, checkout and admin unchanged.

---

*Sources: `docs/superpowers/specs/2026-09-24-turnstile-contact-design.md`,
`docs/superpowers/plans/2026-09-24-turnstile-contact.md`.*
