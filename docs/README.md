# AlpineCommerce Documentation

Welcome to the official AlpineCommerce documentation — a professional e-commerce
platform built on **Magento 2.4.8** (Adobe Commerce Open Source) and open source
reference for learning Magento 2.

## 📂 Documentation Organization

```
docs/
├── README.md                      ← this file (documentation hub)
├── PROJECT_CHARTER.md             ← vision, mission, specifications v1.0
├── ARCHITECTURE.md                ← Magento + AlpineCommerce architecture + ADR registry
├── ENGINEERING_GUIDE.md           ← standards, patterns, workflow, glossary
├── ROADMAP.md                     ← v1.0 development plan and beyond
├── CHANGELOG.md                   ← version history and fixes
├── BACKLOG.md                     ← tracked technical debt (Phase C)
├── magento2/                      ← Magento 2 Core reference docs
│   ├── magento-order-lifecycle.md
│   ├── magento-payment-providers.md
│   ├── magento-admin.md
│   ├── magento-cli.md
│   ├── magento-coding-standards.md
│   ├── magento-components.md
│   ├── magento-composer.md
│   ├── magento-cron-indexers.md
│   ├── magento-debug.md
│   ├── magento-events-observers-plugins.md
│   ├── magento-intro.md
│   ├── magento-js.md
│   ├── magento-layout-templates.md
│   ├── magento-multistore.md
│   ├── magento-rest-graphql.md
│   ├── magento-security.md
│   └── magento-testing.md
├── modules/                       ← each module's chapter (Phase D)
│   ├── AUTO_INVOICE.md
│   ├── BLOG.md
│   ├── CREDIT_MEMO.md
│   ├── CUSTOMER_CARE.md
│   ├── CUSTOMER_GRID.md
│   ├── EU_VAT.md
│   ├── FAQ.md
│   ├── GDPR.md
│   ├── HREFLANG.md
│   ├── LEGAL_PAGES.md
│   ├── LOYALTY_PROGRAM.md
│   ├── PARTIAL_INVOICE.md
│   ├── PRODUCT_LABELS.md
│   ├── PRODUCT_QUESTIONS.md
│   ├── PRODUCT_REVIEWS.md
│   ├── RMA.md
│   ├── STORE_LOCATOR.md
│   ├── STORE_PICKUP.md
│   ├── STORE_SETUP.md
│   └── alpinecommerce-order-lifecycle.md
├── prerequisites/                 ← foundational guides
│   ├── docker.md
│   ├── php-oop.md
│   ├── git-github.md
│   ├── magento-intro.md
│   ├── magento-js.md
│   ├── magento-layout-templates.md
│   ├── magento-cli.md
│   ├── magento-cron-indexers.md
│   ├── magento-events-observers-plugins.md
│   ├── magento-rest-graphql.md
│   ├── magento-composer.md
│   ├── magento-debug.md
│   ├── magento-admin.md
│   ├── magento-security.md
│   ├── magento-multistore.md
│   ├── magento-testing.md
│   ├── magento-coding-standards.md
│   └── ci-cd.md
└── archive/sprints/               ← historical sprint reports
```

---

## 📖 Main Documents

| Document | Role |
|---|---|
| [`PROJECT_CHARTER.md`](PROJECT_CHARTER.md) | 🎯 Vision, philosophy, specifications and functional analysis v1.0 |
| [`ARCHITECTURE.md`](ARCHITECTURE.md) | 🏗️ Magento + AlpineCommerce architecture + ADR registry |
| [`ENGINEERING_GUIDE.md`](ENGINEERING_GUIDE.md) | 📐 Standards, patterns, anti-patterns, glossary |
| [`ROADMAP.md`](ROADMAP.md) | 🗺️ Product roadmap and version history |
| [`CHANGELOG.md`](CHANGELOG.md) | 📜 Version history and fixes |
| [`BACKLOG.md`](BACKLOG.md) | 🛠️ Tracked technical debt (Phase C) |

---

## 🧩 Module Documents

Each AlpineCommerce module has its own document — **self-contained** with
responsibility, scope, architecture, API, decisions, and known bugs.

### Stable Modules

| Module | Document | Status |
|---|---|---|
| Blog | [`BLOG.md`](modules/BLOG.md) | ✅ Stable |
| Faq | [`FAQ.md`](modules/FAQ.md) | ✅ Stable |
| LegalPages | [`LEGAL_PAGES.md`](modules/LEGAL_PAGES.md) | ✅ Stable |
| ProductReviews | [`PRODUCT_REVIEWS.md`](modules/PRODUCT_REVIEWS.md) | ✅ Stable |
| ProductQuestions | [`PRODUCT_QUESTIONS.md`](modules/PRODUCT_QUESTIONS.md) | ✅ Stable |
| ProductLabels | [`PRODUCT_LABELS.md`](modules/PRODUCT_LABELS.md) | ✅ Stable |
| CustomerGrid | [`CUSTOMER_GRID.md`](modules/CUSTOMER_GRID.md) | ✅ Stable |
| CustomerCare | [`CUSTOMER_CARE.md`](modules/CUSTOMER_CARE.md) | ✅ Stable |
| StoreSetup | [`STORE_SETUP.md`](modules/STORE_SETUP.md) | ✅ Stable |

### Modules in Finalization

| Module | Document | Status |
|---|---|---|
| AutoInvoice | [`AUTO_INVOICE.md`](modules/AUTO_INVOICE.md) | ✅ Done |
| CreditMemo | [`CREDIT_MEMO.md`](modules/CREDIT_MEMO.md) | ✅ Done |
| PartialInvoice | [`PARTIAL_INVOICE.md`](modules/PARTIAL_INVOICE.md) | ✅ Done |
| Rma | [`RMA.md`](modules/RMA.md) | ✅ Done |
| Gdpr | [`GDPR.md`](modules/GDPR.md) | ✅ Done |
| StorePickup | [`STORE_PICKUP.md`](modules/STORE_PICKUP.md) | ✅ Done |
| StoreLocator | [`STORE_LOCATOR.md`](modules/STORE_LOCATOR.md) | ✅ Done |
| LoyaltyProgram | [`LOYALTY_PROGRAM.md`](modules/LOYALTY_PROGRAM.md) | ✅ Done |
| EuVat | [`EU_VAT.md`](modules/EU_VAT.md) | ⏳ To be finalized |
| Hreflang | [`HREFLANG.md`](modules/HREFLANG.md) | ⏳ To be finalized |

### Cross-Cutting Documentation

| Document | What you'll learn |
|---|---|
| [`alpinecommerce-order-lifecycle.md`](modules/alpinecommerce-order-lifecycle.md) | 🔄 Complete AlpineCommerce order lifecycle: AutoInvoice, PartialInvoice, CreditMemo, CustomerCare, LoyaltyProgram, Rma, StorePickup |

---

## 🎓 Prerequisites Guides

If you are new to Magento, start with these guides **in order**:

| Guide | Topic | Who needs it |
|---|---|---|
| [`docker.md`](prerequisites/docker.md) | Docker installation, containers, volumes, docker-compose | Everyone |
| [`php-oop.md`](prerequisites/php-oop.md) | Classes, objects, inheritance, interfaces, DI, namespaces | Developers new to OOP |
| [`git-github.md`](prerequisites/git-github.md) | Git commands, branching, pull requests, GitHub workflow | Anyone contributing code |
| [`ci-cd.md`](prerequisites/ci-cd.md) | CI/CD concepts, GitHub Actions, automated pipelines | Everyone understanding deployment |

These guides are written for absolute beginners and use AlpineCommerce
examples throughout.

For Magento-specific prerequisites, see the full reference docs in [`magento2/`](magento2/).

---

## 🚀 Entry points by profile

- **Beginner developer**: start with [`prerequisites/docker.md`](prerequisites/docker.md) and
  [`prerequisites/php-oop.md`](prerequisites/php-oop.md), then [`prerequisites/git-github.md`](prerequisites/git-github.md),
  then [`prerequisites/ci-cd.md`](prerequisites/ci-cd.md), then [`PROJECT_CHARTER.md`](PROJECT_CHARTER.md) (the "why"),
  then [`ARCHITECTURE.md`](ARCHITECTURE.md) (the "how"), then the canonical module document
  [`FAQ.md`](modules/FAQ.md).
- **Intermediate developer**: [`ENGINEERING_GUIDE.md`](ENGINEERING_GUIDE.md) is your reference;
  compare each module to the canonical skeleton.
- **Contributor / maintainer**: [`ENGINEERING_GUIDE.md`](ENGINEERING_GUIDE.md) (validation checklist),
  [`BACKLOG.md`](BACKLOG.md) (debt to address), [`CHANGELOG.md`](CHANGELOG.md) (history of fix decisions).

---

## 🔗 Quick Links

### Documentation
- [📚 Documentation hub](README.md)
- [🏗️ Architecture](ARCHITECTURE.md)
- [📐 Engineering Guide](ENGINEERING_GUIDE.md)
- [🗺️ Roadmap](ROADMAP.md)
- [📜 Changelog](CHANGELOG.md)

### Magento 2 Reference
- [📦 Order Lifecycle](magento2/magento-order-lifecycle.md)
- [💳 Payment Providers](magento2/magento-payment-providers.md)
- [🎯 Events, Observers, Plugins](magento2/magento-events-observers-plugins.md)
- [🔌 REST API & GraphQL](magento2/magento-rest-graphql.md)
- [🖥️ Admin Panel](magento2/magento-admin.md)

### AlpineCommerce Modules
- [🔄 Complete Order Lifecycle](modules/alpinecommerce-order-lifecycle.md)
- [🛒 AutoInvoice](modules/AUTO_INVOICE.md)
- [💬 CustomerCare](modules/CUSTOMER_CARE.md)
- [🎁 LoyaltyProgram](modules/LOYALTY_PROGRAM.md)
- [🚚 StorePickup](modules/STORE_PICKUP.md)

### Code
- [📦 Modules](../src/app/code/AlpineCommerce/)
- [🎨 Theme](../src/app/design/)
- [⚙️ Module config](../src/app/etc/config.php)

---

*Last updated: 2026-09-07*
