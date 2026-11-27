# Magento 2 from Scratch — Integrating JavaScript

> **Series**: [Learning Path](../README.md) → Phase 2 (JS), Article 4 of 4
> **Previous**: [`03-magento-js-libs.md`](03-magento-js-libs.md) — Magento JS libraries
> **Next**: [`../core/06-events-observers.md`](../core/06-events-observers.md) — Events & Observers
> **Deep dive**: [`../magento-js.md`](../magento-js.md)

---

You have a JavaScript module defined with `define()`. Now you need to
**load it on a page**. Magento provides three mechanisms:

1. **`data-mage-init`** — from a `.phtml` template
2. **`x-magento-init`** — from a `<script>` tag in a `.phtml`
3. **Layout XML** — via UI Components (for complex components)

This article covers the first two, which you will use most often for custom
frontend code.

---

## 1. `data-mage-init` — the standard way

`data-mage-init` is an HTML attribute. Place it on any element in a `.phtml`:

```php
<!-- review-form.phtml -->
<div id="review-form"
     data-mage-init='{"AlpineCommerce_ProductReviews/js/review-form": {}}'>
    <!-- form fields -->
</div>
```

When the page loads, Magento:
1. Reads `data-mage-init`
2. Loads the specified RequireJS module
3. Calls its `initialize()` method (if it exists) with the element as context

### Passing configuration

```php
<div data-mage-init='{"my/component": {
    " productId": "<?= $block->escapeHtml($product->getId()) ?>",
    "ajaxUrl": "<?= $block->escapeUrl($block->getUrl('my/ajax')) ?>"
}}'>
```

```js
// my/component.js
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return {
        options: {},
        initialize: function (config, element) {
            this.options = config;

            $(element).on('click', function () {
                console.log('Product ID:', config.productId);
            });
        }
    };
});
```

---

## 2. `x-magento-init` — for multiple elements

When you need to initialize on multiple elements or outside a single root:

```php
<?php
// product-list.phtml
$items = $block->getProducts();
?>

<?php foreach ($items as $item): ?>
    <div class="product-item">
        <!-- product markup -->
    </div>
<?php endforeach; ?>

<script type="text/x-magento-init">
{
    ".product-item": {
        "AlpineCommerce_StoreLocator/js/product-map": {}
    }
}
</script>
```

`x-magento-init` targets **CSS selectors** (not a single element), so you can
initialize on multiple elements at once.

---

## 3. Layout XML — UI Components (admin and checkout)

For the admin panel and complex checkout components, you use UI Components
declared in Layout XML:

```xml
<!-- view/frontend/layout/checkout_index_index.xml -->
<referenceBlock name="checkout">
    <arguments>
        <argument name="js_layout" xsi:type="array">
            <item name="components" xsi:type="array">
                <item name="storePickup" xsi:type="array">
                    <item name="component" xsi:type="string">
                        AlpineCommerce_StorePickup/js/view/store-pickup
                    </item>
                    <item name="template" xsi:type="string">
                        AlpineCommerce_StorePickup/store-pickup
                    </item>
                </item>
            </item>
        </argument>
    </arguments>
</referenceBlock>
```

This is how the StorePickup module adds a KO component to the checkout sidebar.

---

## 4. How AlpineCommerce integrates JS

| Module | Integration | Pattern |
|--------|-------------|---------|
| **StorePickup** | UI Component in checkout layout XML | KO component in checkout sidebar |
| **ProductReviews** | `data-mage-init` in `.phtml` | jQuery AJAX form submission |
| **ProductQuestions** | `data-mage-init` in `.phtml` | jQuery AJAX form submission |
| **LoyaltyProgram** | UI Component in checkout layout XML | KO component + minicart plugin |
| **StoreLocator** | `data-mage-init` in `.phtml` | Vanilla JS (no RequireJS wrapper) |

### Real example: ProductReviews `data-mage-init`

```php
<!-- view/frontend/templates/review/form.phtml -->
<form id="review-form"
      data-mage-init='{"AlpineCommerce_ProductReviews/js/review-form": {}}'>
    <input id="review-title" type="text"/>
    <button type="submit">Submit Review</button>
</form>
```

```js
// view/frontend/web/js/review-form.js
define(['jquery', 'mage/storage', 'mage/translate'], function ($, storage, $t) {
    'use strict';

    return {
        initialize: function (config, element) {
            var self = this;
            $(element).on('submit', function (e) {
                e.preventDefault();

                var data = {
                    title: $('#review-title').val(),
                    detail: $('#review-detail').val()
                };

                storage.post(
                    '/rest/V1/alpinecommerce/product-reviews',
                    JSON.stringify(data),
                    false,
                    'application/json'
                ).done(function () {
                    alert($t('Review submitted'));
                });
            });
        }
    };
});
```

---

## 5. What you should remember

1. **`data-mage-init`** — use when initializing on a single element from a
   `.phtml` template.
2. **`x-magento-init`** — use when targeting multiple elements via CSS selector.
3. **Layout XML + UI Components** — required for checkout and admin
   integration; declares the component class and template.
4. Always pass configuration via the JSON object in the init attribute.
5. The module's `initialize(config, element)` function receives the config and
   the DOM element as arguments.
6. The `LumaCheckout` theme does **not** add any JavaScript — it inherits all
   checkout JS from the parent themes.

---

## 6. Where to go next

- **Phase 3**: [`../core/06-events-observers.md`](../core/06-events-observers.md)
  — How to hook into Magento's lifecycle
- **Previous**: [Magento JS libraries](03-magento-js-libs.md)
- **Deep dive**: [`../magento-js.md`](../magento-js.md) — exercises, debugging,
  common errors, and the full AlpineCommerce JS patterns table.

---

*Sources: `src/app/code/AlpineCommerce/ProductReviews/view/frontend/templates/review/form.phtml`,
`src/app/code/AlpineCommerce/ProductReviews/view/frontend/web/js/review-form.js`,
`src/app/code/AlpineCommerce/StorePickup/view/frontend/layout/checkout_index_index.xml`*
