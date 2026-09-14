# Magento 2 — Styles & CSS: Luma, Hyva & Tailwind

> **Objective**: understand how Magento 2 stylesheets are organized, how the
> Luma theme works, how Hyva replaces it with Tailwind CSS, and how to
> configure and customize the storefront styles.

---

## Table of Contents

1. [Why styles matter in Magento](#1-why-styles-matter-in-magento)
2. [The Luma theme (default Magento 2)](#2-the-luma-theme-default-magento-2)
3. [The Hyva theme (Tailwind CSS)](#3-the-hyva-theme-tailwind-css)
4. [Hyva vs Luma: architecture comparison](#4-hyva-vs-luma-architecture-comparison)
5. [How to install and configure Hyva](#5-how-to-install-and-configure-hyva)
6. [Hyva Tailwind CSS build system](#6-hyva-tailwind-css-build-system)
7. [Customizing styles in Hyva](#7-customizing-styles-in-hyva)
8. [CSS deployment in Magento](#8-css-deployment-in-magento)
9. [AlpineCommerce reference](#9-alpinecommerce-reference)
10. [Official sources](#10-official-sources)

---

## 1. Why styles matter in Magento

Magento 2 separates **structure** (HTML/PHTML), **behavior** (JavaScript),
and **presentation** (CSS). Stylesheets are:

- **Compiled** from LESS (Luma) or Tailwind source (Hyva) into plain CSS
- **Deployed** to `pub/static/frontend/<Vendor>/<theme>/<locale>/`
- **Cached** by Magento's full-page cache and the browser

The theme system determines which CSS files are loaded on each page.

### 1.1 Magento's CSS pipeline (Luma)

```
src/vendor/magento/module-theme/web/css/
    ├── styles.less          ← entry point
    ├── source/_imports/     ← LESS imports
    └── ...
         ↓ bin/magento setup:static-content:deploy
pub/static/frontend/Magento/luma/en_GB/css/styles.css
```

**Source**: `src/vendor/magento/module-theme/view/frontend/web/css/styles.less`

### 1.2 Hyva's CSS pipeline

```
vendor/hyva-themes/magento2-default-theme/web/tailwind/
    ├── tailwind-source.css  ← entry point
    ├── hyva.config.json     ← Tailwind config
    └── generated/           ← auto-generated tokens
         ↓ npm run build (Tailwind CLI)
vendor/hyva-themes/magento2-default-theme/web/css/styles.css
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json`

---

## 2. The Luma theme (default Magento 2)

Luma is the **default Magento 2 frontend theme**. It uses **LESS** (a CSS
preprocessor) and is the reference theme for all Magento projects.

### 2.1 Where Luma lives

```
src/vendor/magento/theme-frontend-luma/
    ├── web/
    │   ├── css/
    │   │   ├── styles.less          ← main entry
    │   │   ├── source/
    │   │   │   ├── _actions.less
    │   │   │   ├── _breadcrumbs.less
    │   │   │   ├── _buttons.less
    │   │   │   ├── _forms.less
    │   │   │   ├── _navigation.less
    │   │   │   └── ...
    │   │   └── print.css
    │   ├── images/
    │   └── js/
    ├── etc/
    │   └── view.xml                 ← page layout + CSS includes
    └── templates/                   ← HTML templates (PHTML)
```

**Source**: `src/vendor/magento/theme-frontend-luma/`

### 2.2 How CSS is included

In `etc/view.xml`, modules declare CSS files:

```xml
<!-- src/vendor/magento/theme-frontend-luma/etc/view.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema/example"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/etc/etc_page.xsd">
    <head>
        <css src="css/styles.css" />
    </head>
</page>
```

**Source**: `src/vendor/magento/theme-frontend-luma/etc/view.xml`

### 2.3 LESS imports

The main `styles.less` imports modular files:

```less
// src/vendor/magento/theme-frontend-luma/web/css/styles.less
@import 'source/_actions';
@import 'source/_breadcrumbs';
@import 'source/_buttons';
@import 'source/_forms';
@import 'source/_navigation';
@import 'source/_theme';
```

**Source**: `src/vendor/magento/theme-frontend-luma/web/css/styles.less`

### 2.4 Luma variables

LESS variables define the design tokens:

```less
// src/vendor/magento/theme-frontend-luma/web/css/source/_theme.less
@color-primary: #46978e;
@color-secondary: #6f6f6f;
@font-size-base: 14px;
@line-height-base: 20px;
```

**Source**: `src/vendor/magento/theme-frontend-luma/web/css/source/_theme.less`

### 2.5 How to customize Luma

Create a **child theme** that extends Luma:

```xml
<!-- app/design/frontend/Vendor/MyLuma/etc/theme.xml -->
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       title="My Luma"
       parent="Magento/luma"
       type="frontend"/>
```

Then override CSS in `web/css/source/_custom.less`:

```less
// web/css/styles.less
@import 'source/_custom';
```

**Source**: `src/vendor/magento/framework/View/etc/element.xml` (theme inheritance)

---

## 3. The Hyva theme (Tailwind CSS)

Hyva is a **Tailwind CSS**-based Magento 2 theme. It replaces Luma's LESS
with utility-first Tailwind classes and Alpine.js for interactivity.

### 3.1 Hyva package structure

```
vendor/hyva-themes/magento2-default-theme/
    ├── Hyva_Theme/                  ← Magento module (registration.php)
    ├── etc/
    │   ├── module.xml               ← module: Hyva_Theme
    │   └── di.xml
    ├── web/
    │   ├── css/
    │   │   ├── styles.css           ← compiled Tailwind output
    │   │   └── print.css
    │   └── tailwind/
    │       ├── tailwind-source.css  ← entry point (imports utilities)
    │       ├── hyva.config.json     ← Tailwind config
    │       ├── package.json         ← npm scripts
    │       └── generated/           ← auto-generated tokens
    └── registration.php
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/`

### 3.2 Module registration

```php
// vendor/hyva-themes/magento2-default-theme/registration.php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Hyva_Theme',
    __DIR__
);
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/registration.php`

### 3.3 Tailwind source entry point

```css
/* vendor/hyva-themes/magento2-default-theme/web/tailwind/tailwind-source.css */
@import 'generated/hyva-tokens.css';
@import 'generated/hyva-source.css';

@tailwind base;
@tailwind components;
@tailwind utilities;
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/tailwind-source.css`

### 3.4 Tailwind configuration

```json
/* vendor/hyva-themes/magento2-default-theme/web/tailwind/hyva.config.json */
{
  "prefix": "",
  "important": false,
  "content": [
    "./**/*.phtml",
    "./**/*.xml"
  ]
}
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/hyva.config.json`

### 3.5 Pre-built styles.css

The package ships a **pre-built** `styles.css` (101 KB, Tailwind v4):

```
vendor/hyva-themes/magento2-default-theme/web/css/styles.css
```

This is what Magento deploys to `pub/static`. To regenerate it:

```bash
cd vendor/hyva-themes/magento2-default-theme/web/tailwind
npm install        # requires Node.js >= 20
npm run build      # generates ../css/styles.css
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json`

### 3.6 How Hyva CSS is loaded

Hyva uses Magento's standard CSS inclusion via `etc/view.xml`:

```xml
<!-- vendor/hyva-themes/magento2-default-theme/etc/view.xml -->
<page>
    <head>
        <css src="Hyva_Theme::css/styles.css" />
    </head>
</page>
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/etc/view.xml`

---

## 4. Hyva vs Luma: architecture comparison

| Aspect | Luma | Hyva |
|--------|------|------|
| **CSS preprocessor** | LESS | Tailwind CSS v4 |
| **Styling approach** | Semantic classes (`.button`, `.form`) | Utility classes (`btn-primary`, `px-4`) |
| **Interactivity** | jQuery + custom JS | Alpine.js (replaces jQuery) |
| **Build step** | Magento LESS compiler | Tailwind CLI (npm) |
| **Entry file** | `web/css/styles.less` | `web/tailwind/tailwind-source.css` |
| **Output** | `pub/static/.../css/styles.css` | `pub/static/.../css/styles.css` |
| **Theme inheritance** | `parent="Magento/luma"` | `parent="Magento/hyva"` or `Hyva/default` |
| **Layout** | Standard Magento layout XML | Same, but with Hyva components |
| **Templates** | PHTML (same) | PHTML (same, but uses Hyva helpers) |

### 4.1 How Hyva replaces Luma

Hyva does **not** modify Magento core. It provides:

1. **A theme module** (`Hyva_Theme`) that registers the `Hyva/default` theme
2. **Layout resets** (`Hyva_BaseLayoutReset`) that override Luma's default
   containers with Tailwind-friendly markup
3. **A CSS framework** that replaces Luma's LESS output with Tailwind CSS

### 4.2 Theme inheritance

```
Magento/luma (base)
    ↑
Hyva/default (extends luma, adds Tailwind CSS + layout resets)
    ↑
AlpineCommerce/HyvaCustom (child theme, customizations)
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/composer.json`

---

## 5. How to install and configure Hyva

This section documents the **actual installation** performed in this
repository (2026-09-14), based on the official Hyva installation guide.

### 5.1 Prerequisites

- Magento 2.4.8 (PHP 8.2)
- Node.js >= 20 (for Tailwind CSS build)
- Composer

### 5.2 Configure composer repositories (GitHub, no Packagist key)

Hyva can be installed from GitHub without a Packagist key:

```json
/* src/composer.json */
{
  "require": {
    "hyva-themes/magento2-default-theme": "^1.5"
  },
  "repositories": [
    {
      "name": "hyva-themes/magento2-default-theme",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-default-theme.git"
    },
    {
      "name": "hyva-themes/magento2-theme-module",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-theme-module.git"
    },
    {
      "name": "hyva-themes/magento2-base-layout-reset",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-base-layout-reset.git"
    },
    {
      "name": "hyva-themes/magento2-compat-module-fallback",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-compat-module-fallback.git"
    },
    {
      "name": "hyva-themes/magento2-mollie-theme-bundle",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-mollie-theme-bundle.git"
    },
    {
      "name": "hyva-themes/magento2-luma-checkout",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-luma-checkout.git"
    },
    {
      "name": "hyva-themes/magento2-theme-fallback",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-theme-fallback.git"
    },
    {
      "name": "hyva-themes/magento2-order-cancellation-webapi",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-order-cancellation-webapi.git"
    },
    {
      "name": "hyva-themes/magento2-email-module",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-email-module.git"
    },
    {
      "name": "hyva-themes/magento2-graphql-view-model",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-graphql-view-model.git"
    },
    {
      "name": "hyva-themes/magento2-default-theme-csp",
      "type": "git",
      "url": "https://github.com/hyva-themes/magento2-default-theme-csp.git"
    }
  ]
}
```

**Source**: `src/composer.json` (repositories section)

### 5.3 Install the theme

```bash
composer require hyva-themes/magento2-default-theme:^1.5 --no-interaction
```

This installs:
- `hyva-themes/magento2-default-theme` 1.5.2
- `hyva-themes/magento2-theme-module` 1.5.2
- `hyva-themes/magento2-base-layout-reset` 2.0.5
- `hyva-themes/magento2-compat-module-fallback` 1.1.4
- `hyva-themes/magento2-email-module` 1.0.6
- `hyva-themes/magento2-graphql-view-model` 1.0.5
- `hyva-themes/magento2-mollie-theme-bundle` 1.1.0
- `hyva-themes/magento2-order-cancellation-webapi` 1.0.1
- Plus Magewire and Mollie compatibility modules

**Source**: `src/composer.lock`

### 5.4 Enable modules

Add to `src/app/etc/config.php`:

```php
'Hyva_Theme' => 1,
'Hyva_BaseLayoutReset' => 1,
'Hyva_CompatModuleFallback' => 1,
'Hyva_Email' => 1,
'Hyva_GraphqlTokens' => 1,
'Hyva_GraphqlViewModel' => 1,
'Hyva_MollieThemeBundle' => 1,
'Hyva_OrderCancellationWebapi' => 1,
```

**Source**: `src/app/etc/config.php`

### 5.5 Run setup upgrade

```bash
bin/magento setup:upgrade --keep-generated
```

**Source**: `src/app/etc/config.php` (module list)

### 5.6 Activate the theme

```sql
UPDATE core_config_data SET value=12 WHERE path='design/theme/theme_id';
```

Where `12` is the `theme_id` for `Hyva/default`:

```sql
SELECT theme_id, theme_path FROM theme WHERE theme_path='Hyva/default';
```

**Source**: `src/app/etc/env.php` (database config)

### 5.7 Deploy static content

```bash
bin/magento setup:static-content:deploy --no-interaction -f
```

### 5.8 Compile DI

```bash
bin/magento setup:di:compile
```

### 5.9 Build Tailwind CSS (optional)

The package ships a pre-built `styles.css`. To regenerate:

```bash
cd vendor/hyva-themes/magento2-default-theme/web/tailwind
npm install
npm run build
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json`

---

## 6. Hyva Tailwind CSS build system

### 6.1 Package scripts

```json
/* vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json */
{
  "name": "@hyva-themes/magento2-default-theme",
  "version": "3.0.0",
  "dependencies": {
    "@hyva-themes/hyva-modules": "^1.4.0",
    "@tailwindcss/cli": "^4.3.1",
    "tailwindcss": "^4.3.1"
  },
  "scripts": {
    "start": "npm run watch",
    "generate": "npx hyva-sources && npx hyva-tokens",
    "watch": "npm run generate && npx tailwindcss -i tailwind-source.css -o ../css/styles.css --watch",
    "browser-sync": "npx browser-sync start --config ./browser-sync.config.cjs",
    "build": "npm run generate && npx tailwindcss -i tailwind-source.css -o ../css/styles.css --minify",
    "build-prod": "npm run build"
  },
  "engines": {
    "node": ">=20.0.0"
  }
}
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/package.json`

### 6.2 Build commands explained

| Command | Purpose |
|---------|---------|
| `npm run generate` | Generates `hyva-source.css` and `hyva-tokens.css` from Hyva modules |
| `npm run watch` | Generates + compiles Tailwind, watches for changes |
| `npm run build` | Generates + compiles Tailwind with minification |

### 6.3 Generated files

```
vendor/hyva-themes/magento2-default-theme/web/tailwind/generated/
    ├── hyva-source.css    ← component classes from Hyva modules
    └── hyva-tokens.css    ← design tokens (colors, spacing, etc.)
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/generated/`

### 6.4 Tailwind v4 changes

Hyva 1.5.x uses **Tailwind CSS v4** (no config file needed):

- `@tailwind base/components/utilities` directives in `tailwind-source.css`
- CSS variables for design tokens (`--color-primary`, `--spacing-4`, etc.)
- `@hyva-themes/hyva-modules` provides the utility and component classes

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/tailwind-source.css`

---

## 7. Customizing styles in Hyva

### 7.1 Create a child theme

```xml
<!-- app/design/frontend/AlpineCommerce/HyvaCustom/etc/theme.xml -->
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       title="AlpineCommerce Hyva Custom"
       parent="Hyva/default"
       type="frontend"/>
```

**Source**: Magento theme inheritance system

### 7.2 Override Tailwind source

In the child theme, create `web/tailwind/tailwind-source.css`:

```css
/* app/design/frontend/AlpineCommerce/HyvaCustom/web/tailwind/tailwind-source.css */
@import 'generated/hyva-tokens.css';
@import 'generated/hyva-source.css';

/* Custom overrides */
@layer components {
    .btn-primary {
        @apply bg-primary text-on-primary hover:bg-primary-darker;
    }
}

@tailwind base;
@tailwind components;
@tailwind utilities;
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/tailwind-source.css`

### 7.3 Override CSS directly

For simple overrides, create a CSS file in the child theme:

```css
/* app/design/frontend/AlpineCommerce/HyvaCustom/web/css/custom.css */
.page-wrapper {
    background-color: var(--color-container-lighter);
}
```

And include it in `etc/view.xml`:

```xml
<!-- app/design/frontend/AlpineCommerce/HyvaCustom/etc/view.xml -->
<page>
    <head>
        <css src="Hyva_Theme::css/styles.css" />
        <css src="AlpineCommerce_HyvaCustom::css/custom.css" />
    </head>
</page>
```

### 7.4 Custom Tailwind config

For advanced customization, modify `hyva.config.json`:

```json
/* app/design/frontend/AlpineCommerce/HyvaCustom/web/tailwind/hyva.config.json */
{
  "prefix": "ac-",
  "important": false,
  "content": [
    "./**/*.phtml",
    "./**/*.xml"
  ]
}
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/tailwind/hyva.config.json`

### 7.5 Build the child theme

```bash
cd app/design/frontend/AlpineCommerce/HyvaCustom/web/tailwind
npm install
npm run build
```

---

## 8. CSS deployment in Magento

### 8.1 Static content deployment

```bash
bin/magento setup:static-content:deploy --no-interaction -f
```

Deploys CSS to:
```
pub/static/frontend/Hyva/default/en_GB/css/styles.css
```

**Source**: `src/vendor/magento/framework/App/StaticContent/Deploy.php`

### 8.2 CSS file locations

```
pub/static/frontend/
    └── Hyva/
        └── default/
            └── en_GB/
                └── css/
                    ├── styles.css          ← main theme CSS
                    └── print.css           ← print styles
```

**Source**: `src/pub/static/frontend/`

### 8.3 Cache and versioning

Magento appends a version query string to CSS URLs:
```
/static/version1789388109/frontend/Hyva/default/en_GB/css/styles.css
```

This is controlled by:
- `dev/static/sign` config (admin: Stores → Configuration → Advanced → Developer)
- `bin/magento cache:flush` regenerates the version

**Source**: `src/vendor/magento/framework/App/StaticContent/Version.php`

### 8.4 LESS compilation (Luma only)

For Luma, Magento compiles LESS on-the-fly or pre-compiles:

```bash
bin/magento dev:asset:compile          # pre-compile LESS
bin/magento dev:asset:clean            # remove compiled files
```

**Source**: `src/vendor/magento/module-dev-asset/`

### 8.5 Hyva does NOT use LESS

Hyva uses Tailwind CSS, so LESS compilation is **not** used for the Hyva theme.
The `magento_import returns empty result` notice in system.log is expected:

```
main.NOTICE: magento_import returns empty result by path css/source/_email.less
for theme Hyva/default
```

This is harmless — Hyva has no `_email.less` file.

**Source**: `src/var/log/system.log`

---

## 9. AlpineCommerce reference

### 9.1 Current state

AlpineCommerce uses **Hyva** as the default frontend theme:

- **Default theme**: `Hyva/default` (theme_id=12 in database)
- **Child theme**: `AlpineCommerce/HyvaCustom` (in `src/app/design/frontend/AlpineCommerce/HyvaCustom/`)
- **Hyva modules enabled**: 9 modules in `src/app/etc/config.php`

**Source**: `src/app/etc/config.php`

### 9.2 Hyva modules enabled

| Module | Package | Purpose |
|--------|---------|---------|
| `Hyva_Theme` | `hyva-themes/magento2-theme-module` | Core Hyva framework |
| `Hyva_BaseLayoutReset` | `hyva-themes/magento2-base-layout-reset` | Reset Luma layout containers |
| `Hyva_CompatModuleFallback` | `hyva-themes/magento2-compat-module-fallback` | Compatibility fallback |
| `Hyva_Email` | `hyva-themes/magento2-email-module` | Email templates |
| `Hyva_GraphqlTokens` | `hyva-themes/magento2-graphql-tokens` | GraphQL auth tokens |
| `Hyva_GraphqlViewModel` | `hyva-themes/magento2-graphql-view-model` | GraphQL view model |
| `Hyva_MollieThemeBundle` | `hyva-themes/magento2-mollie-theme-bundle` | Mollie payment styling |
| `Hyva_OrderCancellationWebapi` | `hyva-themes/magento2-order-cancellation-webapi` | Order cancellation API |
| `FriendsOfHyva_ReactCheckout` | `hyva-themes/magento2-react-checkout` | React checkout |

**Source**: `src/app/etc/config.php`

### 9.3 Hyva extensions config

```json
/* src/app/etc/hyva-themes.json */
{
    "extensions": [
        { "src": "vendor/hyva-themes/magento2-theme-module/src" },
        { "src": "vendor/mollie/magento2-hyva-compatibility/src" },
        { "src": "vendor/magewirephp/magewire/src" }
    ]
}
```

**Source**: `src/app/etc/hyva-themes.json`

### 9.4 Theme configuration

```sql
-- Database: core_config_data
config_id | scope | scope_id | path                  | value
102       | stores| 1        | design/theme/theme_id | 12  (Hyva/default)
464       | default| 0       | design/theme/theme_id | 12  (Hyva/default)
```

**Source**: `src/app/etc/env.php` (database)

### 9.5 Pre-built CSS

The Hyva theme ships a pre-built `styles.css` (101 KB):

```
src/vendor/hyva-themes/magento2-default-theme/web/css/styles.css
```

Deployed to:
```
src/pub/static/frontend/Hyva/default/en_GB/css/styles.css
```

**Source**: `src/vendor/hyva-themes/magento2-default-theme/web/css/styles.css`

---

## 10. Official sources

| Topic | Link |
|-------|------|
| Hyva Theme Documentation | [docs.hyva.io](https://docs.hyva.io/) |
| Hyva Installation Guide | [docs.hyva.io/hyva-themes/installation](https://docs.hyva.io/hyva-themes/installation/) |
| Hyva Theme GitHub | [github.com/hyva-themes/magento2-default-theme](https://github.com/hyva-themes/magento2-default-theme) |
| Tailwind CSS v4 | [tailwindcss.com](https://tailwindcss.com/) |
| Magento 2 Theme System | [developer.adobe.com/commerce/php/docs](https://developer.adobe.com/commerce/php/docs/) |
| Magento LESS Compilation | [developer.adobe.com/commerce/php/docs](https://developer.adobe.com/commerce/php/docs/how-to/website/set_up.html) |

### Source references

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

Hyva references come from `src/vendor/hyva-themes/`.

AlpineCommerce-specific configuration is referenced from
`src/app/code/AlpineCommerce/` and `src/app/etc/`.

*Last updated: 2026-09-14*
