# JavaScript in Magento — DOM and AJAX

> **Target audience**: PHP developers who must write or maintain the frontend
> of a Magento 2 module. Nothing here is "generic JavaScript": every concept is
> explained through the code that actually runs in this project —
> `AlpineCommerce/StoreLocator`, `AlpineCommerce/ProductReviews`,
> `AlpineCommerce/StorePickup`, `AlpineCommerce/LoyaltyProgram`.
>
> **Goal**: understand how a Magento page is assembled, how a JS component is
> loaded on it, how it reads and writes the DOM, and how it talks back to the
> REST API — plus where Magento forbids you from going.

---

## 1. The mental model

Magento is a **server-rendered** application. A request produces HTML:

```
GET /checkout/cart
   └── PHP: layout XML → Block → .phtml  ──►  HTML + <script> tags
                                                   │
Browser ◄──────────────────────────────────────────┘
   ├── HTML parsed → DOM (the live tree you manipulate)
   ├── RequireJS loads the AMD modules declared in the page
   ├── KnockoutJS binds data to the checkout components
   └── JS calls REST endpoints to mutate the cart / quote
```

Three rules follow from this, and they explain 90% of Magento JS:

1. **The server owns the truth.** A `.phtml` renders the initial state; JS
   only *updates* it. JS never decides a price, a stock level or a permission.
2. **JS is loaded by name, not by URL.** You never write
   `<script src="...">`. You declare a module name (`AlpineCommerce_StoreLocator/js/store-locator`)
   and Magento's RequireJS resolves it to a file.
3. **Every component is initialized by Magento**, after the DOM is ready,
   with `(config, element)`.

---

## 2. Where the files live

| Path | Role |
|---|---|
| `view/frontend/web/js/*.js` | the component code |
| `view/frontend/requirejs-config.js` | declares the short name → file mapping |
| `view/frontend/layout/*.xml` | injects `<script src="...">` into a page |
| `view/frontend/templates/*.phtml` | the markup + the `data-mage-init` hook |
| `view/adminhtml/web/js/*.js` | the same, for admin |
| `etc/webapi.xml` | declares the REST routes your JS calls |
| `etc/acl.xml`, `webapi.xml` | who is allowed to call them |

The three layers of a real module in this repo:

```
AlpineCommerce/StoreLocator/
├── etc/webapi.xml                                   ← the AJAX endpoints
├── view/frontend/
│   ├── requirejs-config.js                           ← 'AlpineCommerce_StoreLocator' alias
│   ├── templates/store-locator.phtml                 ← markup + data-mage-init
│   └── web/js/store-locator.js                       ← the component
└── Model/Api/… + Controller/…                        ← the PHP behind the REST calls
```

Read them in that order: **template (what the DOM looks like) → JS (what it
does) → controller (what the server answers)**.

---

## 3. The language, as Magento uses it

Magento 2 targets ES5-era browsers but modern Magento code uses ES6+. You will
meet the whole spectrum. Here is only what you actually need.

### 3.1 Variables — `var` vs `let` vs `const`

```javascript
var submitUrl = config.submitUrl || '/rest/V1/alphacommerce/product-reviews'; // function scope
let visible = 0;      // block scope, can be reassigned
const cards = [];     // block scope, never reassigned
```

**Magento convention**: modules written for core compatibility still use `var`.
AlpineCommerce components (`store-pickup.js`, `loyalty-points.js`) use `var` for
the internal state and `let`-free style. Match the file you are editing rather
than imposing a personal style.

### 3.2 The language you see every day

```javascript
// String
var url = '/rest/V1/alphacommerce/product-reviews/' + id + '/vote'; // concatenation
var text = `Store ${store.city} - ${store.phone}`;                  // template literal (ES6)

// Number — ALWAYS pass the radix
var rating = parseInt(formValue, 10) || 0;   // '5' → 5, 'abc' → NaN → 0
var total  = parseFloat(response.grand_total) || 0;

// Boolean — two real booleans only
if (window.checkoutConfig.isCustomerLoggedIn && window.checkoutConfig.quoteData) { … }

// Null / undefined
if (!input) { return; }   // both are falsy; covers "element not on this page"

// Array
var stores = this.availableStores();          // read
stores.push(newStore);                        // add
stores.filter(function (s) { return s.city === 'Paris'; });

// Object — the shape of every REST response
var response = {
    items: [{ sku: '24-MB01', qty: 1 }],
    total_count: 1
};
```

### 3.3 Functions

```javascript
// Function declaration — hoisted, usable before its definition
function findStore(code) {
    var stores = this.availableStores();
    for (var i = 0; i < stores.length; i++) {
        if (stores[i].source_code === code) {
            return stores[i];
        }
    }
    return null;
}

// Function expression — the AMD signature
return function (config, element) { … };

// `this` is lost inside a plain callback
var self = this;                              // the standard Magento workaround
self.syncMessage($t('Pickup store saved.'));

// arrow function (ES6) keeps `this` — used in modern Magento code
self.stores.forEach((store) => this.render(store));
```

`var self = this;` appears in almost every core Magento file. It exists for a
real reason: when you pass a function to `$.ajax`, `setTimeout` or
`ko.subscribe`, the callback is called without your object, so `this` becomes
the global object.

### 3.4 Objects — the shape of every Magento JS component

```javascript
define(['jquery'], function ($) {
    'use strict';

    return {
        options: {},                       // defaults
        initialize: function () { … },     // called by Magento
        render: function (data) { … },     // public: callable from templates
        // anything not returned is private to the module
    };
});
```

Two return shapes exist in Magento, and mixing them is the classic bug:

| Shape | Used by | How it is initialized |
|---|---|---|
| `return function (config, element) {…}` | "widget" components | called directly by `data-mage-init` |
| `return { initialize: function() {} }` | jQuery widget style | `initialize()` called, `element` is `this.element` |
| `return Component.extend({…})` | checkout components (Knockout) | Magento builds it from the layout |

This repo uses both: `store-locator.js` and `review-form.js` return a function,
`store-pickup.js` and `loyalty-points.js` return `Component.extend({…})`.

---

## 4. `define()` — the AMD wrapper (mandatory)

Every Magento JS file starts with `define()`. Nothing else is loaded.

```javascript
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (config, element) { … };
});
```

| Part | Meaning |
|---|---|
| array | dependency names, loaded **before** your code runs |
| parameters | same order as the array, always |
| `'use strict';` | first statement of the body — mandatory in Magento code |
| `return` | the module's public API |

Three consequences:

- There is **no global variable**. `$` exists only inside your closure, so two
  modules cannot overwrite each other.
- Dependencies are **cached**. `jquery` is downloaded once even if 40 modules
  ask for it.
- The dependency list is a contract. `'jquery'` → the jQuery instance;
  `'mage/translate'` → the `$t()` translation function.

### 4.1 The `mage/*` libraries you will use

| Dependency | What you get | Typical use |
|---|---|---|
| `'jquery'` | `$` | DOM + `$.ajax` |
| `'mage/translate'` | `$t('text')` | every user-visible string |
| `'mage/storage'` | `storage.get/post/remove` | AJAX with the CSRF token attached |
| `'mage/utils'` | `utils.template`, `utils.mixin` | HTML template binding |
| `'mage/mage'` | `$.widget` registration | jQuery UI widgets (modal, tabs) |
| `'ko'` | Knockout | observables, computed, bindings |
| `'Magento_Checkout/js/model/quote'` | `quote` | the live cart / totals |
| `'Magento_Checkout/js/model/url-builder'` | `urlBuilder.createUrl()` | quote-scoped REST URLs |
| `'Magento_Checkout/js/view/summary/abstract-total'` | `Component` | base class of a checkout summary total |
| `'Magento_Customer/js/customer-data'` | `customerData` | private content in localStorage |

**Never hardcode a user-visible string**: use `$t('...')` so the module stays
translatable. `review-form.js` currently uses `alert()` instead — acceptable
for an internal tool, wrong for a storefront.

### 4.2 `requirejs-config.js` — the name → file mapping

You never write the long path in a template. The module declares a short alias:

```javascript
// view/frontend/requirejs-config.js
var config = {
    map: {
        '*': {
            'AlpineCommerce_StoreLocator':
                'AlpineCommerce/StoreLocator/view/frontend/web/js/store-locator'
        }
    }
};
```

Then templates and layouts use `'AlpineCommerce_StoreLocator/js/store-locator'`
— RequireJS resolves the vendor prefix from the alias. If a module "is not
found" in the console, this file is almost always the cause.

---

## 5. How Magento loads and starts your code

### 5.1 `data-mage-init` — the standard entry point

```php
<!-- view/frontend/templates/store-locator.phtml -->
<div class="store-locator-page" data-mage-init='{"AlpineCommerce_StoreLocator/js/store-locator": {}}'>
```

Magento, after the DOM is ready:

1. reads the attribute,
2. parses the JSON (the key = AMD module name, the value = config),
3. loads the module through RequireJS,
4. calls it with `(config, element)` where `element` is the div itself.

So the component never searches the whole page — it receives its own root:

```javascript
define([], function () {
    'use strict';

    return function (config, element) {
        var input = element.querySelector('#store-locator-search');
        var cards = Array.prototype.slice.call(element.querySelectorAll('.store-card'));
        var noResults = element.querySelector('.store-locator-no-results');

        if (!input || !cards.length) {
            return;   // the block is not on this page — do nothing, throw nothing
        }
    };
});
```

That early `return` is not defensive noise: a module can legitimately be loaded
on several pages, only some of which contain its markup.

### 5.2 Passing PHP values as config

Static JSON in the template is fine for a fixed component, but a real one gets
its URLs from PHP:

```php
<script type="text/x-magento-init">
{
    "#review-form": {
        "AlpineCommerce_ProductReviews/js/review-form": {
            "submitUrl": "<?= $block->escapeUrl($block->getUrl('rest/V1/…')) ?>"
        }
    }
}
</script>
```

```javascript
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var submitUrl = config.submitUrl || '/rest/V1/alphacommerce/product-reviews';
        // fallback keeps the component usable when the config is missing
    };
});
```

Three Magento-specific traps here:

- **`escapeHtml` / `escapeUrl`** inside the JSON, or one quote in a product
  name breaks the whole init block.
- **`/* @noEscape */`** only when the value is already escaped by the block.
- JSON must be valid: PHP arrays need `json_encode`, never a hand-built string.

### 5.3 `layout/*.xml` — putting the component on a page

```xml
<referenceContainer name="content">
    <block class="Magento\Framework\View\Element\Template"
           name="alpine.store_locator"
           template="AlpineCommerce_StoreLocator::store-locator.phtml"/>
</referenceContainer>
```

### 5.4 `window.checkoutConfig` — the checkout bridge

Checkout components cannot receive config through `data-mage-init`: they are
instantiated from the checkout layout. Magento injects a global object instead,
and your module adds its own key from a plugin on
`Magento\Checkout\Model\CheckoutConfig` (`etc/frontend/di.xml`):

```javascript
var config = window.checkoutConfig.storePickup || {};

this.carrierCode   = config.carrierCode || 'storepickup';
this.availableStores = ko.observableArray(config.availableStores || []);
```

Always `|| {}` and always a default per key: `checkoutConfig` differs between
logged-in and guest sessions, and a missing key must degrade gracefully, not
throw.

---

## 6. The DOM inside a Magento page

The markup is **yours** (the `.phtml` of your module) plus core's. So DOM work
is scoped and predictable — you control the ids and classes.

### 6.1 Two selector styles, one rule

jQuery (used by most widgets) and the native API (used by the newer files in
this repo) are equivalent. Pick the one the file already uses.

```javascript
// jQuery
$(element).find('.store-card')
$('#review-title').val()
$(this).data('review-id')          // reads data-review-id
$('.vote-yes').on('click', fn)

// native
element.querySelector('.store-card')
document.getElementById('review-title').value
button.dataset.reviewId            // data-review-id
element.addEventListener('click', fn)
```

Two differences that cause bugs:

| | jQuery | native |
|---|---|---|
| missing element | empty jQuery object (no error) | `null` → `.querySelector` throws |
| multiple elements | first for `.val()`, all for iteration | `querySelector` = first, `querySelectorAll` = all |

### 6.2 Reading a value out of the DOM

```html
<input type="text" name="title" id="review-title" value="">
<input type="radio" name="rating" value="5">
```

```javascript
var title = $('#review-title').val();                        // '' if absent
var rating = $('input[name="rating"]:checked').val();        // undefined if none
var productId = $('input[name="product_id"]').val();

if (!title || !detail || !rating) {
    alert('Please fill in all required fields and select a rating.');
    return;
}
```

Note the `.trim()` in `question.js`: a space is truthy in JavaScript, so
`question.trim()` is what actually rejects an empty textarea.

### 6.3 Writing to the DOM

```javascript
resultCount.textContent = visible + ' stores found';   // safe: no HTML parsing
card.hidden = !matches;                              // hide without touching classes
el.classList.toggle('is-loading', busy);             // second arg = force state
```

**`textContent`, never `innerHTML`, with anything that comes from a customer.**
A review body containing `<script>` is an XSS hole otherwise. `innerHTML` is
acceptable only for markup you built yourself in the same file.

### 6.4 Toggling state instead of reloading

The single biggest UX and performance decision in a Magento widget: **never
reload the page to reflect a change**. `review-form.js` and `question.js` both
call `window.location.reload()` after a successful POST — that works, but it
throws away the scroll position, refetches every product on the page and
resets any open accordion.

The Magento-native alternatives:

```javascript
// 1. patch the node that changed
countEl.textContent = count + 1;
rowEl.classList.add('is-updated');
rowEl.remove();                        // remove the deleted line from the mini-cart

// 2. re-render a region from a template
$('#product-reviews').html($('#product-reviews-tpl').html(utils.template(data)));

// 3. re-render the quote totals — Knockout takes care of the rest
quote.setTotals(response);
```

### 6.5 Event delegation — the pattern for AJAX-rendered lists

After an AJAX call re-renders a list, listeners attached directly to the old
rows are gone. Delegating one listener to the container survives every re-render:

```javascript
list.addEventListener('click', function (event) {
    var button = event.target.closest('.store-card .select');

    if (!button) {
        return;
    }

    var card = button.closest('.store-card');
    console.log('selected store', card.dataset.storeId);
});
```

Compare with the fragile version in `list.js`, which binds `$('#submit-question')`
globally and breaks as soon as the block appears twice on the page.

### 6.6 Do not spam the server while the customer types

`input` fires per keystroke. Debounce, exactly like `loyalty-points.js` does
before its `sync()`:

```javascript
scheduleSync: function () {
    var self = this;

    clearTimeout(this.syncTimeout);
    this.syncTimeout = setTimeout(function () {
        self.sync();
    }, 500);
}
```

---

## 7. AJAX in Magento

### 7.1 Which endpoint do you call?

Your own module routes, declared in `etc/webapi.xml`:

```xml
<route url="/V1/alphacommerce/product-questions" method="POST">
    <service class="AlpineCommerce\ProductQuestions\Api\QuestionRepositoryInterface"
             method="save"/>
    <resources>
        <resource ref="anonymous"/>   <!-- or self for logged-in customers -->
    </resources>
</route>
```

```
Browser JS  ──POST /rest/V1/alphacommerce/product-questions──►  Controller
                                                                   │
                                                          Service → Repository → DB
```

`<resources><resource ref="anonymous"/></resources>` is a security decision:
who may call this endpoint. `anonymous` means the storefront; `self` means the
logged-in customer only. The JS side cannot enforce it — the server does.

### 7.2 `$.ajax` — the direct route

```javascript
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var submitUrl = config.submitUrl || '/rest/V1/alphacommerce/product-reviews';

        $(element).on('click', '#submit-review', function () {
            var title   = $(element).find('#review-title').val();
            var detail  = $(element).find('#review-detail').val();
            var rating  = $(element).find('input[name="rating"]:checked').val();
            var productId = $(element).find('input[name="product_id"]').val();

            if (!title || !detail || !rating) {
                alert('Please fill in all required fields and select a rating.');
                return;
            }

            $(this).prop('disabled', true);

            $.ajax({
                url: submitUrl,
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    productId: parseInt(productId, 10),
                    title: title,
                    detail: detail,
                    rating: parseInt(rating, 10)
                })
            }).done(function () {
                // the server accepted it
            }).fail(function (xhr) {
                alert('Error submitting review: ' + xhr.responseText);
            }).always(function () {
                $(this).prop('disabled', false);
            });
        }.bind(this));
    };
});
```

Read the three parts:

- `contentType` + `JSON.stringify` — Magento REST expects a JSON **body**.
- `.fail(xhr)` — the REST error body (`{"message": "…"}`) is in `xhr.responseText`;
  this is where you surface a server-side message to the customer.
- `.always()` — re-enable the button. Without it the customer clicks again and
  posts a duplicate review.

### 7.3 `mage/storage` — the Magento way (preferred)

`mage/storage` is `$.ajax` plus what Magento requires: the customer's CSRF
token, the correct content type, and cookie-based auth for the customer area.

```javascript
define(['mage/storage', 'mage/translate'], function (storage, $t) {
    'use strict';

    return {
        save: function (payload) {
            var self = this;

            storage.post(
                '/checkout/cart/item',
                JSON.stringify(payload),
                false,                  // do not reload the page on success
                'application/json'
            ).done(function (data) {
                self.message($t('Item added to your cart.'));
            }).fail(function (xhr) {
                self.message(xhr.responseText);
            }).always(function () {
                self.busy(false);
            });
        }
    };
});
```

Signature: `storage.post(url, data, reloadAfter, contentType)`.
`false` for `reloadAfter` is what makes it an AJAX call rather than a
POST-then-reload.

### 7.4 Quote-scoped URLs with `url-builder`

A checkout action belongs to the customer's active quote. Its id lives in
`checkoutConfig.quoteData.id`, and the URL is built by Magento:

```javascript
var url = urlBuilder.createUrl('/carts/mine/store-pickup', {});
// → /rest/V1/carts/mine/store-pickup
```

**Never hardcode a quote id in a template.** Guessing it leaks another
customer's cart.

### 7.5 Mutating the cart: `quote.setTotals`

This is the loop that makes checkout feel instant:

```
user changes points / pickup store
   │
   ├─ JS updates the optimistic UI (ko.observable → totals recalculate)
   │
   ├─ POST to /carts/mine/…  (storage.post)
   │
   ├─ server recomputes totals with the real rules
   │
   └─ quote.setTotals(response)   ← replaces the totals in the whole checkout
```

```javascript
storage.post(url, JSON.stringify({points: points}), false, 'application/json')
    .done(function (response) {
        quote.setTotals(response);            // every total on the page updates
        self.originalGrandTotal = (parseFloat(response.grand_total) || 0) + self.getDiscount();
        self.syncMessage($t('Loyalty discount applied.'));
    })
    .fail(function () {
        self.syncMessage($t('Unable to apply loyalty discount.'));
    })
    .always(function () {
        self.isSyncing(false);
    });
```

Two lessons in those twelve lines:

- **Optimistic UI, server truth.** The estimate is computed client-side for
  responsiveness, then replaced by the server's number. The discount is never
  *trusted* from the browser — the server recomputes it.
- **A failure is a message, not a crash.** `.fail()` shows the customer
  something; it must never leave the UI in a "loading" state.

### 7.6 The four states of every AJAX UI

```javascript
this.isSaving(true);            // 1. loading   → spinner, button disabled
// 2. success  → update the affected node only
// 3. empty    → "No store found" (store-locator.js sets noResults.hidden)
// 4. failure  → an inline message, the old value restored
```

`store-locator.js` is the only one of the four modules that handles the *empty*
state explicitly — the pattern to copy.

### 7.7 CSRF and secrets

- POST/PUT/DELETE REST calls from the storefront need the customer token;
  `mage/storage` attaches it. Raw `$.ajax` on a token-protected route returns
  `401 Unauthorized`.
- A private key in JS is **public** — anyone can read it in the page source.
  Only the server talks to payment providers, only it holds secrets.
- Client-side validation (`if (!title) return;`) is UX. Real validation and
  all authorization live in the PHP service layer.

---

## 8. KnockoutJS on the checkout

Where jQuery + DOM is used on catalog pages, checkout is built with Knockout:
**observables** instead of `textContent`, **bindings** instead of listeners.

### 8.1 Observables are variables the page watches

```javascript
this.pointsUsed    = ko.observable(0);
this.isSyncing     = ko.observable(false);
this.availableStores = ko.observableArray(config.availableStores || []);
this.syncMessage   = ko.observable('');
```

An observable is a **function**: `pointsUsed()` reads it, `pointsUsed(42)`
writes it. Writing triggers every binding that depends on it.

```html
<!-- store-pickup.html -->
<label>
    <input type="number" data-bind="value: pointsUsed, valueUpdate: 'input'">
</label>
<span data-bind="if: isVisible">Pickup at: <span data-bind="text: selectedStoreInfo"></span></span>
<span data-bind="visible: isSaving">Saving…</span>
<button data-bind="click: saveStore, disable: isSaving">Save</button>
```

`text:` writes through `textContent` (escaped), `visible:` toggles a class,
`enable/disable` toggles the disabled property. You never touch the DOM
manually in a component — that is the whole point of the binding.

### 8.2 Computed — derived values

```javascript
this.isPickupMethod = ko.computed(function () {
    var method = quote.shippingMethod();

    return !!(method && method['carrier_code'] === this.carrierCode);
}, this);

this.selectedStoreInfo = ko.computed(function () {
    var code = this.selectedSourceCode();
    var store = this.findStore(code);

    return store ? store.street + ', ' + store.city + ' - ' + store.phone : '';
}, this);
```

A computed re-evaluates automatically when its dependencies change. If the
customer switches to another carrier, `isPickupMethod()` becomes `false` and the
bindings hide the block — with no imperative code.

Note `method['carrier_code']`: the quote object has a key with an underscore,
so bracket notation is required.

### 8.3 Subscribe — reacting without binding

```javascript
this.pointsUsed.subscribe(function () {
    this.scheduleSync();
}, this);
```

The second argument is the `this` for the callback — the reason `subscribe`
accepts it.

### 8.4 The two ways to write a checkout component

```javascript
define([
    'ko',
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/url-builder',
    'mage/storage',
    'mage/translate'
], function (ko, Component, quote, urlBuilder, storage, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'AlpineCommerce_StorePickup/store-pickup'   // the .html file
        },

        initialize: function () {
            this._super();          // mandatory: builds the base component
            …
            return this;            // mandatory in a KO component
        }
    });
});
```

- `defaults.template` names the Knockout template file
  (`view/frontend/web/template/*.html`) — HTML **with `data-bind` attributes**,
  no `id` and no `<script>`.
- `this._super()` is non-negotiable: `abstract-total` sets up the totals
  registry, and skipping it breaks the summary silently.
- `return this;` at the end of `initialize` is required by Magento's component
  factory.
- `this.isVisible` is special: Knockout renders the component only while it is
  true, which is how a summary total disappears when its condition is not met.

---

## 9. Debugging

In order, from cheapest to most informative:

1. **Console errors** — `Uncaught ReferenceError: $ is not defined` means you
   used `$` without declaring `'jquery'` in `define()`. `TypeError: undefined
   is not a function` usually means `initialize()` was never called.
2. **Network tab, filter `xhr/fetch`** — the REST call, its status and its JSON
   body. `400` = your payload is wrong, `401` = CSRF token missing,
   `403` = `webapi.xml` ACL, `404` = the route does not exist.
3. **Inspect the element** — confirm the `data-mage-init` JSON parsed (a
   Magento-escaping bug breaks it silently) and that the ids match the `.phtml`.
4. **`knockoutUtils` / `ko.dataFor(el)`** in the console — dumps the component
   bound to a checkout element, the fastest way to see why a total is empty.
5. **Clear caches** after adding a JS file: `bin/magento setup:static-content:deploy -f`
   and a hard reload. A stale deployed `requirejs-config.js` is the classic
   "my module is not found" cause.

---

## 10. Checklist for a new component

1. `etc/webapi.xml` — the routes, with the right `<resources>`.
2. `view/frontend/web/js/<name>.js` — `define()`, `'use strict'`, one return shape.
3. `view/frontend/requirejs-config.js` — the module alias.
4. `view/frontend/templates/<name>.phtml` — the markup + `data-mage-init`,
   PHP values escaped.
5. `view/frontend/layout/<page>.xml` — place the block.
6. AJAX: `storage` or `$.ajax` with `JSON.stringify`, `.fail()` message,
   `.always()` re-enable, no `location.reload()`.
7. No `innerHTML` with customer data, no secrets, no hardcoded quote id.
8. Test: happy path, empty result, server error (DevTools → Offline), double
   click.

---

## 11. Quick reference

```javascript
// module
define(['jquery', 'mage/translate', 'mage/storage'],
function ($, $t, storage) {
    'use strict';

    return function (config, element) { /* (config, element) */ };
});

// entry point (in the .phtml)
<div data-mage-init='{"Vendor_Module/js/file": {"someUrl": "<?= $block->escapeUrl($url) ?>"}}'>

// alias (requirejs-config.js)
map: { '*': { 'Vendor_Module': 'Vendor/Module/view/frontend/web/js/file' } }

// AJAX
storage.post(url, JSON.stringify(payload), false, 'application/json')
    .done(fn).fail(fn).always(fn);

// checkout URLs
urlBuilder.createUrl('/carts/mine/action', {})   →  /rest/V1/carts/mine/action

// checkout totals
quote.setTotals(response);

// knockout
this.value = ko.observable(0);
this.derived = ko.computed(function () { … }, this);
this.value.subscribe(function (v) { … }, this);
```

---

## 12. Practice

1. **`store-locator.js`** — add a department filter using `closest` and
   `classList`, and handle the "no result" state without a page reload.
2. **`review-form.js`** — replace `window.location.reload()` with a DOM update
   of the reviews list, and add a `.fail()` message that displays
   `xhr.responseText` inline.
3. **`loyalty-points.js`** — remove the debounce and observe in the Network tab
   how many requests a 5-second typing burst produces.
4. **`store-pickup.js`** — add a `computed` that shows the distance or the
   opening hours of the selected store, and bind it with `text:`.
5. Write a new component from scratch (block + template + JS + REST route) for a
   newsletter subscribe box, following the checklist in section 10.

Deepen with the existing articles:
[`docs/magento2/js/01-requirejs.md`](../magento2/js/01-requirejs.md),
[`02-knockoutjs.md`](../magento2/js/02-knockoutjs.md),
[`03-magento-js-libs.md`](../magento2/js/03-magento-js-libs.md),
[`04-integrating-js.md`](../magento2/js/04-integrating-js.md),
and the deep dive
[`docs/magento2/magento-js.md`](../magento2/magento-js.md).

---

*Last updated: 2026-10-01.*