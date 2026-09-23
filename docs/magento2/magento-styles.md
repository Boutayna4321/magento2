# Magento 2 / Adobe Commerce — Themes & Styles: Luma, Hyvä, Luma Fallback and Custom Themes

> **Audience**: Magento 2 / Adobe Commerce developers who need to understand,
> create or customize a storefront theme.
>
> **Goal**: after reading this document you should be able to:
>
> 1. Explain how the Magento theme system works (registration, inheritance, file fallback).
> 2. Customize a **Luma**-based theme with LESS.
> 3. Understand how **Hyvä** replaces Luma with Tailwind CSS and Alpine.js.
> 4. Create a **Hyvä child theme**.
> 5. Configure the **Hyvä Luma theme fallback** and create a **custom Luma theme used as fallback**.
> 6. Deploy and debug styles.
>
> Every code sample is either taken from the official documentation or copied
> from the real source code in this repository (`src/vendor/...`). Official
> references are listed at the end of each section and in [§11](#11-official-sources).
> Section [§10](#10-concrete-example-alpinecommerce) shows how all of this is applied in this project.

---

## Table of Contents

1. [Which theme approach should I use?](#1-which-theme-approach-should-i-use)
2. [Core concepts of the Magento theme system](#2-core-concepts-of-the-magento-theme-system)
3. [Luma and Blank (LESS)](#3-luma-and-blank-less)
4. [How to create a custom theme that extends Luma](#4-how-to-create-a-custom-theme-that-extends-luma)
5. [Hyvä (Tailwind CSS + Alpine.js)](#5-hyvä-tailwind-css--alpinejs)
6. [How to create a Hyvä child theme](#6-how-to-create-a-hyvä-child-theme)
7. [Hyvä Luma theme fallback](#7-hyvä-luma-theme-fallback)
8. [Deployment, cache and debugging](#8-deployment-cache-and-debugging)
9. [Luma vs Hyvä: summary](#9-luma-vs-hyvä-summary)
10. [Concrete example: AlpineCommerce](#10-concrete-example-alpinecommerce)
11. [Official sources](#11-official-sources)

---

## 1. Which theme approach should I use?

| Situation | Approach | Section |
|-----------|----------|---------|
| Classic Magento store, jQuery/RequireJS/Knockout ecosystem | Child theme of **Luma** (or **Blank**) | [§4](#4-how-to-create-a-custom-theme-that-extends-luma) |
| Performance-oriented store, Tailwind CSS + Alpine.js | Child theme of **Hyvä/default** | [§6](#6-how-to-create-a-hyvä-child-theme) |
| Store runs on Hyvä, but some pages (typically the checkout) must keep Luma | Hyvä + **Luma theme fallback** + a Luma child theme for those pages | [§7](#7-hyvä-luma-theme-fallback) |

> **Rule of thumb**: never edit files in `vendor/`. Every customization
> happens in a theme under `app/design/frontend/<Vendor>/<theme>/` (or in a module).
> Files in `vendor/` are overwritten by `composer install/update`.

---

## 2. Core concepts of the Magento theme system

### 2.1 What is a theme?

A theme is a component (like a module) that changes the **look** of the
storefront or admin: templates (`.phtml`), layout XML, CSS/LESS, JS, images,
fonts and translations. A theme belongs to one **area**: `frontend` or `adminhtml`.

Themes live in one of two places:

| Location | Used for |
|----------|----------|
| `app/design/frontend/<Vendor>/<theme>/` | Project themes (your code) |
| `vendor/<vendor>/<package>/` | Themes installed with Composer (Luma, Blank, Hyvä…) |

### 2.2 The three files that make a theme

**1. `registration.php`** — declares the theme to Magento. The second argument
is the theme *full path* `<area>/<Vendor>/<theme>`; it is case-sensitive and
is the identifier used everywhere else (parent declaration, fallback config, static files).

```php
<?php
use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/<Vendor>/<theme>', __DIR__);
```

Real example — Luma registers itself as `frontend/Magento/luma` (lowercase `luma`):

```php
// src/vendor/magento/theme-frontend-luma/registration.php
ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/Magento/luma', __DIR__);
```

**2. `theme.xml`** — title and (optional) parent theme:

```xml
<!-- src/vendor/magento/theme-frontend-luma/theme.xml -->
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd">
    <title>Magento Luma</title>
    <parent>Magento/blank</parent>
    <media>
        <preview_image>media/preview.jpg</preview_image>
    </media>
</theme>
```

The `<parent>` value is `<Vendor>/<theme>` **exactly as registered** (without the area).

**3. `composer.json`** — optional, only needed to distribute the theme as a package.

`etc/view.xml` is **not** required: it configures product image sizes and
theme variables (gallery, etc.), and is inherited from the parent. Copy the
parent's `etc/view.xml` only if you need to change these values.
It does **not** include CSS files (see [§2.5](#25-how-css-is-included-on-the-page)).

> Official: [Create a storefront theme](https://developer.adobe.com/commerce/frontend-core/guide/themes/create-storefront)

### 2.3 Theme directory structure

```
app/design/frontend/<Vendor>/<theme>/
├── registration.php
├── theme.xml
├── composer.json                 (optional)
├── etc/view.xml                  (optional — image sizes, theme vars)
├── media/preview.jpg             (optional — admin preview)
├── i18n/<locale>.csv             (theme translations, e.g. fr_FR.csv)
├── web/                          (theme-wide static files)
│   ├── css/source/               (LESS partials: _theme.less, _extend.less…)
│   ├── fonts/  images/  js/
└── <Namespace>_<Module>/         (module-specific overrides)
    ├── layout/                   (layout XML, e.g. default.xml)
    ├── templates/                (.phtml overrides)
    └── web/css/source/           (module LESS, e.g. _module.less)
```

Example: to override the Luma header template of `Magento_Theme`, create
`<theme>/Magento_Theme/templates/html/header.phtml`.

> Official: [Theme structure](https://developer.adobe.com/commerce/frontend-core/guide/themes/structure)

### 2.4 Theme inheritance and file fallback

When Magento needs a file (template, layout, static file), it searches from the
most specific to the most generic location and uses the **first match**:

**Templates** (e.g. `Magento_Catalog::product/view.phtml`):

1. `<current theme>/Magento_Catalog/templates/product/view.phtml`
2. `<parent theme>/Magento_Catalog/templates/...` (and so on up the parent chain)
3. `<module>/view/frontend/templates/product/view.phtml`
4. `<module>/view/base/templates/product/view.phtml`

**Static files** (CSS/LESS/JS/images) follow the same logic with `web/` directories.

**Layout XML is different**: layout files are **merged**, not replaced. A
`default.xml` in your theme *extends* the parent/module ones. To fully replace
a layout file you must put it in an `override/` directory (rarely needed).

This is why a child theme only contains the files it changes — everything
else comes from the parent.

```
Magento/blank  ← base theme, no parent
     ↑
Magento/luma   ← parent: Magento/blank
     ↑
<Vendor>/<your-luma-theme>   ← parent: Magento/luma
```

> Official: [Theme inheritance](https://developer.adobe.com/commerce/frontend-core/guide/themes/inheritance),
> [Override templates](https://developer.adobe.com/commerce/frontend-core/guide/templates/override),
> [Extend layouts](https://developer.adobe.com/commerce/frontend-core/guide/layouts/extend),
> [Override layouts](https://developer.adobe.com/commerce/frontend-core/guide/layouts/override)

### 2.5 How CSS is included on the page

Stylesheets are included through **layout XML**, usually in
`<theme>/Magento_Theme/layout/default_head_blocks.xml` (loaded on every page).

Blank (inherited by Luma):

```xml
<!-- src/vendor/magento/theme-frontend-blank/Magento_Theme/layout/default_head_blocks.xml -->
<head>
    <css src="css/styles-m.css"/>
    <css src="css/styles-l.css" media="screen and (min-width: 768px)"/>
    <css src="css/print.css" media="print"/>
    <meta name="format-detection" content="telephone=no"/>
</head>
```

Hyvä:

```xml
<!-- src/vendor/hyva-themes/magento2-default-theme/Magento_Theme/layout/default_head_blocks.xml -->
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <css src="css/styles.css"/>
</head>
```

`css/styles.css` is a path **relative to the theme's `web/` directory** and is
resolved with the fallback of §2.4. A child theme that provides its own
`web/css/styles.css` therefore replaces the parent's file without touching the layout.

If the `.css` file does not exist, Magento looks for a `.less` file with the
same name and compiles it (this is how `styles-m.css` is produced from `styles-m.less`).

> Official: [Include CSS](https://developer.adobe.com/commerce/frontend-core/guide/css/themes)

### 2.6 Applying a theme

A theme is registered in the `theme` database table when you run
`bin/magento setup:upgrade` (or open the admin). It is then assigned per
website / store view in:

**Admin → Content → Design → Configuration → (store view) → Applied Theme**

This writes `design/theme/theme_id` in `core_config_data`. Prefer the admin (or
`bin/magento config:set` / `app/etc/config.php`) over manual SQL.

> Official: [Apply and configure a theme in Admin](https://developer.adobe.com/commerce/frontend-core/guide/themes/apply-admin)

---

## 3. Luma and Blank (LESS)

### 3.1 Blank vs Luma

| Theme | Package | Parent | Purpose |
|-------|---------|--------|---------|
| `Magento/blank` | `magento/theme-frontend-blank` | none | Minimal base theme, contains the LESS architecture |
| `Magento/luma` | `magento/theme-frontend-luma` | `Magento/blank` | Demo/reference design built on Blank |

Both use **LESS**, the **Magento UI library** (`lib/web/css/source/lib`),
**RequireJS**, **jQuery** and **Knockout.js** (checkout, minicart, customer sections).

### 3.2 LESS entry points

There is **no** `styles.less` file. Blank defines three root files, compiled to three CSS files:

| Source (`web/css/`) | Output | Loaded for |
|--------------------|--------|-----------|
| `styles-m.less` | `styles-m.css` | all devices (mobile first) |
| `styles-l.less` | `styles-l.css` | `min-width: 768px` |
| `print.less` | `print.css` | print |

Extract of the real `styles-m.less`:

```less
// src/vendor/magento/theme-frontend-blank/web/css/styles-m.less
@import 'source/_reset.less';
@import '_styles.less';
//@magento_import 'source/_module.less'; // Theme modules
//@magento_import 'source/_widgets.less'; // Theme widgets
@import 'source/_theme.less';
//@magento_import 'source/_extend.less';
@import 'source/lib/_responsive.less';
```

### 3.3 The `@magento_import` directive

Standard `@import` includes **one** file. `@magento_import` includes **every
file with that name** found in the theme chain and in all enabled modules. It
must be written as a comment (`//@magento_import`) so the LESS compiler does
not fail on it; Magento's preprocessor replaces it with real `@import` lines.

So `//@magento_import 'source/_module.less';` collects, for example,
`Magento_Checkout/web/css/source/_module.less`,
`Magento_Catalog/web/css/source/_module.less`, etc.

### 3.4 Where to put your customizations

| File (in your theme) | Behaviour | Use it for |
|----------------------|-----------|-----------|
| `web/css/source/_theme.less` | **Replaces** the parent's `_theme.less` | Changing **variables** (colors, fonts…). Copy the parent file first, then edit, otherwise you lose the parent's variables |
| `web/css/source/_extend.less` | **Added** via `@magento_import` | New or overriding **CSS rules** — the recommended place for most customizations |
| `<Module>/web/css/source/_extend.less` | Added, module-scoped | Rules related to one module (e.g. `Magento_Checkout`) |
| `<Module>/web/css/source/_module.less` | **Replaces** the parent/module file | Full rewrite of a module's styles (rare) |

Real Luma variables (`_theme.less` uses the UI-library variable naming, e.g.):

```less
// src/vendor/magento/theme-frontend-luma/web/css/source/_theme.less
@link__color: @theme__color__primary-alt;
@focus__color: @color-blue3;
@border-color__base: @color-gray80;
```

The full list of available variables is in `lib/web/css/source/lib/variables/`.

### 3.5 LESS compilation modes

Configured in **Stores → Configuration → Advanced → Developer → Frontend development workflow**:

| Mode | How | When |
|------|-----|------|
| **Server-side** (default) | PHP LESS compiler, output to `pub/static/frontend/<Vendor>/<theme>/<locale>/css/` | Always in production (only option) |
| **Client-side** | `less.js` in the browser, compiled on each page load | Local development only (slow) |

Intermediate files are written to `var/view_preprocessed/`. Compilation errors
go to `var/log/system.log`. A faster option during development is Grunt
(`Gruntfile.js.sample` in the Magento root).

> Official: [CSS preprocessing](https://developer.adobe.com/commerce/frontend-core/guide/css/preprocess),
> [UI library](https://developer.adobe.com/commerce/frontend-core/guide/css/ui-library),
> [Debug CSS](https://developer.adobe.com/commerce/frontend-core/guide/css/debug)

---

## 4. How to create a custom theme that extends Luma

This procedure is used both for a regular Luma store and for a **Luma theme used as Hyvä fallback** ([§7](#7-hyvä-luma-theme-fallback)).
Example names: vendor `Acme`, theme `MyLuma`.

### Step 1 — Create the directory

```
app/design/frontend/Acme/MyLuma/
```

### Step 2 — `registration.php`

```php
<?php
use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/Acme/MyLuma', __DIR__);
```

### Step 3 — `theme.xml`

```xml
<?xml version="1.0"?>
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd">
    <title>Acme My Luma</title>
    <parent>Magento/luma</parent>
</theme>
```

> ⚠️ Write the parent **exactly** as registered: `Magento/luma` (lowercase `l`).

### Step 4 — Add styles

Theme-wide rules:

```less
// app/design/frontend/Acme/MyLuma/web/css/source/_extend.less
.page-header {
    background: #f5f5f5;
}
```

Module-scoped rules (e.g. checkout only):

```less
// app/design/frontend/Acme/MyLuma/Magento_Checkout/web/css/source/_extend.less
.checkout-index-index .action.primary {
    background: #006bb4;
}
```

To change variables, copy `vendor/magento/theme-frontend-luma/web/css/source/_theme.less`
to `web/css/source/_theme.less` and edit it (see §3.4).

### Step 5 — (Optional) override templates, layouts, logo

- Template: `Magento_Theme/templates/html/header.phtml`
- Layout: `Magento_Theme/layout/default.xml`
- Logo: `web/images/logo.svg` (picked up automatically with this name)

### Step 6 — Register and deploy

```bash
bin/magento setup:upgrade          # registers the theme in the `theme` table
bin/magento cache:flush
# production (or to pre-generate in developer mode):
bin/magento setup:static-content:deploy -f -t Acme/MyLuma en_US fr_FR
```

### Step 7 — Use the theme

- **Regular Luma store**: assign it in Content → Design → Configuration (§2.6).
- **Hyvä fallback theme**: do **not** assign it to a store view; configure it
  as the fallback theme path instead ([§7.4](#74-using-a-custom-luma-theme-as-fallback)).

### Checklist

- [ ] `registration.php` path = `frontend/<Vendor>/<theme>`
- [ ] `<parent>Magento/luma</parent>` (lowercase)
- [ ] Theme visible in `theme` table after `setup:upgrade`
- [ ] Generated CSS exists in `pub/static/frontend/<Vendor>/<theme>/<locale>/css/styles-m.css`
- [ ] Your rule is present in that generated file

---

## 5. Hyvä (Tailwind CSS + Alpine.js)

### 5.1 What Hyvä is

Hyvä is a commercial frontend for Magento 2 that **replaces the Luma frontend stack**:

| Luma stack | Replaced by in Hyvä |
|-----------|---------------------|
| LESS + UI library | **Tailwind CSS** (v4 in Hyvä 1.5) |
| jQuery, RequireJS, Knockout, UI components | **Alpine.js** (+ Magewire for some features) |
| Luma templates/layouts | Hyvä's own templates in `Hyva/default` |

It does not modify Magento core; it is a set of Composer packages.

### 5.2 Hyvä packages

| Package | Registers | Role |
|---------|-----------|------|
| `hyva-themes/magento2-default-theme` | **theme** `frontend/Hyva/default` | Templates, layouts, Tailwind sources, compiled `styles.css` |
| `hyva-themes/magento2-theme-module` | **module** `Hyva_Theme` | PHP side: view models, helpers, `hyva:*` CLI commands |
| `hyva-themes/magento2-base-layout-reset` | module `Hyva_BaseLayoutReset` | Removes Luma layout instructions (generated base layout) |
| `hyva-themes/magento2-compat-module-fallback` | module `Hyva_CompatModuleFallback` | Lets "compatibility modules" override third-party templates for Hyvä |
| `hyva-themes/magento2-theme-fallback` | module `Hyva_ThemeFallback` | Luma theme fallback for chosen URLs ([§7](#7-hyvä-luma-theme-fallback)) |
| `hyva-themes/magento2-luma-checkout` | module `Hyva_LumaCheckout` | Preset config of the fallback for the checkout |

Real registration of the theme (it is a **THEME**, not a module):

```php
// src/vendor/hyva-themes/magento2-default-theme/registration.php
ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/Hyva/default', __DIR__);
```

### 5.3 `Hyva/default` has no parent

```xml
<!-- src/vendor/hyva-themes/magento2-default-theme/theme.xml -->
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd">
    <title>Hyvä Default</title>
    <media>
        <preview_image>media/preview.png</preview_image>
    </media>
</theme>
```

`Hyva/default` does **not** extend Luma or Blank. Luma templates, LESS and JS
are never loaded on Hyvä pages. Module templates (`view/frontend/templates`)
are still in the fallback chain, which is why third-party modules often need a
**Hyvä compatibility module**.

```
Magento/blank ← Magento/luma            (Luma world)

Hyva/default  ← <Vendor>/<hyva-child>   (Hyvä world, independent)
```

Check how the base layout is reset:

```bash
bin/magento hyva:base-layout-resets:info
```

### 5.4 Tailwind sources

```
vendor/hyva-themes/magento2-default-theme/web/
├── css/
│   └── styles.css              ← compiled output (shipped pre-built)
└── tailwind/
    ├── tailwind-source.css     ← entry point
    ├── hyva.config.json        ← Hyvä config (includes, tokens)
    ├── package.json            ← npm scripts
    ├── base/ components/ theme/ utilities/   ← custom CSS partials
    └── generated/              ← created by `npm run generate`
        ├── hyva-source.css     ← CSS from Hyvä-compatible modules
        └── hyva-tokens.css     ← design tokens from hyva.config.json
```

Real entry point (Tailwind **v4** syntax — no `tailwind.config.js`, no `@tailwind base`):

```css
/* src/vendor/hyva-themes/magento2-default-theme/web/tailwind/tailwind-source.css */
@import "@hyva-themes/hyva-modules/css";
@import "tailwindcss" source(none);
@source "../../**/*.phtml";
@source "../../**/*.xml";

/* Custom styles */
@import "./base";
@import "./components";
@import "./theme";
@import "./utilities";

/* Import generated styles for Hyvä Compatible Modules and Design Tokens */
@import "./generated/hyva-source.css";
@import "./generated/hyva-tokens.css";

@theme {
    --color-ink: var(--color-slate-950);
    --color-ink-muted: var(--color-slate-600);
    --color-fg: var(--color-ink);
    --color-fg-secondary: var(--color-ink-muted);
    --color-bg: var(--color-slate-50);
    --color-surface: var(--color-white);
}
```

- `@source` tells Tailwind which files to **scan for class names**. Only
  classes found in these files end up in `styles.css`. A class built
  dynamically in PHP (`'bg-' . $color`) will not be found.
- `@theme { ... }` defines design tokens as CSS variables (Tailwind v4).

Real `hyva.config.json`:

```json
{
  "tailwind": {
    "include": [
      {
        "comment": "Example of how to include parent themes or modules. Use the 'src' key, instead of 'example_src' to use this",
        "example_src": "vendor/hyva-themes/magento2-default-theme"
      }
    ],
    "exclude": []
  },
  "tokens": {
    "values": {
      "color": {
        "primary": "oklch(46% 0.2 265)",
        "secondary": "oklch(53% 0.15 150)",
        "on-primary": "#fff",
        "on-secondary": "#fff"
      }
    }
  }
}
```

- `tailwind.include` — extra directories to scan (parent theme, modules).
- `tokens` — generates `generated/hyva-tokens.css` (e.g. `bg-primary`, `text-on-primary`).

### 5.5 npm scripts

```json
// src/vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json
"scripts": {
  "start": "npm run watch",
  "generate": "npx hyva-sources && npx hyva-tokens",
  "watch": "npm run generate && npx tailwindcss -i tailwind-source.css -o ../css/styles.css --watch",
  "browser-sync": "npx browser-sync start --config ./browser-sync.config.cjs",
  "build": "npm run generate && npx tailwindcss -i tailwind-source.css -o ../css/styles.css --minify",
  "build-prod": "npm run build"
}
```

Requires **Node.js ≥ 20** (`engines.node`). Output: `web/css/styles.css` of the theme.

### 5.6 `app/etc/hyva-themes.json`

Lists modules that contribute Tailwind CSS (read by `npx hyva-sources`).
It is **generated**, do not edit it by hand:

```bash
bin/magento hyva:config:generate
```

> Official: [Hyvä getting started](https://docs.hyva.io/hyva-themes/getting-started/index.html),
> [Working with Tailwind CSS](https://docs.hyva.io/hyva-themes/working-with-tailwindcss/index.html),
> [Using hyva-modules](https://docs.hyva.io/hyva-themes/working-with-tailwindcss/using-hyva-modules/),
> [Compatibility modules](https://docs.hyva.io/hyva-themes/compatibility-modules/index.html)

---

## 6. How to create a Hyvä child theme

Never edit `vendor/hyva-themes/magento2-default-theme`: create a child theme.
Example names: vendor `Acme`, theme `MyHyva`.

### Step 1 — `registration.php` and `theme.xml`

```php
<?php
// app/design/frontend/Acme/MyHyva/registration.php
use \Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::THEME, 'frontend/Acme/MyHyva', __DIR__);
```

```xml
<?xml version="1.0"?>
<!-- app/design/frontend/Acme/MyHyva/theme.xml -->
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd">
    <title>Acme My Hyva</title>
    <parent>Hyva/default</parent>
</theme>
```

### Step 2 — Copy the parent's `web/` directory

The child theme builds **its own** `styles.css`, so it needs the Tailwind sources:

```bash
cp -r vendor/hyva-themes/magento2-default-theme/web/* app/design/frontend/Acme/MyHyva/web/
rm -rf app/design/frontend/Acme/MyHyva/web/tailwind/node_modules   # if present
```

### Step 3 — Include the parent theme in the Tailwind scan

The `@source "../../**"` lines only scan the child theme. The parent's
templates must be added in `web/tailwind/hyva.config.json`:

```json
{
  "tailwind": {
    "include": [
      { "src": "vendor/hyva-themes/magento2-default-theme" }
    ],
    "exclude": []
  },
  "tokens": {
    "values": {
      "color": {
        "primary": "#006bb4",
        "on-primary": "#fff"
      }
    }
  }
}
```

### Step 4 — Customize

- **Design tokens** (brand colors): `tokens` in `hyva.config.json`.
- **Custom CSS**: files in `web/tailwind/components/`, `theme/`, etc., already
  imported by `tailwind-source.css`. Example:

  ```css
  /* app/design/frontend/Acme/MyHyva/web/tailwind/components/button.css */
  .btn-primary {
      @apply bg-primary text-on-primary;
  }
  ```

- **Templates**: copy the template from `vendor/hyva-themes/magento2-default-theme/<Module>/templates/...`
  to the same path in your theme and edit it using Tailwind classes.

### Step 5 — Build

```bash
cd app/design/frontend/Acme/MyHyva/web/tailwind
npm ci            # or npm install
npm run watch     # during development
npm run build     # before commit / deploy → ../css/styles.css
```

The child's `web/css/styles.css` replaces the parent's through file fallback
(§2.5) — no layout change needed. Rebuild after every template change that adds new Tailwind classes.

### Step 6 — Register, assign, deploy

```bash
bin/magento setup:upgrade
# Admin → Content → Design → Configuration → assign "Acme My Hyva"
bin/magento cache:flush
bin/magento setup:static-content:deploy -f -t Acme/MyHyva en_US
```

> Official: [Building your theme](https://docs.hyva.io/hyva-themes/building-your-theme/index.html)

---

## 7. Hyvä Luma theme fallback

### 7.1 What it is

Theme fallback lets a store run on **Hyvä** while **specific URLs are rendered
with a Luma-based theme**. It is mainly used to keep the **Luma checkout**
(Knockout.js, payment integrations) while the rest of the site uses Hyvä, or to
migrate a store page by page.

On fallback pages, according to the Hyvä documentation, *Tailwind CSS and
Alpine.js are not loaded. Instead, RequireJS and all standard Luma theme
dependencies are loaded*. Therefore **any styling for those pages must be
written with the Luma approach (LESS, §3–§4), not with Tailwind**.

### 7.2 Modules

| Module | Package | Role |
|--------|---------|------|
| `Hyva_ThemeFallback` | `hyva-themes/magento2-theme-fallback` | The mechanism (plugin + configuration) |
| `Hyva_LumaCheckout` | `hyva-themes/magento2-luma-checkout` | Only ships default config for the checkout; depends on `Hyva_ThemeFallback` |

### 7.3 How it works

A **before plugin on all frontend controllers** checks the current request.
If it matches one of the configured URL parts, the design theme for this
request is switched to the fallback theme. Matching rules:

| Configured value | Matches |
|------------------|---------|
| `customer/account` | every `customer/account/*` action |
| `customer/account/login` | the login action only |
| `demo-product.html` | any URL containing this string |

Configuration — **Admin → Hyvä Themes → Theme Fallback → General Settings**:

| Setting | Config path | Default |
|---------|-------------|---------|
| Enable | `hyva_theme_fallback/general/enable` | `0` (`1` when `Hyva_LumaCheckout` is installed) |
| Theme full path | `hyva_theme_fallback/general/theme_full_path` | `frontend/Magento/luma` |
| List of URL parts | `hyva_theme_fallback/general/list_part_of_url` | with `Hyva_LumaCheckout`: `checkout/index`, `paypal/express/review`, `paypal/express/saveShippingMethod`, `paypal/transparent/redirect`, `paypal/transparent/response`, `customer/ajax/login` |

Source: `src/vendor/hyva-themes/magento2-luma-checkout/src/etc/config.xml`.

> ⚠️ If you save your own URL list in the admin, it **replaces** the default
> list. Keep the PayPal and `customer/ajax/login` entries if those flows must stay on Luma.
> "Use system value" restores the defaults.

The theme path uses the **full path with area**: `frontend/<Vendor>/<theme>`.

### 7.4 Using a custom Luma theme as fallback

Using `Magento/luma` directly works, but you cannot customize it without a
child theme. Recommended setup:

1. Create a Luma child theme exactly as in [§4](#4-how-to-create-a-custom-theme-that-extends-luma)
   (`<parent>Magento/luma</parent>`). Keep it minimal: usually only
   `Magento_Checkout/web/css/source/_extend.less` or `web/css/source/_extend.less`
   to align colors/fonts with the Hyvä design.
2. `bin/magento setup:upgrade` so the theme exists in the `theme` table.
3. **Do not** assign it to a store view (the store keeps its Hyvä theme).
4. Set the fallback path:

   ```bash
   bin/magento config:set hyva_theme_fallback/general/enable 1
   bin/magento config:set hyva_theme_fallback/general/theme_full_path frontend/Acme/MyLuma
   bin/magento cache:flush
   ```

5. Deploy static content **for both themes and every locale** used by the store views:

   ```bash
   bin/magento setup:static-content:deploy -f -t Hyva/default -t Acme/MyLuma en_US fr_FR
   ```

   In production mode, a fallback theme missing from the deployment results in
   unstyled/broken fallback pages.

### 7.5 Verifying

- Open a fallback URL (e.g. `/checkout`) and view source: CSS must come from
  `/static/.../frontend/Acme/MyLuma/<locale>/css/styles-m.css` and RequireJS must be loaded.
- Open any other page: CSS comes from `.../Hyva/default/<locale>/css/styles.css`, no RequireJS.

### 7.6 Things to know

- Header/footer on fallback pages are Luma's: restyle them in the fallback
  theme if the visual difference matters.
- Customer data (cart, login) is shared, since both themes use the same Magento
  customer sections and session.
- If Hyvä is itself the *fallback* theme (partial migration the other way), the
  Hyvä docs require adding `page_cache/block/esi` to the URL list.
- Hyvä also offers its own checkout (Hyvä Checkout); the Luma fallback is the
  alternative when that is not used.

> Official: [Luma theme fallback (Hyvä docs)](https://docs.hyva.io/hyva-themes/building-your-theme/luma-theme-fallback.html),
> module READMEs in `src/vendor/hyva-themes/magento2-theme-fallback/README.md` and
> `src/vendor/hyva-themes/magento2-luma-checkout/README.md`

---

## 8. Deployment, cache and debugging

### 8.1 Application modes

| Mode | Static files |
|------|--------------|
| `developer` | Generated **on demand** on first request (symlinks/copies in `pub/static`) |
| `production` | Must be generated with `setup:static-content:deploy`; missing files → 404 |

```bash
bin/magento deploy:mode:show
```

### 8.2 Useful commands

| Command | Purpose |
|---------|---------|
| `bin/magento setup:upgrade` | Register new themes/modules |
| `bin/magento setup:static-content:deploy -f [-t Vendor/theme] [locales]` | Generate static files (`-f` forces in developer mode) |
| `bin/magento dev:source-theme:deploy` | Publish LESS sources for Grunt/client-side compilation |
| `bin/magento cache:flush` | Clear caches (layout, config, full page) |
| `bin/magento hyva:config:generate` | Regenerate `app/etc/hyva-themes.json` |
| `npm run build` (in `web/tailwind`) | Rebuild a Hyvä theme's `styles.css` |

### 8.3 Output locations

```
pub/static/frontend/<Vendor>/<theme>/<locale>/css/styles.css        (Hyvä)
pub/static/frontend/<Vendor>/<theme>/<locale>/css/styles-m.css      (Luma)
pub/static/frontend/<Vendor>/<theme>/<locale>/css/styles-l.css      (Luma)
```

URLs include a version segment (`/static/version<timestamp>/...`) when
**Stores → Configuration → Advanced → Developer → Static Files Settings → Sign Static Files**
is enabled. It changes at each deployment, which busts the browser cache.

### 8.4 "My change does not appear" — checklist

**Luma / LESS theme**

```bash
rm -rf pub/static/frontend/<Vendor>/<theme>/* var/view_preprocessed/* var/cache/* var/page_cache/*
bin/magento setup:static-content:deploy -f -t <Vendor>/<theme> <locale>
```

Then check `var/log/system.log` for LESS compilation errors.

**Hyvä theme**

1. Did you run `npm run build` in the **theme you are using** (not the parent)?
2. Is the class used in a file scanned by `@source` / `tailwind.include`?
3. `bin/magento cache:flush` and redeploy static content in production mode.

**Both**: verify which theme is actually applied to the store view
(Content → Design → Configuration) and, for fallback pages, the Theme Fallback configuration.

### 8.5 Harmless log notice on Hyvä

```
main.NOTICE: magento_import returns empty result by path css/source/_email.less for theme Hyva/default
```

Hyvä has no LESS email sources; this notice can be ignored.

> Official: [Static view file deployment](https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/cli/static-view/static-view-file-deployment),
> [Application modes](https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/setup/application-modes),
> [Debug a theme](https://developer.adobe.com/commerce/frontend-core/guide/themes/debug)

---

## 9. Luma vs Hyvä: summary

| Aspect | Luma | Hyvä |
|--------|------|------|
| Parent | `Magento/blank` | none |
| CSS | LESS + Magento UI library | Tailwind CSS v4 |
| CSS entry | `web/css/styles-m.less`, `styles-l.less` | `web/tailwind/tailwind-source.css` |
| CSS output | `styles-m.css`, `styles-l.css`, `print.css` | `styles.css` |
| Compiled by | Magento (PHP LESS) or Grunt | Tailwind CLI via npm |
| JS | jQuery, RequireJS, Knockout, UI components | Alpine.js (+ Magewire) |
| Customization | `_theme.less` (variables), `_extend.less` (rules) | `hyva.config.json` tokens, Tailwind classes in templates, CSS in `web/tailwind/` |
| Child theme parent | `Magento/luma` | `Hyva/default` |
| Third-party modules | Work out of the box | Often need a Hyvä compatibility module |

---

## 10. Concrete example: AlpineCommerce

This project uses **Hyvä for the whole storefront** and the **Luma theme
fallback for the checkout**, with a small custom Luma theme.

### 10.1 Installed versions (from `src/composer.lock`)

| Package | Version |
|---------|---------|
| `magento/product-community-edition` | 2.4.8 |
| `magento/theme-frontend-luma` / `-blank` | 100.4.8 |
| `hyva-themes/magento2-default-theme` / `-theme-module` | 1.5.2 |
| `hyva-themes/magento2-theme-fallback` | 1.0.4 |
| `hyva-themes/magento2-luma-checkout` | 1.1.7 |
| `hyva-themes/magento2-base-layout-reset` | 2.0.5 |

Enabled Hyvä modules (`src/app/etc/config.php`): `Hyva_Theme`,
`Hyva_BaseLayoutReset`, `Hyva_CompatModuleFallback`, `Hyva_Email`,
`Hyva_GraphqlTokens`, `Hyva_GraphqlViewModel`, `Hyva_ThemeFallback`,
`Hyva_LumaCheckout`, `Hyva_MollieThemeBundle`, `Hyva_OrderCancellationWebapi`.

### 10.2 Themes

| theme_id | Theme | Parent | Used for |
|----------|-------|--------|----------|
| 1 | `Magento/blank` | — | parent of Luma |
| 3 | `Magento/luma` | `Magento/blank` | parent of LumaCheckout |
| 12 | `Hyva/default` | — | **applied to all store views** (`design/theme/theme_id = 12`) |
| 15 | `AlpineCommerce/LumaCheckout` | `Magento/luma` | **checkout fallback theme** (not assigned to any store view) |

There is no Hyvä child theme yet: Hyvä is used as shipped. To customize
Hyvä, follow [§6](#6-how-to-create-a-hyvä-child-theme) (e.g. `AlpineCommerce/Hyva`).

### 10.3 Fallback configuration (`core_config_data`, default scope)

| Path | Value |
|------|-------|
| `hyva_theme_fallback/general/enable` | `1` |
| `hyva_theme_fallback/general/theme_full_path` | `frontend/AlpineCommerce/LumaCheckout` |
| `hyva_theme_fallback/general/list_part_of_url` | `[{"path":"checkout\/index"}]` |

Result: `/checkout` is rendered by `AlpineCommerce/LumaCheckout` (Luma, LESS,
RequireJS); every other page by `Hyva/default` (Tailwind, Alpine.js).

> Note: the saved list contains only `checkout/index`, so it replaces the
> `Hyva_LumaCheckout` defaults (§7.3). PayPal Express review and
> `customer/ajax/login` are therefore **not** on the fallback. Add them back if
> those flows are used.

### 10.4 The `AlpineCommerce/LumaCheckout` theme

```
src/app/design/frontend/AlpineCommerce/LumaCheckout/
├── registration.php           → 'frontend/AlpineCommerce/LumaCheckout'
├── theme.xml                  → parent Magento/Luma (see note)
└── web/css/source/_extend.less
```

`_extend.less` only adds checkout accents, scoped to the checkout page body class
so nothing else in Luma is affected:

```less
// src/app/design/frontend/AlpineCommerce/LumaCheckout/web/css/source/_extend.less
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

Generated CSS: `src/pub/static/frontend/AlpineCommerce/LumaCheckout/<locale>/css/styles-m.css`
(deployed for de_AT, de_CH, de_DE, en_GB, es_ES, fr_BE, fr_CH, fr_FR, it_CH, it_IT, nl_BE, nl_NL, pl_PL, sv_SE).

> Note: `theme.xml` declares `<parent>Magento/Luma</parent>` (capital `L`) while
> Luma is registered as `Magento/luma`. It currently resolves (parent_id = 3 in
> the `theme` table), but new themes should use the exact registered name `Magento/luma`.

### 10.5 Adding a new checkout style change (workflow)

1. Edit `src/app/design/frontend/AlpineCommerce/LumaCheckout/web/css/source/_extend.less`
   (keep rules under `.checkout-index-index`).
2. Clear and redeploy only this theme:

   ```bash
   rm -rf pub/static/frontend/AlpineCommerce/LumaCheckout/* var/view_preprocessed/*
   bin/magento setup:static-content:deploy -f -t AlpineCommerce/LumaCheckout <locales>
   bin/magento cache:flush
   ```

3. Check `/checkout` (styled by LumaCheckout) **and** a Hyvä page such as the
   cart (unchanged, served by `Hyva/default`).

### 10.6 Other notes for this repository

- `src/app/design/frontend/Magento/Luma/i18n/*.csv` has no `registration.php`,
  so it is not a registered theme and Magento does not load these translations.
  Theme translations for the fallback belong in
  `AlpineCommerce/LumaCheckout/i18n/<locale>.csv`.
- `src/app/etc/hyva-themes.json` is generated by `bin/magento hyva:config:generate`.

---

## 11. Official sources

### Adobe Commerce / Magento 2 (Frontend Developer Guide)

| Topic | Link |
|-------|------|
| Themes overview | https://developer.adobe.com/commerce/frontend-core/guide/themes/ |
| Create a storefront theme | https://developer.adobe.com/commerce/frontend-core/guide/themes/create-storefront |
| Theme structure | https://developer.adobe.com/commerce/frontend-core/guide/themes/structure |
| Theme inheritance | https://developer.adobe.com/commerce/frontend-core/guide/themes/inheritance |
| Configure a theme (view.xml) | https://developer.adobe.com/commerce/frontend-core/guide/themes/configure |
| Apply a theme in Admin | https://developer.adobe.com/commerce/frontend-core/guide/themes/apply-admin |
| Debug a theme | https://developer.adobe.com/commerce/frontend-core/guide/themes/debug |
| Include CSS | https://developer.adobe.com/commerce/frontend-core/guide/css/themes |
| CSS / LESS preprocessing | https://developer.adobe.com/commerce/frontend-core/guide/css/preprocess |
| UI library | https://developer.adobe.com/commerce/frontend-core/guide/css/ui-library |
| Debug CSS | https://developer.adobe.com/commerce/frontend-core/guide/css/debug |
| Layouts | https://developer.adobe.com/commerce/frontend-core/guide/layouts/ |
| Extend / override layouts | https://developer.adobe.com/commerce/frontend-core/guide/layouts/extend · https://developer.adobe.com/commerce/frontend-core/guide/layouts/override |
| Override templates | https://developer.adobe.com/commerce/frontend-core/guide/templates/override |
| Translations | https://developer.adobe.com/commerce/frontend-core/guide/translations/ |
| Static content deployment | https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/cli/static-view/static-view-file-deployment |
| Application modes | https://experienceleague.adobe.com/en/docs/commerce-operations/configuration-guide/setup/application-modes |

### Hyvä

| Topic | Link |
|-------|------|
| Getting started | https://docs.hyva.io/hyva-themes/getting-started/index.html |
| Building your theme | https://docs.hyva.io/hyva-themes/building-your-theme/index.html |
| Luma theme fallback | https://docs.hyva.io/hyva-themes/building-your-theme/luma-theme-fallback.html |
| Working with Tailwind CSS | https://docs.hyva.io/hyva-themes/working-with-tailwindcss/index.html |
| Using hyva-modules | https://docs.hyva.io/hyva-themes/working-with-tailwindcss/using-hyva-modules/ |
| Compatibility modules | https://docs.hyva.io/hyva-themes/compatibility-modules/index.html |

### Tailwind CSS

| Topic | Link |
|-------|------|
| Theme variables (`@theme`, v4) | https://tailwindcss.com/docs/theme |

### Source code in this repository

- Magento themes: `src/vendor/magento/theme-frontend-blank/`, `src/vendor/magento/theme-frontend-luma/`
- Hyvä: `src/vendor/hyva-themes/`
- Project themes: `src/app/design/frontend/AlpineCommerce/`
- Module/theme configuration: `src/app/etc/config.php`, `src/app/etc/hyva-themes.json`

*Last updated: 2026-09-23 — links checked on this date.*
