# Magento JavaScript

Magento 2 uses a sophisticated JavaScript stack built on **RequireJS** (AMD module loader), **KnockoutJS** (MVVM framework), and **jQuery**.

## Key libraries

| Library | Purpose |
|---|---|
| **RequireJS** | AMD module loader — loads JS files on demand |
| **KnockoutJS** | MVVM — binds UI components to data models |
| **jQuery** | DOM manipulation, AJAX |
| **mage/*** | Magento's own JS library (validation, forms, UI widgets) |
| **UI Components** | Declarative UI framework for grids, forms, listings |

## Where it's used

- **Checkout**: KnockoutJS-based checkout (one-page checkout)
- **Customer account**: KnockoutJS for address book, order history
- **Admin**: UI Components for grids, forms, charts
- **Product pages**: image gallery, configurable products, related products

## Key files

- `requirejs-config.js` — module mapping and paths
- `web/js/*` — Magento core JS libraries
- `view/frontend/web/*` — module JS files
- `view/adminhtml/web/*` — admin JS files

## Where to go next

- **Full reference**: [`../magento2/magento-js.md`](../magento2/magento-js.md)
- **Layout templates**: [`magento-layout-templates.md`](magento-layout-templates.md)

---

*Prerequisite for: frontend development, checkout customization*
