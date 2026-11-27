# Magento 2 from Scratch — Magento JS Libraries

> **Series**: [Learning Path](../README.md) → Phase 2 (JS), Article 3 of 4
> **Previous**: [`02-knockoutjs.md`](02-knockoutjs.md) — KnockoutJS
> **Next**: [`04-integrating-js.md`](04-integrating-js.md) — Integrating JS into pages
> **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

Magento wraps common JavaScript tasks under the `mage/*` namespace. These are
**not** standard libraries — they are Magento-specific wrappers that handle
authentication, error formatting, and session cookies.

This article covers the four you will use 90% of the time.

---

## 1. `mage/storage` — Secure AJAX for REST calls

**`$.ajax` is discouraged in Magento.** Instead, use `mage/storage`:

```js
define(['mage/storage', 'mage/translate'], function (storage, $t) {
    'use strict';

    return {
        saveStore: function (storeCode) {
            storage.post(
                '/rest/V1/alpinecommerce/store-pickup',       // URL
                JSON.stringify({ sourceCode: storeCode }),    // Body
                false,                                        // parallel (no spinner)
                'application/json'                            // Content-Type
            ).done(function (response) {
                console.log('Success:', response);
            }).fail(function (xhr) {
                console.error('Error:', xhr.responseText);
            });
        },

        getPosts: function () {
            storage.get('/rest/V1/blog/posts', false)
                .done(function (posts) {
                    console.log(posts);
                });
        }
    };
});
```

### Why `mage/storage` instead of `$.ajax`?

- **Session cookies** are handled automatically (you stay logged in).
- **401 responses** trigger a redirect to the login page.
- **`X-Requested-With: XMLHttpRequest`** header is added automatically.
- **Magento error format** — errors are parsed into readable messages.
- **Loading overlay** — `true` as the third argument shows a spinner.

### Available methods

| Method | Purpose |
|--------|---------|
| `storage.get(url, parallel)` | GET request |
| `storage.post(url, data, parallel, contentType)` | POST request |
| `storage.put(url, data, parallel, contentType)` | PUT request |
| `storage.delete(url, parallel)` | DELETE request |

---

## 2. `mage/translate` — Client-side translation

```js
define(['mage/translate'], function ($t) {
    'use strict';

    var msg1 = $t('Hello World');
    var msg2 = $t('Hello %1, you have %2 products').replace('%1', 'Alice').replace('%2', '5');

    // Translation is looked up in i18n/fr_FR.csv, i18n/de_DE.csv
    // If a translation exists, $t returns it; otherwise the original string
});
```

**Where translations live**:

```csv
# src/app/code/AlpineCommerce/Blog/i18n/fr_FR.csv
"Add to Cart","Ajouter au panier"
"Hello World","Bonjour le monde"
```

---

## 3. `mage/utils` — Utility helpers

```js
define(['mage/utils'], function (utils) {
    'use strict';

    // Serialize object to query string
    var query = utils.stringify({ page: 1, limit: 10 });
    // → "page=1&limit=10"

    // Deep clone an object (safe copy, no reference sharing)
    var copy = utils.copy(originalObject);

    // Check if object is empty
    if (utils.isEmpty(array)) { ... }

    // Convert snake_case to camelCase
    var camel = utils.camelCase('hello_world');
});
```

---

## 4. `mage/mage` — jQuery UI initialization

```js
define(['jquery', 'mage/mage'], function ($, mage) {
    'use strict';

    // Initialize Magento's validation on a form
    $('#review-form').mage('validation', {
        ignore: '[data-skip-validation]'
    });
});
```

---

## 5. How AlpineCommerce uses these

| Module | Library | File | Usage |
|--------|---------|------|-------|
| StorePickup | `mage/storage` | `js/view/store-pickup.js` | POST to REST API for store selection |
| StorePickup | `mage/translate` | `js/view/store-pickup.js` | Translated "Store saved" message |
| ProductReviews | `mage/storage` | `js/review-form.js` | Submit review via REST |
| LoyaltyProgram | `mage/storage` | `js/view/loyalty-points.js` | Fetch loyalty points |

---

## 6. What you should remember

1. **`mage/storage`** is the only correct way to make AJAX/REST calls in
   Magento. Never use `$.ajax` directly.
2. **`mage/translate`** (`$t`) — use for all user-facing strings; translations
   live in `i18n/*.csv`.
3. **`mage/utils`** — helpers for common tasks (copy, stringify, isEmpty).
4. **`mage/mage`** — initializes jQuery UI components (validation, widgets).
5. All libraries are RequireJS modules — include them in `define([...])`.

---

## 7. Where to go next

- **Article 4**: [`04-integrating-js.md`](04-integrating-js.md) — wiring JS
  into pages via `data-mage-init` and layout XML
- **Previous**: [KnockoutJS](02-knockoutjs.md)
- **Phase 3**: [`../core/06-events-observers.md`](../core/06-events-observers.md)
- **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

*Sources: `src/vendor/magento/module-ui/view/base/web/js/lib/storage/brassistant.js`,
`src/vendor/magento/module-translation/view/frontend/web/js/translate.js`,
`src/app/code/AlpineCommerce/StorePickup/view/frontend/web/js/view/store-pickup.js`*
