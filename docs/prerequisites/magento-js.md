# Magento 2 — JavaScript: Complete Course

> **Objective**: understand and write JavaScript in Magento 2, from the
> first `define()` to the interactive component in the checkout.
> This course assumes you already know what a variable, a function,
> an object and a class are in JavaScript.

---

## Table of Contents

1. [Why does Magento have its own JavaScript?](#1)
2. [RequireJS — The module system](#2)
3. [KnockoutJS — The reactive interface](#3)
4. [jQuery — DOM and AJAX](#4)
5. [The `mage/*` libraries](#5)
6. [The 3 JS patterns in the project](#6)
7. [Integrate JS in Magento](#7)
8. [Debug JS in Magento](#8)
9. [Practical exercises](#9)
10. [Summary](#10)

---

## 1. Why does Magento have its own JavaScript? {#1}

### 1.1 The problem with "classic" JavaScript

```html
<!-- Old way: lots of <script> in the HTML -->
<script src="js/jquery.js"></script>
<script src="js/bootstrap.js"></script>
<script src="js/my-script.js"></script>
<script src="js/other-script.js"></script>
```

**Problems**:
- **Name conflicts**: two files declare `var $`
- **Critical order**: if `my-script.js` loads before `jquery.js`, it breaks
- **Unnecessary files**: the product page loads 500 KB of JS when it only needs 50 KB
- **No reusability**: impossible to share a component between two pages

### 1.2 The Magento solution: RequireJS + KnockoutJS

Magento 2 uses two tools:

| Tool | Role | Analogy |
|------|------|---------|
| **RequireJS** | Load scripts **on demand**, with explicit dependencies | A chef who only orders the necessary ingredients from the kitchen |
| **KnockoutJS** | Create **reactive** interfaces (when JS changes, HTML updates automatically) | A dashboard that updates automatically when data changes |

### 1.3 What you must remember

- Every Magento JS file starts with `define([...])`
- Complex components (checkout) use KnockoutJS
- Simple forms use jQuery
- Server calls go through `mage/storage` (never direct `$.ajax`)

---

## 2. RequireJS — The module system {#2}

### 2.1 What is an AMD module?

**AMD** = *Asynchronous Module Definition*. It is a way to declare
a JS file that:
- lists its **dependencies** (which other files must be loaded before it)
- returns what it **exposes** to other files

```js
// This JS file IS an AMD module
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    
    // Module code...
    
    return {
        // What others can use
        hello: function () {
            return $t('Hello');
        }
    };
});
```

### 2.2 The `define()` function — mandatory syntax

```js
define([
    'jquery',           // Dependency 1
    'mage/translate'    // Dependency 2
], function ($, $t) {   // Parameters: same order as dependencies
    'use strict';
    
    // Your code here
    
    return { /* public API */ };
});
```

**Strict rules**:
1. Parameter names **must** match the order of dependencies
2. `'use strict';` is **mandatory** in the first line of the body
3. The `return` exposes the public API (what is not returned is private)

### 2.3 Dependency paths

| Paths | Example | Explanation |
|---------|---------|-------------|
| **Short alias** | `'jquery'` | Native Magento module, available everywhere |
| **Magento module** | `'mage/storage'` | Magento library (AJAX, translation...) |
| **Custom module** | `'AlpineCommerce_StorePickup/js/view/store-pickup'` | Your own module |
| **Custom alias** | `'alphacommerceStorePickup'` | Defined in `requirejs-config.js` |

### 2.4 `requirejs-config.js` — the module map

Each module can create an alias to shorten paths:

```js
// StorePickup/view/frontend/requirejs-config.js
var config = {
    map: {
        '*': {
            // 'alphacommerceStorePickup' is now an alias for:
            alphacommerceStorePickup: 'AlpineCommerce_StorePickup/js/view/store-pickup'
        }
    }
};
```

Usage in a layout XML:
```xml
<item name="component" xsi:type="string">alphacommerceStorePickup</item>
```

### 2.5 `require()` — one-time execution

To execute code **only once** (not a reusable module):

```js
require(['jquery', 'mage/translate'], function ($, $t) {
    $(document).ready(function () {
        console.log($t('Page loaded'));
    });
});
```

**When to use it**: in a `.phtml` to initialize something.

---

## 3. KnockoutJS — The reactive interface {#3}

### 3.1 The binding concept

With KnockoutJS, you `bind` HTML to JavaScript:

```html
<!-- HTML -->
<input data-bind="value: userName">
<p data-bind="text: userName"></p>
```

```js
// JavaScript
this.userName = ko.observable('Alice');
```

**Result**:
- The input displays "Alice"
- If you change the input → the `<p>` updates automatically
- If you change `this.userName('Bob')` in JS → the input AND the `<p>` update

### 3.2 Observables (`ko.observable`)

An **observable** is a "reactive" variable: when its value changes,
everything bound to it updates.

```js
// DECLARATION
var count = ko.observable(0);          // number
var name = ko.observable('Alice');      // text
var isActive = ko.observable(true);     // boolean
var items = ko.observableArray([]);     // array

// READ → add parentheses ()
console.log(count());        // 0
console.log(name());         // 'Alice'

// WRITE → call like a function
count(10);
name('Bob');
isActive(false);
items.push({id: 1, title: 'Product 1'});
```

### 3.3 Computed (`ko.computed`)

A **computed** is a value **calculated automatically** from
other observables:

```js
var firstName = ko.observable('Alice');
var lastName = ko.observable('Dupont');

// This computed AUTOMATICALLY recalculates
// when firstName or lastName changes
var fullName = ko.computed(function () {
    return firstName() + ' ' + lastName();
});

console.log(fullName()); // 'Alice Dupont'

firstName('Bob');
console.log(fullName()); // 'Bob Dupont' (recalculated automatically)
```

### 3.4 Bindings in HTML

| Binding | Role | Example |
|---------|------|---------|
| `text` | Display text | `data-bind="text: userName"` |
| `value` | Bind an input to a variable | `data-bind="value: count"` |
| `visible` | Show/hide | `data-bind="visible: count() > 0"` |
| `click` | Button click | `data-bind="click: increment"` |
| `event` | Other events | `data-bind="event: {change: save}"` |
| `options` | Dropdown list | `data-bind="options: items, optionsText: 'name'"` |
| `foreach` | Loop over a list | `data-bind="foreach: items"` |
| `i18n` | Translate | `data-bind="i18n: 'Hello'"` |
| `attr` | Dynamic HTML attribute | `data-bind="attr: {for: inputId}"` |

### 3.5 Complete example: StorePickup

**JavaScript** (`store-pickup.js`):
```js
define(['ko', 'mage/storage', 'mage/translate'], function (ko, storage, $t) {
    'use strict';
    
    return {
        initialize: function () {
            // 1. Observables (state)
            this.availableStores = ko.observableArray([]);
            this.selectedStore = ko.observable('');
            this.isSaving = ko.observable(false);
            this.message = ko.observable('');
            
            // 2. Computed (derived)
            this.isVisible = ko.computed(function () {
                return this.selectedStore() !== '';
            }, this);
            
            this.selectedStoreName = ko.computed(function () {
                var code = this.selectedStore();
                var store = this.findStore(code);
                return store ? store.name : '';
            }, this);
        },
        
        findStore: function (code) {
            return this.availableStores().find(function (s) {
                return s.code === code;
            });
        },
        
        saveStore: function () {
            var self = this;
            this.isSaving(true);
            
            storage.post('/carts/mine/store-pickup', 
                JSON.stringify({sourceCode: this.selectedStore()}), 
                false, 
                'application/json'
            ).done(function () {
                self.message($t('Store saved'));
            }).fail(function () {
                self.message($t('Error saving store'));
            }).always(function () {
                self.isSaving(false);
            });
        }
    };
});
```

**HTML** (`store-pickup.html`):
```html
<div data-bind="visible: isVisible">
    <label data-bind="i18n: 'Choose your store'"></label>
    
    <select data-bind="
        options: availableStores,
        optionsText: 'name',
        optionsValue: 'code',
        value: selectedStore,
        event: {change: saveStore}">
    </select>
    
    <p data-bind="text: selectedStoreName"></p>
    <p data-bind="text: message, visible: message"></p>
</div>
```

**Flow**:
1. The user changes the `<select>`
2. Knockout updates `selectedStore`
3. The `event: {change: saveStore}` triggers `saveStore()`
4. `saveStore()` calls the REST via `mage/storage`
5. The server responds → `message` is updated
6. The `<p data-bind="text: message">` updates **automatically**

---

## 4. jQuery — DOM and AJAX {#4}

### 4.1 jQuery in Magento

jQuery is available via RequireJS. **Never use `$` globally**:

```js
// ✅ Correct
define(['jquery'], function ($) {
    'use strict';
    $('#my-element').click(function () { ... });
});

// ❌ Wrong (does not work in Magento)
$('#my-element').click(function () { ... });
```

### 4.2 DOM selection and manipulation

```js
define(['jquery'], function ($) {
    'use strict';
    
    // Select
    var $btn = $('#submit-btn');           // by ID
    var $form = $('.review-form');         // by class
    var $item = $('[data-product-id="5"]'); // by attribute
    
    // Read values
    var title = $('#review-title').val();
    var rating = $('input[name="rating"]:checked').val();
    
    // Modify DOM
    $btn.prop('disabled', true);
    $form.addClass('loading');
    $('#result').html('<p>Done</p>');
    
    // Events
    $btn.on('click', function () {
        console.log('Clicked');
    });
    
    // Delegated (for dynamic elements)
    $(document).on('click', '.vote-btn', function () {
        var reviewId = $(this).data('review-id');
    });
});
```

### 4.3 AJAX with `$.ajax`

```js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    
    function submitReview() {
        var data = {
            productId: parseInt($('#product-id').val()),
            title: $('#review-title').val(),
            detail: $('#review-detail').val()
        };
        
        $.ajax({
            url: '/rest/V1/alphacommerce/product-reviews',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function (response) {
                alert($t('Review submitted'));
            },
            error: function (xhr) {
                alert($t('Error') + ': ' + xhr.responseText);
            }
        });
    }
});
```

---

## 5. The `mage/*` libraries {#5}

Magento provides utilities wrapped under the `mage/` namespace.

### 5.1 `mage/storage` — Secure AJAX

```js
define(['mage/storage'], function (storage) {
    'use strict';
    
    // POST with JSON
    storage.post(
        '/rest/V1/cart',           // URL
        JSON.stringify(data),       // Request body
        false,                      // parallel (no loading overlay)
        'application/json'          // Content-Type
    ).done(function (response) {
        // Success
    }).fail(function (xhr) {
        // Error
    });
    
    // GET
    storage.get('/rest/V1/products/1', false)
        .done(function (product) { ... });
    
    // PUT
    storage.put('/rest/V1/cart/1', JSON.stringify(data), false, 'application/json')
        .done(function () { ... });
    
    // DELETE
    storage.delete('/rest/V1/cart/1', false)
        .done(function () { ... });
});
```

**Why `mage/storage` instead of `$.ajax`?**
- Automatically handles session cookies
- Handles 401 errors (redirect to login)
- Adds the `X-Requested-With: XMLHttpRequest` header
- Formats Magento errors

### 5.2 `mage/translate` — Client-side translation

```js
define(['mage/translate'], function ($t) {
    'use strict';
    
    var msg = $t('Hello World');
    // Looks for translation in i18n/fr_FR.csv, i18n/de_DE.csv, etc.
    
    // With parameters
    var msg2 = $t('Hello %1, you have %2 products').replace('%1', 'Alice').replace('%2', '5');
});
```

### 5.3 `mage/mage` — jQuery UI initialization

```js
define(['jquery', 'mage/mage'], function ($) {
    'use strict';
    
    // Initializes Magento components on an element
    $('#my-form').mage('validation', { /* validation rules */ });
});
```

### 5.4 `mage/utils` — Utilities

```js
define(['mage/utils'], function (utils) {
    'use strict';
    
    // Serialize an object to query string
    var query = utils.stringify({page: 1, limit: 10});
    // → "page=1&limit=10"
    
    // Deep clone an object
    var copy = utils.copy(original);
});
```

---

## 6. The 3 JS patterns in the project {#6}

### 6.1 Pattern 1: UI Component + KnockoutJS

**Used for**: checkout, complex interactive components

**Modules**: StorePickup, LoyaltyProgram

**Structure**:
```
view/frontend/
├── requirejs-config.js       # Module alias
├── web/
│   ├── js/
│   │   └── view/
│   │       └── store-pickup.js   # KO component (observables, computed)
│   └── template/
│       └── store-pickup.html     # KO template (data-bind)
```

**Characteristics**:
- `define(['ko', ...], function (ko, ...)`
- Returns an object with `initialize()` (Magento pattern)
- Observables for state, computed for derived values
- HTML template with `data-bind`
- Integrated via layout XML (`js_config/component`)

### 6.2 Pattern 2: jQuery + AJAX

**Used for**: simple forms, one-time interactions

**Modules**: ProductReviews, ProductQuestions

**Structure**:
```
view/frontend/
├── web/
│   ├── js/
│   │   └── review-form.js       # jQuery init, events
│   └── templates/
│       └── review_form.phtml    # HTML + data-mage-init
```

**Characteristics**:
- `define(['jquery', ...], function ($, ...)`
- Returns an object with `init()`
- jQuery events (`$('#id').on('click', ...)`)
- AJAX with `$.ajax` or `mage/storage`
- Initialized via `data-mage-init` in the `.phtml`

### 6.3 Pattern 3: Vanilla JS (lightweight)

**Used for**: filters, client-side search

**Modules**: StoreLocator

**Structure**:
```
view/frontend/
└── web/
    └── js/
        └── store-locator.js     # No jQuery, no KO
```

**Characteristics**:
- `define(['mage/translate'], function ($t) { ... })`
- Returns a function `(config, element) => { ... }`
- Native DOM (`querySelector`, `addEventListener`)
- No heavy dependencies

---

## 7. Integrate JS in Magento {#7}

### 7.1 Method 1: `data-mage-init` in a `.phtml`

```php
<!-- review_form.phtml -->
<div id="review-form"
     data-mage-init='{"reviewForm": {"submitBtnSelector": "#submit-review"}}'>
    <button id="submit-review">Submit</button>
</div>

<script type="text/x-magento-init">
{
    "#review-form": {
        "reviewForm": {
            "submitBtnSelector": "#submit-review"
        }
    }
}
</script>
```

Magento loads `reviewForm` and calls `init(submitBtnSelector)`.

### 7.2 Method 2: Layout XML (UI components)

```xml
<referenceContainer name="product.info.main">
    <block class="Magento\Framework\View\Element\Template"
           name="store.pickup"
           template="AlpineCommerce_StorePickup::store-pickup.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceStorePickup</item>
            </argument>
            <argument name="data" xsi:type="array">
                <item name="availableStores" xsi:type="object">
                    AlpineCommerce\StorePickup\Block\Adminhtml\Store\Source\StoreInfo
                </item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

### 7.3 Method 3: `requirejs-config.js` + KO Component

```xml
<!-- checkout_index_index.xml -->
<referenceContainer name="checkout.cart.totals">
    <block class="Magento\Checkout\Block\Cart\Totals"
           name="loyalty.points"
           template="AlpineCommerce_LoyaltyProgram::points.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceLoyaltyPoints</item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

The KO component is automatically instantiated by Magento.

---

## 8. Debug JS in Magento {#8}

### 8.1 Chrome DevTools

```
F12 → Console
```

**View loaded RequireJS modules**:
```js
require.s.contexts._.defined
// Displays all loaded modules with their exports
```

**Test a module**:
```js
require(['AlpineCommerce_StorePickup/js/view/store-pickup'], function (Module) {
    console.log(Module);
});
```

### 8.2 Common errors

| Error | Cause | Solution |
|--------|-------|----------|
| `Uncaught Error: Module name ... has not been loaded yet` | Dependency misspelled | Check the name in `define([...])` |
| `$ is not a function` | jQuery not loaded or badly injected | Check the order of parameters in `define()` |
| `ko is not defined` | Knockout not declared as dependency | Add `'ko'` in `define([...])` |
| `define is not defined` | File not loaded via RequireJS | Use `define()`, no inline `<script>` |
| `data-bind` does not work | KO template not linked to the component | Check `template:` and `component:` in the layout |

### 8.3 Enable RequireJS errors

```js
// In the browser console
requirejs.onError = function (err) {
    console.error('RequireJS error:', err.requireModules);
};
```

### 8.4 View JS network

```
F12 → Network → Filter by "JS"
```

Allows you to see which files are loaded, in what order, and if a
file is missing (404).

---

## 9. Practical exercises {#9}

### Exercise 1: First RequireJS module

**Objective**: create a module that displays "Hello Magento" in a `<div>`.

**Steps**:
1. Create `src/app/code/AlpineCommerce/Blog/view/frontend/web/js/hello.js`
2. Create a template `src/app/code/AlpineCommerce/Blog/view/frontend/templates/hello.phtml`
3. Add a layout `view/frontend/layout/blog_index_index.xml`
4. Add `data-mage-init` in the template

**Solution**:
```js
// hello.js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';
    return {
        run: function () {
            $('#hello-container').html('<p>' + $t('Hello Magento') + '</p>');
        }
    };
});
```

```html
<!-- hello.phtml -->
<div id="hello-container"></div>
<script type="text/x-magento-init">
{
    "#hello-container": {
        "AlpineCommerce_Blog/js/hello": {}
    }
}
</script>
```

### Exercise 2: Simple KO observable

**Objective**: create a counter with a + and - button.

**Solution**:
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

```html
<div data-bind="with: $parent">
    <p>Count: <span data-bind="text: count"></span></p>
    <button data-bind="click: increment">+</button>
    <button data-bind="click: decrement">-</button>
</div>
```

### Exercise 3: AJAX to REST

**Objective**: submit a form and display the response.

**Solution**:
```js
define(['jquery', 'mage/storage', 'mage/translate'], function ($, storage, $t) {
    'use strict';
    return {
        submit: function () {
            var data = {
                title: $('#title').val(),
                detail: $('#detail').val()
            };
            storage.post('/rest/V1/alphacommerce/product-reviews',
                JSON.stringify(data), false, 'application/json'
            ).done(function () {
                alert($t('Submitted'));
            });
        }
    };
});
```

### Exercise 4: KO Computed

**Objective**: calculate the total price (quantity × unit price) in real time.

**Solution**:
```js
define(['ko'], function (ko) {
    'use strict';
    return function () {
        this.quantity = ko.observable(1);
        this.unitPrice = 29.99;
        this.total = ko.computed(function () {
            return (this.quantity() * this.unitPrice).toFixed(2);
        }, this);
    };
});
```

```html
<input type="number" data-bind="value: quantity, valueUpdate: 'input'">
<p>Total: $<span data-bind="text: total"></span></p>
```

---

## 10. Summary {#10}

| Concept | Magento equivalent | AlpineCommerce example |
|---------|-------------------|------------------------|
| JS Module | `define([...], function (...) { ... })` | All `.js` files |
| Modular loading | RequireJS | `requirejs-config.js` |
| Reactive UI | KnockoutJS | StorePickup, LoyaltyProgram |
| DOM + AJAX | jQuery | ProductReviews, ProductQuestions |
| Secure AJAX | `mage/storage` | StorePickup `saveStore()` |
| Translation | `mage/translate` | All modules |
| Initialization | `data-mage-init` | `review_form.phtml` |
| UI Component | `js_config/component` | Layout XML checkout |
| Observable | `ko.observable()` | `selectedStore`, `pointsUsed` |
| Computed | `ko.computed()` | `isVisible`, `totalPrice` |
| Template | `web/template/*.html` | `store-pickup.html` |
| Alias | `requirejs-config.js` | `alphacommerceStorePickup` |

### What to master

1. **`define([...], function (...) { ... })`** — mandatory structure of every Magento JS file
2. **`ko.observable()`** — for reactive variables
3. **`ko.computed()`** — for calculated values
4. **`mage/storage`** — for REST calls
5. **`mage/translate`** — for translations
6. **`data-bind`** — to bind HTML to JS
7. **`data-mage-init`** — to initialize a component from a `.phtml`
8. **Layout XML** — to integrate a UI component into a page

### Next steps

- Read AlpineCommerce JS files: `StorePickup/view/frontend/web/js/view/store-pickup.js`
- Create a simple JS module (exercise 1)
- Add a KO component in the checkout
- Explore the `mage/*` files in `lib/web/`

---

*Last updated: 2026-08-11.*
