# Magento 2 from Scratch — KnockoutJS

> **Series**: [Learning Path](../README.md) → Phase 2 (JS), Article 2 of 4
> **Previous**: [`01-requirejs.md`](01-requirejs.md) — RequireJS
> **Next**: [`03-magento-js-libs.md`](03-magento-js-libs.md) — Magento JS libs
> **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

KnockoutJS (**KO**) is the reactive UI library that powers Magento's checkout
and complex admin forms. If React or Vue made data drive the UI, Knockout does
the same thing — but with a different syntax.

This article covers just enough KO to read and modify Magento checkout code.

---

## 1. The reactive concept

In plain JavaScript, if you change a variable, the HTML doesn't update:

```js
let count = 0;
count = 5;
// The page still shows "0" unless you manually update it
```

Knockout makes variables **reactive** with **observables**:

```js
define(['ko'], function (ko) {
    'use strict';

    return {
        initialize: function () {
            this.count = ko.observable(0);
        },

        increment: function () {
            this.count(this.count() + 1);
        }
    };
});
```

Now when `this.count(5)` is called, every HTML element bound to `count`
updates **automatically**.

---

## 2. Observables: read and write

| Operation | Syntax | Example |
|-----------|--------|---------|
| Create | `ko.observable(initial)` | `this.name = ko.observable('Alice')` |
| Read | `name()` | `var current = this.name()` |
| Write | `name(value)` | `this.name('Bob')` |
| Array create | `ko.observableArray([])` | `this.items = ko.observableArray([1, 2, 3])` |

> **Key gotcha**: reading uses `()`, writing does not:
> `this.count()` reads, `this.count(5)` writes.

---

## 3. Computed — derived values

A **computed** recalculates automatically when its dependencies change:

```js
this.firstName = ko.observable('Alice');
this.lastName = ko.observable('Dupont');

this.fullName = ko.computed(function () {
    return this.firstName() + ' ' + this.lastName();
}, this);
```

When `firstName` or `lastName` changes, `fullName` updates automatically.

---

## 4. Data bindings in HTML

Knockout uses `data-bind` attributes to connect HTML to JavaScript:

| Binding | What it does |
|--------|-------------|
| `text: someValue` | Set text content |
| `html: someHtml` | Set HTML content (⚠️ XSS risk) |
| `value: someValue` | Two-way bind to an input |
| `visible: condition` | Show/hide element |
| `click: someFunction` | Click handler |
| `checked: someValue` | Checkbox/radio binding |
| `options: items` | Dropdown options |
| `selectedOptions: values` | Multi-select binding |
| `foreach: items` | Loop over an array |
| `if: condition` | Include element only if true |
| `with: someObject` | Change context to an object |
| `attr: { href: url }` | Set an HTML attribute |

### Example: a live counter

```html
<div data-bind="with: $parent">
    <p>Count: <span data-bind="text: count"></span></p>
    <button data-bind="click: increment">+</button>
    <button data-bind="click: decrement">-</button>
</div>
```

```js
define(['ko'], function (ko) {
    'use strict';

    return {
        initialize: function () {
            this.count = ko.observable(0);
        },
        increment: function () {
            this.count(this.count() + 1);
        },
        decrement: function () {
            this.count(this.count() - 1);
        }
    };
});
```

---

## 5. How it works in Magento checkout

The checkout page (`checkout_index_index.xml`) loads UI Components (XML) that
define Knockout components. Each component has:

1. A **JavaScript** file (`view/frontend/web/js/view/...`)
2. A **template** (`view/frontend/web/template/...`) — KO template, not `.phtml`

```js
// src/app/code/AlpineCommerce/StorePickup/view/frontend/web/js/view/store-pickup.js
define(['ko', 'mage/storage', 'mage/translate'], function (ko, storage, $t) {
    'use strict';

    return {
        initialize: function (event) {
            this.availableStores = ko.observableArray([]);
            this.selectedStore = ko.observable('');
            this.isSaving = ko.observable(false);
            this.message = ko.observable('');
        },

        isVisible: ko.computed(function () {
            return this.selectedStore() !== '';
        }, this),

        saveStore: function () {
            var self = this;
            this.isSaving(true);

            storage.post(
                '/rest/V1/alpinecommerce/store-pickup',
                JSON.stringify({ sourceCode: this.selectedStore() }),
                false,
                'application/json'
            ).done(function () {
                self.message($t('Store saved'));
            }).always(function () {
                self.isSaving(false);
            });
        }
    };
});
```

```html
<!-- view/frontend/web/template/store-pickup.html -->
<div data-bind="visible: isVisible">
    <label data-bind="i18n: 'Choose your store'"></label>
    <select data-bind="
        options: availableStores,
        optionsText: 'name',
        value: selectedStore,
        event: { change: saveStore }">
    </select>
    <p data-bind="text: message, visible: message"></p>
</div>
```

---

## 6. Common KO patterns in Magento

| Pattern | Code |
|--------|------|
| **Observable array for a list** | `this.items = ko.observableArray([])` |
| **Push to array** | `this.items().push(newItem)` |
| **Filter** | `this.items().filter(function (i) { return i.active(); })` |
| **Conditional display** | `<div data-bind="visible: isLoading">` |
| **Loop** | `<ul data-bind="foreach: items">` |
| **Two-way input** | `<input data-bind="value: myValue">` |

---

## 7. What you should remember

1. **Observables** (`ko.observable`) make values reactive — changes auto-update the HTML.
2. **Computed** (`ko.computed`) derives values from other observables automatically.
3. **`data-bind`** in HTML connects to the JavaScript model.
4. **Reading** an observable uses `()`: `myVar()`. **Writing** does not: `myVar(5)`.
5. **`define(['ko'], function (ko) { ... })`** — always wrap KO code in a RequireJS module.
6. Magento checkout uses KO components: `.js` file + `.html` template.
7. The `LumaCheckout` theme does **not** add custom KO components — the checkout
   behavior comes entirely from Magento core.

---

## 8. Where to go next

- **Article 3**: [`03-magento-js-libs.md`](03-magento-js-libs.md) — `mage/storage`
  for REST, `mage/translate` for translations
- **Article 4**: [`04-integrating-js.md`](04-integrating-js.md) — wiring JS into pages
- **Previous**: [RequireJS](01-requirejs.md)
- **Deep dive**: [`../magento-js.md`](../magento-js.md) — exercises, jQuery,
  debugging, and the full AlpineCommerce JS patterns table.

---

*Sources: `src/app/code/AlpineCommerce/StorePickup/view/frontend/web/js/view/store-pickup.js`,
`src/vendor/magento/module-checkout/view/frontend/web/js/action/*.js`,
`src/vendor/magento/module-ui/view/base/web/js/lib/knockout/`*
