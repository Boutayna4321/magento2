# Magento 2 from Scratch — What Even Is It?

> **Series**: [Learning Path](../README.md) → Phase 1, Article 1 of 5
> **Previous**: — (first article)
> **Next**: [`02-module-anatomy.md`](02-module-anatomy.md) — Inside a Magento module
> **Deep dive**: [`../magento-components.md`](../magento-components.md), [`../magento-intro.md`](../magento-intro.md)

---

Magento is **not** like Laravel or Symfony. If you come from a "classic" PHP
framework, you'll run into unfamiliar terms: EAV, service contracts, plugins,
UI components, RequireJS, Knockout, declarative schema.

This article strips away the jargon. By the end you will understand **what
Magento is for**, **why it is so big**, and **how a request travels** from
browser to database. No code yet — that comes in the next article.

---

## 1. Magento in one sentence

> Magento is an e-commerce platform built as a collection of **interchangeable
> modules**, each declaring its own database tables, routes, admin menus, CSS,
> JavaScript and API endpoints — all wired together by a dependency-injection
> container and an event system.

It is not an application framework that you **borrow** to build an app. It is an
**app you customize**. The distinction matters: when you create a product
attribute or place an order, you are exercising Magento's own business logic,
not writing your own.

---

## 2. Why Magento is so big

A fresh Magento 2.4.8 install pulls in **465 database tables** and ships
**~400 modules** from `vendor/magento/module-*`. Three reasons:

| Reason | What it means |
|--------|---------------|
| **Batteries included** | Catalog, cart, checkout, payments, shipping, customers, taxes, cache, admin grid, reports — all shipped |
| **EAV data model** | Products have dynamic attributes, so data is spread across many tables |
| **Multi-store** | One install can run 3 websites in 5 languages with different prices, domains and payment methods |

You are not expected to memorize it. You are expected to learn **where to look**
and **how the pieces connect**.

---

## 3. Areas — the four faces of Magento

The same code can run in different contexts called **areas**:

| Area | URL prefix | What it serves |
|------|-----------|----------------|
| `frontend` | `/` | The storefront (customer-facing pages) |
| `adminhtml` | `/admin` | The admin panel |
| `webapi_rest` | `/rest/` | REST API endpoints |
| `graphql` | `/graphql` | GraphQL queries |

A module can register different routes, layouts, templates and configuration
for each area. That is why the module folder structure always has
`etc/frontend/`, `etc/adminhtml/`, etc.

---

## 4. The request lifecycle (big picture)

```
Browser
  │ GET /blog
  ▼
Nginx (serves CSS/JS, passes PHP to PHP-FPM)
  │
  ▼
index.php  ←  single entry point (bootstraps Magento)
  │
  ▼
Front Controller  ←  identifies the area (frontend, adminhtml, ...)
  │
  ▼
Router  ←  reads routes.xml, maps URL → Controller class
  │
  ▼
Controller  ←  orchestrates: calls a Repository, returns a ResultPage
  │
  ▼
Repository  ←  business logic (load/save, delegates to ResourceModel)
  │
  ▼
ResourceModel  ←  raw SQL
  │
  ▼
MySQL  ←  the data
  │
  ▼
Block + Template (.phtml)  ←  prepares and renders HTML
  │
  ▼
Browser
```

> **Key principle**: the Controller **never** talks to the database directly.
> It calls a **Repository** (a Service Contract). The Repository delegates to a
> ResourceModel for SQL. This separation is what makes APIs and tests possible.

---

## 5. Two data models: EAV and Flat

Magento stores data in **two** different ways:

- **Flat tables** — one row per entity, real columns (orders, invoices, customers).
  Simple, fast, familiar SQL.

- **EAV (Entity-Attribute-Value)** — used for products and categories. Data is
  split across `eav_attribute` (defines what attributes exist) and
  `catalog_product_entity_*` (one table per data type: `_varchar`, `_int`,
  `_text`, `_decimal`, `_datetime`). Flexible, but queries are complex.

> The LumaCheckout `_extend.less` you have open? That is purely a CSS
> override — it touches neither data model. The templates and JavaScript for
> the checkout come from the **parent theme** (`Magento/luma`) through Magento's
> file fallback system.

---

## 6. Themes and the frontend stack

Magento supports **two frontend architectures**:

| Architecture | Used by | CSS | JS |
|-------------|---------|-----|-----|
| **Luma** (legacy) | Checkout, admin | LESS + UI library | jQuery, RequireJS, Knockout |
| **Hyvä** (modern) | Storefront | Tailwind CSS | Alpine.js (+ Magewire) |

AlpineCommerce uses **Hyvä for the storefront** and **Luma only for the
checkout** (via the Hyvä Luma theme fallback). That is why the
`LumaCheckout` theme only contains a `_extend.less` — it overrides checkout
colors, nothing more.

---

## 7. What you should remember

1. **Magento is a collection of modules**, not a framework to build on top of.
2. **Every module** declares routes, DB schema, admin menus, APIs — independently.
3. A request goes **Front Controller → Router → Controller → Repository →
   ResourceModel → MySQL → Block → Template**.
4. The Controller **never** touches SQL; it always calls a Repository.
5. **Two data models**: EAV (flexible, complex) and Flat (simple, fast).
6. **Two frontend stacks**: Luma (LESS/jQuery) and Hyvä (Tailwind/Alpine.js).

---

## 8. Where to go next

- **Article 2**: [Module Anatomy](02-module-anatomy.md) — open a module and see
  what every folder does.
- **Deep dive**: [`magento-components.md`](../magento-components.md) — the full
  component interaction diagram.
- **Frontend**: [`html/01-layout-xml.md`](../html/01-layout-xml.md) covers how
  pages are assembled; [`css/01-themes-and-less.md`](../css/01-themes-and-less.md)
  covers themes and styling.

---

*All code references in this series come from the Magento 2.4.8 source in
`src/vendor/magento/`. AlpineCommerce examples come from `src/app/code/AlpineCommerce/`.*
