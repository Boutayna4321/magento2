# Magento 2 from Scratch — RequireJS & AMD Modules

> **Series**: [Learning Path](../README.md) → Phase 2 (JS), Article 1 of 4
> **Previous**: [`../css/02-luma-vs-hyva.md`](../css/02-luma-vs-hyva.md) — Luma vs Hyvä
> **Next**: [`02-knockoutjs.md`](02-knockoutjs.md) — KnockoutJS reactivity
> **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

In a plain HTML page, you would include JavaScript with `<script src="...">`.
In Magento, you can't do that — scripts would load in the wrong order, conflict
on global variables, and load files you don't need.

Magento uses **RequireJS** — an AMD (Asynchronous Module Definition) loader —
to manage all JavaScript.

---

## 1. The `define()` syntax (mandatory)

Every Magento JS file must start with `define()`:

```js
// view/frontend/web/js/calculator.js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return {
        add: function (a, b) {
            return a + b;
        },

        sayHello: function () {
            return $t('Hello from Magento');
        }
    };
});
```

| Part | What it does |
|------|-------------|
| `['jquery', 'mage/translate']` | List of dependencies (loaded before this file runs) |
| `function ($, $t)` | Parameters — **order must match** the dependencies array |
| `'use strict';` | **Mandatory** first line in the body |
| `return { ... }` | The public API — everything not returned is private |

> **Rule**: parameter names must match dependency paths. `'jquery'` → `$`,
> `'mage/translate'` → `$t`. This is a Magento convention, not a RequireJS rule,
> but it is how you'll see it everywhere.

---

## 2. Two ways to call a module

### `require()` — one-shot execution (not reusable)

Use `require()` when you just need to run code once, typically from a `.phtml`:

```js
require(['jquery', 'Magento_Blog/js/calculator'], function ($, calc) {
    'use strict';

    $(document).ready(function () {
        var result = calc.add(2, 3);
        $('.result').text(result);
    });
});
```

### `define()` — reusable module

Use `define()` when you create a file that will be loaded by others (the
module pattern shown in §1 above).

---

## 3. requirejs-config.js — the alias map

The full module path `AlpineCommerce_Blog/js/calculator` is long. You can
create an **alias** in `requirejs-config.js`:

```js
// view/frontend/requirejs-config.js
var config = {
    map: {
        '*': {
            // 'blogCalc' is now an alias for the full path
            blogCalc: 'AlpineCommerce_Blog/js/calculator'
        }
    }
};
```

Now you can use the short name everywhere:

```js
require(['jquery', 'blogCalc'], function ($, calc) {
    // calc is the same module
});
```

---

## 4. Built-in dependencies

Magento provides several built-in JS modules:

| Path | Variable | What it provides |
|------|----------|-----------------|
| `jquery` | `$` | jQuery (DOM manipulation, events) |
| `ko` | `ko` | KnockoutJS (observables, computeds) |
| `mage/translate` | `$t` | Translation function |
| `mage/storage` | `storage` | Secure AJAX (REST calls) |
| `mage/utils` | `utils` | Helpers (copy, stringify, etc.) |
| `mage/mage` | — | jQuery UI initialization |
| `underscore` | `_` | Utility library |
| `moment` | `moment` | Date formatting |

---

## 5. File paths and module resolution

RequireJS resolves paths through a chain:

```
Module path: Vendor_Module/js/file.js
  →  src/app/code/Vendor/Module/view/frontend/web/js/file.js
  →  src/app/design/frontend/<Vendor>/<Theme>/Vendor_Module/web/js/file.js  (theme override)
  →  pub/static/frontend/<Vendor>/<Theme>/<locale>/Vendor_Module/js/file.js (deployed)
```

To override a module's JS, copy the file into the theme at the same path.
Magento's fallback will pick up the theme version automatically.

---

## 6. The checkout and RequireJS

The checkout page uses Magento's **UI Components** system, which means it
loads a complex RequireJS configuration. The `LumaCheckout` theme does **not**
add any custom JavaScript — it only has `_extend.less` for CSS. All checkout
behavior (shipping methods, payment forms, etc.) comes from Magento's core
`Magento_Checkout` module via the parent theme.

If you needed to add checkout JavaScript, you would:
1. Create `view/frontend/web/js/checkout/my-script.js` in your theme or module
2. Declare it in `requirejs-config.js`
3. Initialize it via `data-mage-init` in a PHTML or via a UI Component

---

## 7. What you should remember

1. **Every Magento JS file** starts with `define([deps], function (...) { ... })`.
2. **`'use strict'`** is mandatory as the first line inside `define()`.
3. **Parameter order must match** the dependencies array.
4. Everything in the function body that is **not returned** is **private**.
5. **`require()`** runs code once; **`define()`** creates a reusable module.
6. **`requirejs-config.js`** creates aliases to shorten module paths.
7. **Theme override** works the same as template override — copy the file.

---

## 8. Where to go next

- **Article 2**: [KnockoutJS](02-knockoutjs.md) — reactive UI with observables
- **Article 3**: [`03-magento-js-libs.md`](03-magento-js-libs.md) — `mage/storage`,
  `mage/translate`, `mage/utils`
- **Article 4**: [`04-integrating-js.md`](04-integrating-js.md) — how to wire
  JS into a page
- **Previous**: [Luma vs Hyvä](../css/02-luma-vs-hyva.md)
- **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

*Sources: `src/vendor/magento/module-require-js/view/frontend/web/js/require.js`,
`src/vendor/magento/module-checkout/view/frontend/web/js/*.js`,
`src/app/code/AlpineCommerce/StorePickup/view/frontend/web/js/view/store-pickup.js`*
