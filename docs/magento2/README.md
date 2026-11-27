# Magento 2 — Quick-Start Learning Path

> **Target**: junior developers new to Magento 2.
> Each article is a short, blog-style walkthrough (~5-10 min read).
> Follow the order; every article links to the previous and next.
> When you need the deep dive, each article points to the full reference doc.

---

## How to use this path

Read **left to right** in each row. Skip ahead only if you are comfortable
with the prerequisite.

| Row | What you'll learn | Time |
|----|-------------------|------|
| [1](#phase-1-core-concepts) | What Magento is, how a request flows | 30 min |
| [2](#phase-2-the-frontend-trinity) | HTML templates, CSS themes, JS modules | 60 min |
| [3](#phase-3-extension-points) | Plugins, observers, service contracts | 45 min |
| [4](#phase-4-apis--admin) | REST/GraphQL, admin UI components | 45 min |
| [5](#phase-5-deep-dives) | Full reference docs (when you need detail) | — |

---

## Phase 1 — Core Concepts

> Prerequisite: PHP OOP. If you need a refresher, read
> [`prerequisites/php-oop.md`](../prerequisites/php-oop.md) first.

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`core/01-what-is-magento.md`** | Magento vs other frameworks, architecture, areas, the big picture |
| 2 | **`core/02-module-anatomy.md`** | A Magento module from the inside: folders, registration, config |
| 3 | **`core/03-request-lifecycle.md`** | Browser → Nginx → index.php → Router → Controller → Repository → Block → Template |
| 4 | **`core/04-dependency-injection.md`** | Object Manager, di.xml, service contracts, interfaces vs implementations |
| 5 | **`core/05-database-model.md`** | EAV vs Flat: why Magento's data model looks the way it does |

**After Phase 1** you can read the full
[`magento-components.md`](magento-components.md) (719 lines, deep dive).

---

## Phase 2 — The Frontend Trinity (HTML, CSS, JS)

> Each article is self-contained but builds on Phase 1.

### HTML — Templates & Layout

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`html/01-layout-xml.md`** | Layout XML: containers, blocks, `<referenceContainer>`, naming convention |
| 2 | **`html/02-phtml-templates.md`** | PHTML files: the PHP+HTML bridge, escaping, security |

_Deep dive_: [`magento-layout-templates.md`](magento-layout-templates.md) (532 lines).

### CSS — Themes & Styles

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`css/01-themes-and-less.md`** | Theme folders, LESS compilation, `_extend.less`, `_theme.less` |
| 2 | **`css/02-luma-vs-hyva.md`** | Luma (LESS) vs Hyvä (Tailwind) — why AlpineCommerce uses both |

_Deep dive_: [`magento-styles.md`](magento-styles.md) (2037 lines).

### JS — RequireJS, Knockout, jQuery

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`js/01-requirejs.md`** | AMD modules, `define()`, `requirejs-config.js`, dependency aliases |
| 2 | **`js/02-knockoutjs.md`** | Observables, computeds, `data-bind`, KO templates |
| 3 | **`js/03-magento-js-libs.md`** | `mage/storage`, `mage/translate`, `mage/mage`, `mage/utils` |
| 4 | **`js/04-integrating-js.md`** | `data-mage-init`, `x-magento-init`, layout XML integration, UI components |

_Deep dive_: [`magento-js.md`](magento-js.md) (849 lines).

---

## Phase 3 — Extension Points

> How to change Magento **without touching core code**.

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`core/06-events-observers.md`** | `events.xml`, writing observers, common events |
| 2 | **`core/07-plugins.md`** | Before/after/around plugins, `di.xml`, sortOrder |
| 3 | **`core/08-preferences.md`** | When to use preferences to swap an interface implementation |

_Deep dive_: [`magento-events-observers-plugins.md`](magento-events-observers-plugins.md) (646 lines).

---

## Phase 4 — APIs & Admin

| # | Article | What it covers |
|---|---------|----------------|
| 1 | **`core/09-rest-graphql.md`** | REST vs GraphQL, `webapi.xml`, service contracts as APIs |
| 2 | **`core/10-ui-components.md`** | Admin grids and forms: XML → JS → HTML |

_Deep dives_: [`magento-rest-graphql.md`](magento-rest-graphql.md),
[`magento-admin.md`](magento-admin.md).

---

## Phase 5 — Deep Dives

These are the **full reference docss** written before this learning path.
They are longer and more detailed — read them when you need the specifics.

| Document | Topic | Length |
|----------|-------|--------|
| [`magento-components.md`](magento-components.md) | Component interactions, request flow, design patterns | 719 lines |
| [`magento-styles.md`](magento-styles.md) | Themes, LESS, Hyvä, Tailwind, fallback, checkout | 2037 lines |
| [`magento-js.md`](magento-js.md) | RequireJS, Knockout, jQuery, mage libraries, integration | 849 lines |
| [`magento-layout-templates.md`](magento-layout-templates.md) | Layout XML, PHTML templates, template fallback | 532 lines |
| [`magento-events-observers-plugins.md`](magento-events-observers-plugins.md) | Events, observers, plugins, preferences | 646 lines |
| [`magento-database.md`](magento-database.md) | EAV, flat tables, table families, relations | 1059 lines |
| [`magento-database-queries.md`](magento-database-queries.md) | SQL, collections, repositories, schema changes | 1672 lines |
| [`magento-database-practice.md`](magento-database-practice.md) | 17 hands-on exercises | 1100 lines |
| [`magento-product-attributes.md`](magento-product-attributes.md) | Product attributes: admin vs data patch | 927 lines |
| [`magento-cli.md`](magento-cli.md) | CLI commands | 631 lines |
| [`magento-composer.md`](magento-composer.md) | Composer, dependencies | 486 lines |
| [`magento-cron-indexers.md`](magento-cron-indexers.md) | Cron, indexers | 602 lines |
| [`magento-multistore.md`](magento-multistore.md) | Websites, stores, store views | 425 lines |
| [`magento-order-lifecycle.md`](magento-order-lifecycle.md) | Order lifecycle, payments | 1675 lines |
| [`magento-payment-providers.md`](magento-payment-providers.md) | Payment providers | 931 lines |
| [`magento-security.md`](magento-security.md) | Security basics | 533 lines |
| [`magento-testing.md`](magento-testing.md) | Testing | 620 lines |
| [`magento-debug.md`](magento-debug.md) | Debugging, workflow | 566 lines |
| [`magento-coding-standards.md`](magento-coding-standards.md) | Coding standards | 660 lines |
| [`magento-admin.md`](magento-admin.md) | Admin basics, UI components | 653 lines |

---

## At a glance

```
Phase 1: Core        →  core/01-05
                         │
Phase 2: Frontend     →  html/01-02, css/01-02, js/01-04
                         │
Phase 3: Extensions   →  core/06-08
                         │
Phase 4: APIs & Admin →  core/09-10
                         │
Phase 5: Deep dives   →  *.md (existing reference docs)
```

_All documentation in this project is in [`docs/README.md`](../README.md)._
