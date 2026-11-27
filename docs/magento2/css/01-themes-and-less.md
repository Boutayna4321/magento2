# Magento 2 from Scratch — Themes & LESS

> **Series**: [Learning Path](../README.md) → Phase 2 (CSS), Article 1 of 2
> **Previous**: [`../html/02-phtml-templates.md`](../html/02-phtml-templates.md) — PHTML templates
> **Next**: [`02-luma-vs-hyva.md`](02-luma-vs-hyva.md) — Luma vs Hyvä
> **Deep dive**: [`../magento-styles.md`](../magento-styles.md)

---

In Magento, **themes** define the look and feel. Unlike the HTML/CSS/JS you
might write for a plain website, Magento themes have a specific folder
structure, a compilation step for CSS (LESS), and a **fallback system**.

This article uses the `AlpineCommerce/LumaCheckout` theme (the file you have
open) as a real example.

---

## 1. Where themes live

Themes live in `app/design/frontend/<Vendor>/<Theme>/`:

```
app/design/
├── frontend/
│   ├── Magento/
│   │   ├── luma/              ← base theme (Composer-installed)
│   │   └── blank/             ← base theme (Composer-installed)
│   └── AlpineCommerce/
│       ├── hyva/              ← Hyvä storefront theme
│       └── LumaCheckout/      ← Luma checkout theme
```

Every theme has **three required files**:

```
LumaCheckout/
├── registration.php              ← registers the theme
├── theme.xml                    ← title, parent theme
└── web/
    └── css/
        └── source/
            └── _extend.less     ← your CSS overrides
```

### `registration.php`

```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::THEME,
    'frontend/AlpineCommerce/LumaCheckout',
    __DIR__
);
```

The second argument — `frontend/AlpineCommerce/LumaCheckout` — is the
**full path** of the theme. This exact string is used everywhere else
(fallback config, static file URLs, `config:set`).

### `theme.xml`

```xml
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <title>AlpineCommerce Luma Checkout</title>
    <parent>Magento/luma</parent>
</theme>
```

The `<parent>` is the **full theme path without the area**. `Magento/luma`
is how Luma is registered (lowercase `l`). This establishes the **fallback
chain**: if a file isn't found in LumaCheckout, Magento looks in Luma, then
Blank.

---

## 2. The fallback chain

```
Magento/blank         ← base: LESS architecture, UI library
      ↑
Magento/luma          ← demo design on top of Blank
      ↑
AlpineCommerce/LumaCheckout   ← your checkout override
```

When Magento needs a file (e.g., a template, a CSS file, a JS file), it
searches **from most specific to most generic** and uses the **first match**:

1. `AlpineCommerce/LumaCheckout/Magento_Checkout/templates/...`
2. `Magento/Luma/Magento_Checkout/templates/...`
3. `Magento/Blank/Magento_Checkout/templates/...`
4. `Magento/Checkout/view/frontend/templates/...`

This is why your theme only contains **one file** (`_extend.less`) — everything
else comes from the parent themes.

---

## 3. How CSS is loaded

Magento's Luma/Blank stack uses **LESS**, not plain CSS. The chain is:

```
_extend.less  (your overrides)
     ↑
_module.less  (module styles)
     ↑
_theme.less   (variable overrides)
     ↑
_styles.less  (UI library imports)
     ↑
styles-m.less  (entry point → styles-m.css)
```

### The entry points

There is **no** `style.css` file. Instead, Magento compiles three LESS files
into three CSS files:

| LESS source | CSS output | Media query |
|-------------|-----------|-------------|
| `styles-m.less` | `styles-m.css` | all devices (mobile-first) |
| `styles-l.less` | `styles-l.css` | `min-width: 768px` (desktop) |
| `print.less` | `print.css` | print |

### Where to put your CSS

| File | Behaviour | Use for |
|------|-----------|---------|
| `web/css/source/_theme.less` | **Replaces** parent's | Changing variables (colors, fonts) |
| `web/css/source/_extend.less` | **Added** via import | New rules or overrides (recommended) |
| `<Module>/web/css/source/_extend.less` | Module-scoped | Rules specific to one module |
| `<Module>/web/css/source/_module.less` | **Replaces** parent | Full rewrite (rare) |

The `_extend.less` you have open is the **recommended** place for customizations.
It is loaded *after* everything else via `@magento_import`, so your rules win.

---

## 4. Your `_extend.less` — a real example

The file you have open targets `.checkout-index-index` (the body class of the
checkout page):

```less
.checkout-index-index {
    .opc-wrapper {
        .step-title {
            border-bottom: 3px solid #006bb4;
            color: #006bb4;
        }
    }

    .action.primary {
        background: #006bb4;
    }

    .field {
        .control {
            input:focus,
            select:focus,
            textarea:focus {
                border-color: #006bb4;
            }
        }
    }

    .opc-block-summary {
        border-top: 3px solid #006bb4;
    }
}
```

**What this does**: it overrides the step title border, the primary button
background, the focus border on form fields, and the order summary border —
all scoped to the checkout page so nothing else is affected.

> The `#006bb4` color is AlpineCommerce's primary blue. The parent documentation
> lists [all available LESS variables](css/02-luma-vs-hyva.md) you can use
> instead of hardcoding colors.

---

## 5. LESS variables and the UI library

Magento's LESS uses **variables** and **mixins** from the UI library:

```less
// _theme.less
@primary__color: #006bb4;
@border-radius__base: 4px;
@font-size__base: 14px;

// _extend.less
.my-button {
    .lib-button-primary();  // mixin from UI library
    color: @primary__color;
}
```

The full list of available variables is in
`src/vendor/magento/lib/web/css/source/lib/variables/`.

The complete reference of existing Luma CSS classes is in
[`src/app/design/frontend/AlpineCommerce/LumaCheckout/docs/existing-classes-reference.md`](
../../../AlpineCommerce/LumaCheckout/docs/existing-classes-reference.md).
It documents every class the checkout inherits so you know what to target.

---

## 6. Compiling and deploying styles

### Development (server-side LESS)

Magento compiles LESS on the server. When you change `_extend.less`:

```bash
# Full cycle (recommended for development)
rm -rf pub/static/frontend/AlpineCommerce/LumaCheckout/*
rm -rf var/view_preprocessed/*
bin/magento setup:static-content:deploy -f -t AlpineCommerce/LumaCheckout en_US fr_FR
bin/magento cache:flush
```

### Production

In production mode, static files **must** be pre-generated. Run the same
`setup:static-content:deploy` command. Missing CSS = unstyled page.

---

## 7. What you should remember

1. **Themes** live in `app/design/frontend/<Vendor>/<Theme>/` with three
   required files: `registration.php`, `theme.xml`, and at least one CSS/JS.
2. **Fallback chain**: child → parent → grandparent → module. Files not in
   your theme come from parents.
3. **`_extend.less`** is the recommended place for CSS overrides (loaded last,
   wins by cascade).
4. LESS compiles to `styles-m.css`, `styles-l.css`, `print.css` —
   mobile-first with a desktop media query.
5. Use **variables** (`@primary__color`) and **mixins** (`.lib-button-primary()`)
   from the UI library instead of hardcoding values.
6. After changing CSS, clear `pub/static` and `var/view_preprocessed`, then
   redeploy.

---

## 8. Where to go next

- **Article 2**: [Luma vs Hyvä](02-luma-vs-hyva.md) — why AlpineCommerce uses two
  frontend stacks.
- **Phase 2 JS**: [`../js/01-requirejs.md`](../js/01-requirejs.md)
- **Previous**: [PHTML Templates](../html/02-phtml-templates.md)
- **Deep dive**: [`../magento-styles.md`](../magento-styles.md) — the complete
  2000-line guide to themes, Hyvä, Tailwind, and fallback configuration.

---

*Sources: `src/app/design/frontend/AlpineCommerce/LumaCheckout/theme.xml`,
`src/app/design/frontend/AlpineCommerce/LumaCheckout/web/css/source/_extend.less`,
`src/vendor/magento/theme-frontend-luma/web/css/source/styles-m.less`,
`src/app/design/frontend/AlpineCommerce/LumaCheckout/docs/existing-classes-reference.md`*
