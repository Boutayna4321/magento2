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
12. [Existing Luma LESS Classes Reference](#12-existing-luma-less-classes-reference)
    - [12.1 Page Structure / Layout](#23-page-structure--layout)
    - [12.2 Header & Navigation](#13-header)
    - [12.3 Checkout Classes](#18-checkout--page-structure)
    - [12.4 Minicart & Cart](#27-minicart-header)
    - [12.5 Variables](#34-variables-already-overridden-by-luma)
    - [12.6 Helper Classes](#33-common-helper-classes-extends-used-throughout)

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

---

## 12. Existing Luma LESS Classes Reference

This section lists every CSS class that **already exists** in the Luma theme (inherited via the `Magento/blank` → `Magento/luma` → `AlpineCommerce/LumaCheckout` parent chain) so you know what to target in your `_extend.less` without redefining it from scratch.

Source files scanned from `vendor/magento/`:
- `theme-frontend-blank/web/css/source/_*.less`
- `theme-frontend-blank/Magento_Theme/web/css/source/_module.less`
- `theme-frontend-luma/web/css/source/_theme.less`
- `theme-frontend-luma/Magento_Theme/web/css/source/_module.less`
- `theme-frontend-luma/Magento_Checkout/web/css/source/module/checkout/_*.less`

---

## 23. Page Structure / Layout

### `.page-wrapper`
The outermost page container. Stretches content area for sticky footer (min-height: 100vh). On desktop, children like `.breadcrumbs`, `.top-container`, `.widget` get `box-sizing: border-box; width: 100%`.

### `.page-main`
Main content area. `flex-grow: 1` so it fills remaining space. Contains `.page-title-wrapper`, `.columns`, etc.

### `.columns`
Two-column layout wrapper. Contains `.column.main` (primary content) and `.sidebar-main` / `.sidebar-additional` (sidebars). On desktop uses flexbox; on mobile becomes block.

### `.column.main`
The primary content column.

### `.sidebar-main`
Left sidebar column. On 2columns-right, gets `padding-left` instead of `padding-right`.

### `.sidebar-additional`
Right/additional sidebar.

### `.page-title-wrapper`
Wraps `.page-title`. On desktop, `.page-title` is `display: inline-block`.

### `.page-title`
The page heading. On desktop, inline-block; next to it a `.action` floats right.

### `.page-header`
Entire header block. Gets `border-bottom: 1px solid @border-color__base`.

### `.page-footer`
Entire footer block. On desktop, `margin-top: auto` for sticky footer.

### `.copyright`
Footer copyright bar. Full-width background `@copyright__background-color` (gray-middle4), white text.

---

## 13. Header

### `.header.panel`
The top header panel (above logo). Contains `.header.links` and `.switcher` (store/language switcher).

### `.header.links`
Inline list of header links (My Account, Register, Sign In, etc.). On mobile, hidden; on desktop shown inline. List items are `> li` with inline margins.

### `.panel.header`
Same element as `.header.panel` — parent of `.links` and `.switcher`.

### `.header.content`
The main header content area (logo + nav). Extends `.abs-add-clearfix`.

### `.logo`
Site logo. Floats left, max 50% width. Contains `<img>`. On desktop, centered (`margin: 0 auto 25px 0`).

### `.customer-welcome`
The customer name dropdown. On desktop uses `.lib-dropdown` mixin. Has `.customer-name`, `.customer-menu`, `.greet`.

### `.customer-menu`
Dropdown menu inside `.customer-welcome`. Hidden by default; shown when `.customer-welcome` has `.active`.

### `.action.switch`
The toggle button inside `.customer-welcome`. Reset button style, white text on desktop. Shows `@icon-down` / `@icon-up` icons.

### `.greet`
The "Welcome, [name]" text. Hidden on desktop (inside `.customer-welcome`).

### `.customer-name`
Customer name display text. Hidden on mobile.

### `.switcher`
Generic switcher element (store/language switcher, navigation). On mobile: font-size 1.6rem, clickable header. Positioned in `.page-header .panel.wrapper` and `.page-footer`.

### `.switcher .options`
The dropdown options container. Uses `.lib-dropdown()`. Contains `ul.dropdown`.

### `.switcher .options ul.dropdown`
Dropdown list of options. Each `a` is a block link with padding.

### `.switcher .label`
Switcher label. Visually hidden.

### `.widget`
CMS widget block. Clear both. `.block-title` extends `.abs-block-widget-title`.

### `.widget.block`
Widget block inside header/footer. Margin `@indent__base 0`.

### `.sidebar .widget.block`
Sidebar widget blocks. `:not(:last-child)` gets bottom margin `@indent__xl`.

### `.no-display`
Element with no display. Extends `.abs-no-display`.

---

## 14. Navigation (Main Menu)

### `.nav-sections`
Container for the main navigation. Has background `@navigation__background` (gray94).

### `.nav-toggle`
The hamburger menu icon on mobile. Hidden on desktop. `position: absolute`, top 15px, left 15px.

### `.nav-sections-items`
Container for nav section items. Uses clearfix.

### `.nav-sections-item-title`
Each navigation section title (mobile). On mobile: `background: darken(@navigation__background, 5%)`, border, height 71px. Active state gets transparent background.

### `.nav-sections-item-content`
Dropdown content area. Hidden by default on mobile; shown when parent `.switcher-options` has `.active`.

### `.switcher`
Generic switcher element (store switcher, navigation sections). On mobile: font-size 1.6rem, clickable header.

### `.switcher-trigger`
The clickable part of a switcher. Contains a `<strong>` label with dropdown icon.

### `.switcher-options`
The dropdown container. `.active` class shows `.switcher-dropdown`.

### `.switcher-dropdown`
The dropdown list. Hidden by default; shown when parent has `.active`.

### `.navigation`
The actual menu navigation tree. Uses `.lib-main-navigation()` (mobile) and `.lib-main-navigation-desktop()` (desktop).

### `.header.links` (inside nav)
On mobile nav, header links are shown as a list with bold font, borders between items.

---

## 15. Breadcrumbs

### `.breadcrumbs`
Uses `.lib-breadcrumbs()` mixin. Standard breadcrumb list with `>` separators.

---

## 16. Buttons / Actions

### `.action`
Base class for all action links/buttons. Can be combined:

### `.action.primary` / `.action-primary`
Primary button style. Uses `.lib-button-primary()` mixin. This is the main CTA button (e.g., "Place Order", "Add to Cart").

### `.action.back`
Back action link. Hidden by default (`.actions-toolbar .secondary .action.back { display: none }`).

### `.action.skip`
Skip-to-content link. Visually hidden unless focused (screen readers).

### `.action.skip-wrapper`
Container for skip link.

### `button`
Native `<button>` elements. `:active` gets `box-shadow` inset highlight.

### `a.action.primary`
Links styled as primary buttons. Uses `.lib-link-as-button()` mixin.

### `.action-toggle`
Toggle action (used in payment option titles). Shows expand/collapse icon.

### `.action-close`
Close action (used in modals). In checkout modals, hidden on desktop.

### `.action-save-address`
Save address button in checkout modals. On mobile: `width: 100%`. On desktop: floats right.

### `.action-hide-popup`
Hide popup action. Styled as link.

---

## 17. Forms / Fields

### `.fieldset`
Fieldset container. Uses `.lib-form-fieldset()` mixin. On desktop, `.legend` extends `.abs-margin-for-forms-desktop`.

### `.field`
Individual form field. Uses `.lib-form-field()` mixin.

### `.field.no-label`
Field without visible label — label is visually hidden.

### `.field.choice`
Checkbox/radio choice field. Label is `display: inline`, normal weight.

### `.field.date`
Date field. Extends `.abs-field-date`. Contains `.time-picker`.

### `.field._error`
Field with error state. Input/select/textarea gets `@checkout-field-validation__border-error` border.

### `.field._with-tooltip`
Field with tooltip. Uses `.abs-field-tooltip`.

### `.control`
Wrapper around the actual input element(s).

### `.label`
Field label. On desktop: `@font-weight__semibold`; in sidebar forms: `text-align: left`, margin bottom.

### `.legend`
Fieldset legend. Contains `<strong>`.

### `.note`
Form field note/helper text. In checkout shipping: `font-size: @font-size__base`, `margin-top: @indent__s`.

### `.fields`
Container for grouped fields. Children are `.field`.

### `.input-text`
Text input elements. Standard form text input.

### `select` / `.select`
Dropdown selects. On desktop: has select arrow background image, 32px height.

### `.input.focus`
Focus state on inputs.

### `.tooltip`
Form field tooltip. Uses `.lib-tooltip(right)` mixin.

### `.tooltip-content`
Tooltip content. Min-width 200px, normal white-space.

### `._has-datepicker`
Input with date picker. The calendar trigger button gets calendar icon.

### `.cald` / `.calendar`
Calendar/date picker popup.

---

## 18. Checkout — Page Structure

### `.checkout-index-index`
**Body class on the checkout page.** All checkout-specific styles are scoped under this. This is your entry point in `_extend.less`.

### `.checkout-container`
Outer checkout wrapper.

### `.checkout-onepage-success`
Checkout success page (`.checkout-onepage-success`).

---

## 19. Checkout — Steps & Progress

### `.opc-wrapper`
One-page checkout steps wrapper.

### `.opc`
Steps list. Extends `.abs-reset-list`.

### `.step-title`
Step title (e.g., "Shipping Address", "Payment Method"). Extends `.abs-checkout-title`. Has `border-bottom` using `@checkout-step-title__border`.

### `.step-content`
Step content container.

### `.checkout-shipping-method`
Shipping method section. `.step-title` gets `margin-bottom: 0`.

### `.checkout-payment-method`
Payment method section. `.step-title` removes border/margin.

### `.no-quotes-block`
Fallback block when no shipping methods available.

### `.checkout-shipping-address`
Shipping address form container.

### `.checkout-billing-address`
Billing address section.

### `.checkout-billing-address-details`
Display of saved billing address (non-editable view). Line-height @checkout-billing-address-details__line-height, padding `0 0 0 23px`.

### `.billing-address-same-as-shipping-block`
Checkbox container for "billing same as shipping".

### `.payment-method-note`
Note above billing address form.

### `.no-payments-block`
Message shown when no payment methods available.

### `.payments`
Payments container. Contains `.legend` (visually hidden).

---

## 20. Checkout — Order Summary (Right Sidebar)

### `.opc-block-summary`
The order summary block. Background `@checkout-summary__background-color` (white-smoke), padding `22px @indent__l`.

### `.opc-summary-wrapper`
Wrapper containing `.modal-header` on mobile (modal version). On desktop, hides `.action-close` in modal header.

### `.opc-block-summary > .title`
Summary block title. Extends `.abs-checkout-title`, `display: block`.

### `.table-totals`
Totals table inside summary. Extends `.abs-sidebar-totals`.

### `.opc-block-summary .mark`
"Label" column in totals. `.value` gets color `@checkout-summary-mark-value__color` (gray40), `display: block`.

### `.grand.incl` / `.grand.excl`
Grand total rows. Excl gets border-top, font-size 14, normal weight.

### `.not-calculated`
Text for not-calculated totals. Italic, normal white-space.

### `.items-in-cart`
Cart items title in summary. Clickable (cursor: pointer) with expandable icon. `.active` shows up icon.

### `.minicart-items-wrapper`
Cart items list inside summary. Has max-height 370px, padding 15px.

### `.product-item`
Each product row in summary. Contains `.product-item-details` and `.product-item-inner`.

### `.product-item-details`
Product details area. Extends clearfix.

### `.product-item-inner`
Inner table-like layout for product name + price. Display table.

### `.product-item-name-block`
Product name column. Block display.

### `.subtotal`
Product subtotal. Block display, left-aligned.

### `.price`
Price display. Font-size 16px (desktop: 14px for excluding-tax), regular weight.

### `.price-including-tax` / `.price-excluding-tax`
Tax-included/excluded price. Excluding-tax gets font-size 10px.

### `.message`
Message inside product item (e.g., "no options").

### `.actions-toolbar` (in summary)
Actions toolbar at bottom of summary. `.secondary` gets top border, block display.

### `.column.main .product-item`
Inside `.opc-block-summary .column.main`, removes margin/padding from product items.

---

## 21. Checkout — Shipping Address

### `.form-shipping-address`
Shipping address form. Gets `margin-top/bottom @checkout-shipping-address__margin-top` (28px). Max-width 500px on desktop.

### `.form-login`
Login form on checkout. Same styles as shipping address form.

### `.shipping-address-items`
Container for saved address items (radio selection). `font-size: 0`.

### `.shipping-address-item`
Each saved address. Border 2px transparent, padded `@indent__base`, width 1/2 on tablet, 1/3 on desktop, 100% on mobile.

### `.shipping-address-item.selected-item`
Selected address. Border-color = `@active__color`, gets checkmark icon after pseudo-element.

### `.action-select-shipping-item`
Radio button for selecting address. Floats right, hidden on desktop for selected items.

### `.action-show-popup`
Button to show "add new address" popup. Margin bottom.

### `.edit-address-link`
Edit address link. Styled as action button (`.abs-action-button-as-link`).

### `.table-checkout-shipping-method`
Shipping methods table. Header hidden, rows have border-top on cells.

### `.methods-shipping`
Shipping methods container. `.actions-toolbar .action.primary` extends `.abs-button-l`.

### `.col-price`
Price column in shipping table. Semibold weight.

### `.row-error`
Error row in shipping table. No border-top, reduced padding.

---

## 22. Checkout — Payments

### `.payment-method`
Each payment method block. `.payment-method-title` is clickable header.

### `.payment-method-title`
Payment method title. Padding using `@checkout-payment-method-title__padding`. Contains `.payment-icon` and `.action-help`.

### `.payment-icon`
Payment method icon image. Inline-block, right margin.

### `.action-help`
Help icon next to payment method title.

### `.payment-method-content`
The payment form/content. Hidden by default (`display: none`); shown when parent `.payment-method` has `._active`.

### `.payment-method._active`
Active payment method. Child `.payment-method-content` becomes `display: block`.

### `.payment-group`
Group of payment methods. Consecutive groups get top margin on `.step-title`.

### `.field-select-billing`
Billing address selector dropdown. Label visually hidden.

### `.billing-address-form`
Billing address form. Max-width = `@checkout-shipping-address__max-width`.

### `.fieldset` (in payment-method-content)
Fieldset within payment content. `:not(:last-child)` gets bottom margin.

### `.payment-option`
Individual payment option (e.g., credit card fields). `._active` shows content. `._collapsible` has clickable title.

### `.payment-option-title`
Payment option title. Has border-top, padding. Contains `.action-toggle`.

### `.payment-option-content`
Payment option content. Hidden by default. Extends `.abs-discount-code`.

### `.action-apply`
Apply coupon/discount action.

### `.payment-option-inner`
Inner wrapper for payment option fields.

### `.credit-card-types`
Credit card type selector. List of icons. `.item._active` shows in color, `._inactive` at 40% opacity.

### `.ccard`
Credit card form container. Has `.fields` (`.year` with left padding, `.select` with padding).

### `.ccard .month .select`
Month dropdown. Width 140px.

### `.ccard .year .select`
Year dropdown. Width 80px.

### `.ccard .captcha, .ccard .number .input-text`
Card number input. Width 225px.

### `.field.cvv`
CVV field. `.control` is inline-block with right padding.

### `.cvv .label` / `.cvv .input-text`
CVV label (block display), input width 55px.

### `.fieldset.group.group-2 .field`
In CCV form, group-2 fields get `width: auto !important`.

---

## 23. Checkout — Progress Bar

### `.checkout-progress-bar`
The step progress indicator. Uses `.lib-progress-bar()` mixin from the UI library.

---

## 24. Checkout — Modals (Address Forms)

### `.modal-popup` (within `.checkout-index-index`)
Modal windows on checkout. Contains `.fieldset` and `.modal-footer`.

### `.modal-header`
Modal header. On desktop, `.action-close` is hidden.

### `.modal-footer`
Modal footer. Contains `.action-save-address` and `.action-hide-popup`.

### `.form-shipping-address` (within modal)
Same form styles as above but inside modal, max-width on desktop.

---

## 25. Checkout — Estimated Total / Minicart

### `.opc-estimated-wrapper`
Estimated total wrapper (mobile only). Gets background `@checkout-step-content-mobile__background`, top/bottom borders. Desktop: hidden via `.abs-no-display-desktop`.

### `.estimated-block`
The "estimated total" label. Floats left, bold.

### `.estimated-label`
Label within estimated block.

### `.minicart-wrapper`
Minicart wrapper inside estimated total. `.action.showcart` gets reset button style, primary color icon.

---

## 26. Cart

### `.cart-table`
Cart page table. Extends `.lib-table-bordered()` with special cart styling.

### `.cart`
Cart container.

### `.minicart-wrapper`
Minicart dropdown wrapper.

---

## 27. Minicart (Header)

### `.minicart-wrapper`
Wrapper around minicart content.

### `.minicart-items`
Minicart items list.

### `.minicart-items-wrapper`
Scrollable wrapper inside minicart.

### `.product-item` (in minicart)
Each product row.

### `.product-item-details`
Product details in minicart.

### `.product-item-info`
Product image/link container.

### `.product-item-name`
Product name link.

### `.product-item-price`
Price display.

### `.product-item-actions`
Action buttons (edit, delete).

### `.action.delete`
Delete/remove button.

### `.action.edit`
Edit button.

### `.btn-slide`
Slide toggle button for minicart.

### `.counter`
Quantity counter.

### `.counter-number`
The number inside counter.

### `.counter-label`
Label text in counter.

> See section **36. Minicart (Header Dropdown)** for the full detailed minicart reference with variables and mixin details.

---

## 28. Messages / Alerts

### `.message`
Base message class. Types:

### `.message.info` / `.message.error` / `.message.warning` / `.message.notice` / `.message.success`
Different message types. Each gets appropriate icon via `.lib-message-icon-inner()`.

### `.message.global`
Global message (cookie notice, demo store). `.noscript` and `.cookie` get note styling. `.cookie` is fixed at bottom. `.demo` gets caution styling.

### `.messages`
Container for multiple messages.

---

## 29. Tables

### `.table-wrapper`
Table wrapper. Margin-bottom.

### `.table`
Base table class. Not `.cart` or `.totals`: gets bordered styling (`.lib-table-bordered()` light variant).

### `.table.totals`
Totals table. Gets special sidebar totals styling.

### `.table.cart`
Cart table. Excluded from general table styling.

### `.table.table-comparison`
Product comparison table.

### `.data-table-definition-list`
Responsive definition list table (mobile: collapses).

---

## 30. Pager (Pagination)

### `.pages`
Pager container. Uses `.lib-pager()`.

### `.action.previous` / `.action.next`
Previous/next page arrows. Width 34px, side margins.

### `.pages.item`
Each page number item.

### `.pages.items`
Items container.

---

## 31. Block / Widget

### `.block`
Generic block container.

### `.block-title`
Block title. Uses `.abs-block-title` / `.abs-block-widget-title`.

### `.block-content`
Block content area.

### `.switcher-store`
Store switcher.

---

## 32. Collapsible Navigation (Sidebar)

### `.block-collapsible`
Collapsible block.

### `.switch.close`
Close state of collapsible.

### `.switch.active`
Active state of collapsible.

---

## 33. Common Helper Classes (extends used throughout)

These are abstract classes extended into selectors. You can target them too:

| Class | Purpose |
|---|---|
| `.abs-button-primary all` | Primary button styling |
| `.abs-button-l all` | Large button |
| `.abs-button-reset()` | Button reset (no styles) |
| `.abs-action-button-as-link all` | Link as button (text-only) |
| `.abs-action-button-as-linkDesktop all` | Desktop link-as-button |
| `.abs-button-responsive all` | Responsive button (mobile full-width) |
| `.abs-button-l all` | Large button base |
| `.abs-no-display all` / `.abs-no-display-s all` / `.abs-no-display-desktop all` | Display none at respective sizes |
| `.abs-visually-hidden all` | Screen reader only |
| `.abs-add-clearfix all` / `.abs-add-clearfix-desktop all` | Clearfix |
| `.abs-reset-list all` | Reset list styles |
| `.abs-margin-for-forms-desktop all` | Margin for form fields on desktop |
| `.abs-checkout-title all` | Checkout step/title styling |
| `.abs-sidebar-totals all` / `.abs-sidebar-totals-mobile all` | Sidebar totals table |
| `.abs-field-tooltip all` | Field with tooltip |
| `.abs-field-date all` | Date field |
| `.abs-product-options-list all` | Product options list |
| `.abs-checkout-tooltip-content-position-top all` | Tooltip content position |
| `.abs-discount-code all` | Discount code styling |
| `.abs-block-title all` / `.abs-block-widget-title all` | Block/widget title |
| `.abs-block-content all` | Block content |
| `.abs-add-box-sizing all` / `.abs-add-box-sizing-desktop all` | Box-sizing border-box |

---

## 34. Variables Already Overridden by Luma

These are defined in `theme-frontend-luma/web/css/source/_theme.less` (Luma's variable overrides). Your theme inherits these. Key ones relevant to checkout:

| Variable | Luma Value | Role |
|---|---|---|
| `@primary__color` | *(inherited from Blank)* | Primary brand color — active borders, links |
| `@active__color` | *(inherited from Blank)* | Active/selected color (shipping address border) |
| `@checkout-step-title__border` | `@border-width__base solid @color-gray80` | Step title bottom border |
| `@checkout-step-title__font-size` | `26px` | Step title font size |
| `@checkout-step-title__font-weight` | `@font-weight__light` | Step title weight |
| `@checkout-step-content-mobile__background` | `@color-gray-light01` | Mobile step content background |
| `@checkout-summary__background-color` | `@color-white-smoke` | Order summary background |
| `@checkout-summary__padding` | `22px @indent__l` | Order summary padding |
| `@checkout-summary-mark-value__color` | `@color-gray40` | Summary mark value color |
| `@checkout-summary-items__max-height` | `370px` | Max height of summary items |
| `@checkout-summary-items__padding` | `15px` | Summary items padding |
| `@checkout-shipping-address__max-width` | `500px` | Max width of address form |
| `@checkout-shipping-address__margin-top` | `28px` | Top margin on address form |
| `@checkout-shipping-item__border` | `2px solid transparent` | Shipping address item border |
| `@checkout-shipping-item__active__border-color` | `@active__color` | Active address border |
| `@checkout-shipping-method__border` | `@checkout-step-title__border` | Shipping method row border |
| `@checkout-shipping-method__padding` | `@indent__base` | Shipping method row padding |
| `@checkout-payment-method-title__border` | `@checkout-shipping-method__border` | Payment title border |
| `@checkout-payment-method-title__padding` | `@checkout-shipping-method__padding` | Payment title padding |
| `@checkout-modal-popup__width` | `800px` | Modal popup width |
| `@header__background-color` | `false` (transparent) | Header background |
| `@header-panel__background-color` | `@color-gray-middle4` | Top panel background |
| `@header-panel__text-color` | `@color-white` | Top panel text color |
| `@header-icons-color` | `@color-gray46` | Header icon color |
| `@header-icons-color-hover` | `@color-gray20` | Header icon hover |
| `@footer__background-color` | `@color-gray-light01` | Footer background |
| `@copyright__background-color` | `@color-gray-middle4` | Copyright bar bg |

---

## 35. How to Use This in `_extend.less`

Your current `_extend.less` already targets `.checkout-index-index`. Here are common override patterns:

```less
// Override existing class styles (later in cascade wins)
.checkout-index-index {
    .opc-wrapper {
        .step-title {
            // Already exists — border-bottom, color, font-size, padding
            border-color: #your-color;
        }
    }

    .opc-block-summary {
        // Already exists — background, padding
        border-top: 3px solid #006bb4;
    }

    .shipping-address-item {
        // Already exists — border, padding, width
        border-color: #your-color;
    }

    .payment-method-title {
        // Already exists — padding, border
        background: #your-color;
    }

    .action.primary {
        // Already exists — primary button (.lib-button-primary)
        // Uses @primary__color from theme variables
    }
}

// Page-wide header/footer (visible on all Luma fallback pages)
.page-header {
    .header.panel {
        background: #your-color;
    }
}

.page-footer {
    .copyright {
        background: #your-color;
    }
}
```

**Key principle:** Your `_extend.less` is loaded **after** all parent theme styles via `@magento_import`, so any class you define here **overrides** the existing Luma styles for that class.

---

## 36. Minicart (Header Dropdown)

### `.minicart-wrapper`
Wrapper around the minicart dropdown. Uses `.lib-dropdown()` mixin. Floats right. On desktop, `.block-minicart` is 390px wide.

### `.block-minicart`
The minicart dropdown panel. Padding `25px @minicart__padding-horizontal`.

### `.block-minicart .block-title`
Block title — hidden (`display: none`).

### `.minicart-items`
Minicart items list. Uses `.lib-list-reset-styles()`. Contains `.product-item`.

### `.minicart-items-wrapper`
Scrollable wrapper. Border 1px solid `@minicart__border-color`, overflow-x: auto, padding 15px.

### `.product-item` (in minicart)
Each cart item. Padded `@indent__base 0`; first-child gets `padding-top: 0`. Has border-top on non-first items.

### `.product-item > .product`
Clearfix container.

### `.product-item-details`
Product details. Padding-left 88px (to make room for image).

### `.product-item-price`
Price area.

### `.price` (in product-item-details)
Bold font-weight.

### `.price-including-tax` / `.price-excluding-tax`
Margin `@indent__xs 0`.

### `.product-item-name`
Product name. Font-weight regular, margin bottom `@indent__s`. Link inherits `@link__color`.

### `.product-image-wrapper`
Image wrapper. Extends `.abs-reset-image-wrapper`.

### `.action.showcart`
Cart icon button (toggles minicart). Uses cart icon `@icon-cart`. `.text` is visually hidden.

### `.counter.qty`
Quantity counter badge. Background `@active__color`, color `@page__background-color`, height 24px, border-radius 2px.

### `.counter-number`
The number text inside counter. Has text-shadow.

### `.counter-label`
"Items" label. Visually hidden.

### `.action.close`
Close button on minicart. Top-right corner, remove icon.

### `.action.edit` / `.action.delete`
Edit (pencil icon) and delete (trash icon) actions. Icon buttons using `.lib-icon-font()`.

### `.items-total`
Total items count. Floats left. `.count` is bold.

### `.subtotal`
Cart subtotal text. Floats right. `.label` uses `.abs-colon`.

### `.amount .price-wrapper:first-child .price`
Subtotal price. Large bold font.

### `.message` (in minicart)
Margin adjustments.

### `.details-qty`
Qty details.

### `.update-cart-item`
Update qty text.

### `.toggle`
Expand/collapse product options. Uses `.abs-toggling-title`.

### `.product.options.list`
Product options list. Extends `.abs-product-options-list` and `.abs-add-clearfix`.

### `.weee[data-label]`
WELL fee display. Font-size 11px. `.label` hidden.

### `.product.pricing`
Pricing wrapper. Margin-top 3px.

### `.block-content > .actions`
Actions at bottom of minicart. `.primary .action.primary` extends `.abs-button-l`, full width.

---

## 37. Cart Page (`checkout-cart-index`)

### `.checkout-cart-index`
Body class for cart page.

### `.cart-container`
Cart wrapper.

### `.form-cart`
Cart form. Extends `.abs-shopping-cart-items`.

### `.cart-summary`
Order summary block on cart page. Background `@sidebar__background-color`.

### `.cart-summary > .title`
Summary title. Hidden on desktop (`display: none`).

### `.cart-totals`
Totals table. Extends `.abs-sidebar-totals`.

### `.cart`
Cart table container.

### `.cart.table-wrapper .cart`
The actual cart table.

### `.cart .cart thead tr th.col`
Table headers. Border-bottom, padding.

### `.cart .cart > .item`
Each cart item row. Border-bottom.

### `.cart .col.item`
Item cell. Position relative, padding.

### `.cart .col.price / .col.subtotal / .col.msrp / .col.qty`
Column cells. Centered text on desktop, 33% width on mobile.

### `.cart .col.qty .input-text`
Quantity input. Width 60px, height 36px.

### `.cart .item-actions`
Action buttons area. Contains `.action-edit` and `.action-delete`.

### `.cart .product-item-photo`
Product image. Position absolute, max-width 65px.

### `.cart .product-item-name`
Product name link. Font-size 18px.

### `.cart .item-options`
Product options. Extends `.abs-product-options-list` and `.abs-add-clearfix`.

### `.cart .cart-tax-total`
Tax total. Extends `.abs-tax-total`.

### `.cart-discount`
Discount coupon block. Extends `.abs-discount-block`.

### `.cart-empty`
Empty cart message.

### `.checkout-methods-items`
Checkout button list. Extends `.abs-reset-list`. `.action.primary.checkout` extends `.abs-button-l`, full width.

### `.cart-products-toolbar`
Toolbar above/below cart products.

---

## 38. Key LESS Variables (Blank/Luma)

These are defined in `lib/web/css/source/lib/variables/` and overridden in Luma's `_theme.less`. You can override them in your own `_theme.less` (copy Luma's first). Key ones to know:

### Color variables
| Variable | Default | Role |
|---|---|---|
| `@primary__color` | `#006bb4` (Luma) | Primary brand (links, active states, buttons) |
| `@secondary__color` | `#f5f5f5` (Luma, gray-gray-light1) | Secondary brand |
| `@link__color` | `@theme__color__primary-alt` | Link color |
| `@link__hover__color` | *(darker)* | Link hover |
| `@border-color__base` | `@color-gray80` | Base border color |
| `@page__background-color` | `@color-gray-light0` | Page background |
| `@color-gray-light0` | `#faf8f7` | Light gray bg |
| `@color-gray-light1` | `#f5f5f5` | Light gray bg |
| `@color-gray94` | `#f5f5f5` | Navigation bg |
| `@color-gray80` | `#d9d6d6` | Border color |

### Spacing variables
| Variable | Value | Role |
|---|---|---|
| `@indent__base` | `15px` | Default indent/gutter |
| `@indent__s` | `10px` | Small indent |
| `@indent__xs` | `5px` | Extra small indent |
| `@indent__l` | `20px` | Large indent |
| `@indent__xl` | `30px` | Extra large indent |

### Breakpoint variables
| Variable | Value | Role |
|---|---|---|
| `@screen__s` | `480px` | Small screen max |
| `@screen__m` | `768px` | Medium (desktop threshold) |
| `@screen__l` | `1024px` | Large |

---

## 39. File Loading & Override Priority

When `@media-common = true` (default, includes all devices), styles load first. Then mobile (`max-width: @screen__s`) and desktop (`min-width: @screen__m`) media queries are applied.

**Override order in your `_extend.less`:**
1. Blank theme styles (base)
2. Luma theme overrides (on top of Blank)
3. Your `AlpineCommerce/LumaCheckout/_extend.less` (loaded via `@magento_import`, after everything else)

This means your rules in `_extend.less` **always win** over Luma's defaults (assuming equal specificity). If you need to win against higher specificity, just add more specificity — e.g., `.checkout-index-index .step-title` instead of just `.step-title`.

**To deploy changes:**
```bash
rm -rf pub/static/frontend/AlpineCommerce/LumaCheckout/* var/view_preprocessed/*
bin/magento setup:static-content:deploy -f -t AlpineCommerce/LumaCheckout <locale>
bin/magento cache:flush
```
