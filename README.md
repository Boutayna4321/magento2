# AlpineCommerce — Learn Magento 2 with a real e-commerce project

> **A repository for two purposes:** a real e-commerce platform built on
> **Magento 2.4.8** (Adobe Commerce Open Source), and a **complete practical course** that takes
> you from "I don't know Magento" to "I develop a professional module".

```
┌──────────────────────────────────────────────────────────────┐
│   AlpineCommerce                                              │
│   ├── E-commerce platform (19 business modules)               │
│   └── Magento 2 Course                                        │
│       ├── docs/README.md                    → the documentation hub  │
│       ├── docs/ENGINEERING_GUIDE.md         → the standards          │
│       ├── docs/magento2/*.md                → Magento Core references │
│       ├── docs/modules/*.md                 → each module's chapter  │
│       └── docs/prerequisites/*.md           → foundational guides   │
└──────────────────────────────────────────────────────────────┘
```

---

## What is Magento 2?

**Magento 2** is an open-source e-commerce platform written in PHP, published by Adobe.
It comes in two editions:

| Edition | What it is |
|---|---|
| **Adobe Commerce** (formerly Magento Commerce) | Paid edition: B2B features, advanced MSI, Page Builder, Adobe support, optional cloud. |
| **Magento Open Source** | Free edition: the complete e-commerce core, extensible via modules. **This is the one we use.** |

**Why Magento exists**: online stores quickly become complex (multi-store,
millions of product files, validation workflows, ERP integrations, large-scale
customization). Magento structures this complexity with a modular architecture, instead
of recoding everything for each project.

**4 key takeaways**
1. **Modular**: everything is a module. You add a feature by creating a module, not by modifying the core.
2. **Extensible without modification**: Plugins, Observers, Layout XML, and DI allow you to change behavior without touching the original code.
3. **Service Contracts**: every business capability is exposed via an interface — the same API serves the frontend, the admin, and the REST API.
4. **EAV + tables**: the data model combines flexible attributes (EAV) and optimized flat tables.

**General architecture** (the phrase to remember)

```
HTTP Request
    ↓
Router (URL router)
    ↓
Controller (orchestrates, does not handle business logic)
    ↓
Service Contract (business interface)
    ↓
Repository (implementation, encapsulates data access)
    ↓
ResourceModel (talks to the database)
    ↓
Database (MySQL)
    ↓
Response (HTML, JSON...)
```

Each step is explained in detail in `docs/ARCHITECTURE.md`.

---

## Why choose Magento?

**Projects that use Magento**: large and medium-sized retailers with large catalogs,
multi-store, B2B and B2C, need for deep customization and integrations
(ERP, PIM, payment, logistics).

| ✅ Advantages | ❌ Disadvantages |
|---|---|
| Complete ecosystem (commerce, CMS, promotions, B2B) | Steep learning curve |
| Extensible without touching the core | Resource-intensive (not for a simple storefront) |
| Native multi-store / multi-language | Production infrastructure cost |
| Large community and extension marketplace | Extensions can conflict |
| REST API + GraphQL | Specialized hosting recommended |

**When Magento is NOT a good solution**
- Simple store (< 100 products, no customization) → a SaaS (Shopify, WooCommerce) is enough.
- Static storefront without payment → unnecessarily heavy.
- Team without PHP/Symfony experience → the learning curve will be a hurdle.

---

## The AlpineCommerce Philosophy

> **Always extend before creating.**

We **never** write our own versions of Catalog, Customer, Sales, Checkout,
Inventory, or CMS: these blocks are provided by Magento and proven. We **extend** them.

Extension order (from least intrusive to most intrusive):

```
Plugin        → intercept an existing method
Observer      → react to a business event
Layout XML    → modify the page structure
DI Preference → replace a class (last resort)
New module    → only for new business value
```

See `docs/ENGINEERING_GUIDE.md` → section "❌ What you must NEVER do".

---

## The project in brief

- **Target version**: Magento 2.4.8 (PHP 8.2)
- **19 business modules** in `src/app/code/AlpineCommerce/` — each illustrates specific Magento
  concepts (see `docs/README.md` → "Module documents")
- **Frontend**: custom Luma-based theme in `src/app/design/`
- **API**: REST + GraphQL
- **Environment**: Docker (PHP-FPM, Nginx, MySQL, Redis, Elasticsearch)

---

## Repository Structure

```
.
├── src/
│   ├── app/
│   │   ├── code/
│   │   │   ├── AlpineCommerce/        ← 19 custom business modules
│   │   │   │   ├── AutoInvoice/
│   │   │   │   ├── Blog/
│   │   │   │   ├── CreditMemo/
│   │   │   │   ├── CustomerCare/
│   │   │   │   ├── CustomerGrid/
│   │   │   │   ├── EuVat/
│   │   │   │   ├── Faq/
│   │   │   │   ├── Gdpr/
│   │   │   │   ├── HealthCheck/
│   │   │   │   ├── Hreflang/
│   │   │   │   ├── LegalPages/
│   │   │   │   ├── LoyaltyProgram/
│   │   │   │   ├── PartialInvoice/
│   │   │   │   ├── ProductLabels/
│   │   │   │   ├── ProductQuestions/
│   │   │   │   ├── ProductReviews/
│   │   │   │   ├── Rma/
│   │   │   │   ├── StoreLocator/
│   │   │   │   ├── StorePickup/
│   │   │   │   ├── StoreSetup/
│   │   │   │   └── Test/
│   │   │   └── Cartware/              ← additional custom modules
│   │   ├── design/                    ← custom Luma-based theme
│   │   └── etc/
│   │       └── config.php             ← module status/config
│   ├── vendor/                        ← Composer dependencies (840MB, not committed)
│   ├── generated/                     ← generated code (not committed)
│   ├── pub/
│   │   ├── media/                     ← uploaded images/products (not committed)
│   │   └── static/                    ← static assets (not committed)
│   └── var/                           ← cache, sessions, logs (not committed)
├── docs/                              ← complete documentation
│   ├── README.md                      ← documentation hub
│   ├── PROJECT_CHARTER.md             ← vision, philosophy, specifications
│   ├── ENGINEERING_GUIDE.md           ← standards, patterns, anti-patterns
│   ├── ARCHITECTURE.md                ← Magento + AlpineCommerce architecture
│   ├── ROADMAP.md                     ← product roadmap and version history
│   ├── CHANGELOG.md                   ← version history and fixes
│   ├── BACKLOG.md                     ← tracked technical debt
│   ├── magento2/                      ← Magento Core reference docs
│   │   ├── magento-order-lifecycle.md
│   │   ├── magento-payment-providers.md
│   │   ├── magento-admin.md
│   │   ├── magento-cli.md
│   │   ├── magento-coding-standards.md
│   │   ├── magento-components.md
│   │   ├── magento-composer.md
│   │   ├── magento-cron-indexers.md
│   │   ├── magento-debug.md
│   │   ├── magento-events-observers-plugins.md
│   │   ├── magento-intro.md
│   │   ├── magento-js.md
│   │   ├── magento-layout-templates.md
│   │   ├── magento-multistore.md
│   │   ├── magento-rest-graphql.md
│   │   ├── magento-security.md
│   │   └── magento-testing.md
│   ├── modules/                       ← each module's chapter
│   │   ├── AUTO_INVOICE.md
│   │   ├── BLOG.md
│   │   ├── CREDIT_MEMO.md
│   │   ├── CUSTOMER_CARE.md
│   │   ├── CUSTOMER_GRID.md
│   │   ├── EU_VAT.md
│   │   ├── FAQ.md
│   │   ├── GDPR.md
│   │   ├── HREFLANG.md
│   │   ├── LEGAL_PAGES.md
│   │   ├── LOYALTY_PROGRAM.md
│   │   ├── PARTIAL_INVOICE.md
│   │   ├── PRODUCT_LABELS.md
│   │   ├── PRODUCT_QUESTIONS.md
│   │   ├── PRODUCT_REVIEWS.md
│   │   ├── RMA.md
│   │   ├── STORE_LOCATOR.md
│   │   ├── STORE_PICKUP.md
│   │   ├── STORE_SETUP.md
│   │   └── alpinecommerce-order-lifecycle.md
│   ├── prerequisites/                 ← foundational guides
│   │   ├── docker.md
│   │   ├── php-oop.md
│   │   ├── git-github.md
│   │   ├── magento-intro.md
│   │   ├── magento-js.md
│   │   ├── magento-layout-templates.md
│   │   ├── magento-cli.md
│   │   ├── magento-cron-indexers.md
│   │   ├── magento-events-observers-plugins.md
│   │   ├── magento-rest-graphql.md
│   │   ├── magento-composer.md
│   │   ├── magento-debug.md
│   │   ├── magento-admin.md
│   │   ├── magento-security.md
│   │   ├── magento-multistore.md
│   │   ├── magento-testing.md
│   │   ├── magento-coding-standards.md
│   │   └── ci-cd.md
│   └── archive/sprints/               ← historical sprint reports
├── docker-compose.yml                  ← Docker service definitions
├── Dockerfile                          ← PHP/Nginx image
├── php.ini                             ← PHP configuration
├── nginx/                              ← Nginx config
├── scripts/                            ← installation and management scripts
├── composer.json                       ← project dependencies
├── composer.lock                       ← locked dependencies
├── .env.example                        ← Docker environment template
└── README.md                           ← this file
```

---

## Documentation

### Core Documentation

| Document | Role |
|---|---|
| `docs/README.md` | 🎯 The official documentation hub |
| `docs/PROJECT_CHARTER.md` | 🎓 Vision, philosophy, specifications and functional analysis v1.0 |
| `docs/ENGINEERING_GUIDE.md` | 📐 The Engineering Bible: standards and anti-patterns |
| `docs/ARCHITECTURE.md` | 🏗️ Magento and AlpineCommerce architecture + ADR registry |
| `docs/ROADMAP.md` | 🗺️ The product roadmap and version history |
| `docs/CHANGELOG.md` | 📜 Version history and fixes |
| `docs/BACKLOG.md` | 🛠️ Tracked technical debt (Phase C) |

### Magento 2 Reference Docs (`docs/magento2/`)

| Document | Role |
|---|---|
| `magento-order-lifecycle.md` | 📦 Complete order lifecycle: quote → order → invoice → shipment → credit memo |
| `magento-payment-providers.md` | 💳 Payment architecture: methods, gateways, transactions |
| `magento-admin.md` | 🖥️ Admin panel: grids, forms, ACL, system configuration |
| `magento-cli.md` | ⌨️ `bin/magento` commands: setup, cache, deploy, indexer |
| `magento-coding-standards.md` | 📏 PSR-12, naming conventions, code style |
| `magento-components.md` | 🧩 Magento component types: modules, themes, language packs |
| `magento-composer.md` | 📦 Composer workflows, dependencies, updates |
| `magento-cron-indexers.md` | ⏰ Cron jobs, indexers (realtime/schedule), flat tables |
| `magento-debug.md` | 🐛 Logs, developer mode, Xdebug, common errors |
| `magento-events-observers-plugins.md` | 🎯 Events, observers, plugins (interceptors) |
| `magento-intro.md` | 📖 Magento architecture, modules, EAV, multi-store |
| `magento-js.md` | 📜 RequireJS, KnockoutJS, jQuery, mage/* libraries |
| `magento-layout-templates.md` | 🎨 Layout XML, containers, blocks, PHTML templates |
| `magento-multistore.md` | 🌐 Websites/stores/store views, scope hierarchy |
| `magento-rest-graphql.md` | 🔌 REST API, GraphQL, authentication, service contracts |
| `magento-security.md` | 🔒 Form keys, ACL, validation, XSS, CSRF |
| `magento-testing.md` | 🧪 Unit tests, integration tests, functional tests |

### Module Documents (`docs/modules/`)

Each AlpineCommerce module has its own document — **self-contained** with
responsibility, scope, architecture, API, decisions, and known bugs.

#### Stable Modules

| Module | Document | Status |
|---|---|---|
| Blog | `modules/BLOG.md` | ✅ Stable |
| Faq | `modules/FAQ.md` | ✅ Stable |
| LegalPages | `modules/LEGAL_PAGES.md` | ✅ Stable |
| ProductReviews | `modules/PRODUCT_REVIEWS.md` | ✅ Stable |
| ProductQuestions | `modules/PRODUCT_QUESTIONS.md` | ✅ Stable |
| ProductLabels | `modules/PRODUCT_LABELS.md` | ✅ Stable |
| CustomerGrid | `modules/CUSTOMER_GRID.md` | ✅ Stable |
| CustomerCare | `modules/CUSTOMER_CARE.md` | ✅ Stable |
| StoreSetup | `modules/STORE_SETUP.md` | ✅ Stable |

#### Modules in Finalization

| Module | Document | Status |
|---|---|---|
| AutoInvoice | `modules/AUTO_INVOICE.md` | ✅ Done |
| CreditMemo | `modules/CREDIT_MEMO.md` | ✅ Done |
| PartialInvoice | `modules/PARTIAL_INVOICE.md` | ✅ Done |
| Rma | `modules/RMA.md` | ✅ Done |
| Gdpr | `modules/GDPR.md` | 🔄 Finalization |
| StorePickup | `modules/STORE_PICKUP.md` | 🔄 Finalization |
| StoreLocator | `modules/STORE_LOCATOR.md` | 🔄 Finalization |
| LoyaltyProgram | `modules/LOYALTY_PROGRAM.md` | ⏳ To be finalized |
| EuVat | `modules/EU_VAT.md` | ⏳ To be finalized |
| Hreflang | `modules/HREFLANG.md` | ⏳ To be finalized |

#### Cross-Cutting Documentation

| Document | Role |
|---|---|
| `alpinecommerce-order-lifecycle.md` | 🔄 Complete AlpineCommerce order lifecycle: AutoInvoice, PartialInvoice, CreditMemo, CustomerCare, LoyaltyProgram, Rma, StorePickup |

### Prerequisites Guides (`docs/prerequisites/`)

If you are new to Magento, start with these guides **in order**:

| Guide | Topic | Who needs it |
|---|---|---|
| `docker.md` | Docker installation, containers, volumes, docker-compose | Everyone |
| `php-oop.md` | Classes, objects, inheritance, interfaces, DI, namespaces | Developers new to OOP |
| `git-github.md` | Git commands, branching, pull requests, GitHub workflow | Anyone contributing code |
| `magento-intro.md` | Magento architecture, modules, EAV, multi-store, themes | Everyone new to Magento |
| `magento-js.md` | RequireJS, KnockoutJS, jQuery, mage/* libraries | Everyone writing frontend JS |
| `magento-layout-templates.md` | Layout XML, containers, blocks, PHTML templates | Everyone modifying frontend |
| `magento-cli.md` | `bin/magento` commands: module, upgrade, compile, cache, deploy | Everyone developing on Magento |
| `magento-cron-indexers.md` | Cron jobs, indexers (realtime/schedule), flat tables | Everyone understanding automation |
| `magento-events-observers-plugins.md` | Events, observers, plugins (interceptors) | Everyone extending Magento |
| `magento-rest-graphql.md` | REST API, GraphQL, authentication, service contracts | Everyone integrating APIs |
| `magento-composer.md` | Composer, packages, autoload, updating dependencies | Everyone installing dependencies |
| `magento-debug.md` | Logs, developer mode, Xdebug, common errors | Everyone (debugging is daily work) |
| `magento-admin.md` | Admin navigation, ACL, menus, system.xml, UI Components | Everyone using/extending admin |
| `magento-security.md` | Form keys, ACL, validation, XSS, CSRF, sanitization | Everyone writing secure code |
| `magento-multistore.md` | Websites/stores/store views, scope hierarchy, config fallback | Everyone working with multiple stores |
| `magento-testing.md` | Unit tests, integration tests, API functional tests | Everyone writing/running tests |
| `magento-coding-standards.md` | PSR-12, naming conventions, module structure | Everyone writing code |
| `ci-cd.md` | CI/CD concepts, GitHub Actions, automated pipelines | Everyone understanding deployment |

These guides are written for absolute beginners and use AlpineCommerce
examples throughout.

---

## Entry points by profile

- **Beginner developer**: start with `prerequisites/docker.md` and
  `prerequisites/php-oop.md`, then `prerequisites/git-github.md`, then
  `prerequisites/magento-intro.md`, then `prerequisites/magento-js.md`,
  then `prerequisites/magento-layout-templates.md`, then `prerequisites/magento-cli.md`,
  then `prerequisites/magento-cron-indexers.md`,
  then `prerequisites/magento-events-observers-plugins.md`,
  then `prerequisites/magento-rest-graphql.md`, then `prerequisites/magento-composer.md`,
  then `prerequisites/magento-debug.md`, then `prerequisites/magento-admin.md`,
  then `prerequisites/magento-security.md`, then `prerequisites/magento-multistore.md`,
  then `prerequisites/magento-testing.md`, then `PROJECT_CHARTER.md` (the "why"),
  then `ARCHITECTURE.md` (the "how"), then the canonical module document
  `modules/FAQ.md`.
- **Intermediate developer**: `ENGINEERING_GUIDE.md` is your reference;
  compare each module to the canonical skeleton.
- **Contributor / maintainer**: `ENGINEERING_GUIDE.md` (validation checklist),
  `BACKLOG.md` (debt to address), `CHANGELOG.md` (history of fix decisions).

---

## Link to code

- Modules: `src/app/code/AlpineCommerce/*`
- Custom theme: `src/app/design/`
- The documentation is the **Source of Truth**: every architecture decision is tracked there,
  all code must respect it, any modification is validated.

---

# Magento 2.4.8 - Docker Development Environment

## What's in the repository

This repository contains the source code for the Magento 2.4.8 project along with a full Docker development environment.

### Tracked (committed to git)
- `src/app/code/` — Custom modules (AlpineCommerce/*, Cartware/*)
- `src/app/design/` — Custom themes
- `src/app/etc/config.php` — Module status/config (safe to commit)
- `src/composer.json` — Project dependencies manifest
- `src/bin/magento` — Magento CLI
- `docker-compose.yml` — Docker service definitions
- `Dockerfile`, `php.ini`, `nginx/` — PHP and Nginx config
- `scripts/` — Installation and management scripts
- `.env.example` — Template for Docker environment variables
- `docs/` — Complete documentation

### Excluded (NOT committed to git — regenerated locally)
- `src/vendor/` — Composer dependencies (840MB, restore via `composer install`)
- `src/generated/` — Generated code (regenerated by Magento)
- `src/pub/media/` — Uploaded images/products (273MB, user-generated content)
- `src/pub/static/` — Static assets (regenerated via `setup:static-content:deploy`)
- `src/var/` — Cache, sessions, logs, reports
- `src/app/etc/env.php` — Database credentials and environment-specific config
- `.env` — Docker environment variables (contains passwords)

## Prerequisites

1. **Docker Desktop** (or Docker Engine on Linux, Docker Desktop on macOS/Windows)
2. **Docker Compose** plugin (included with Docker Desktop 4.0+)
3. **Magento Marketplace account** (free) — get your keys at [developer.adobe.com](https://developer.adobe.com/commerce/marketplace/)

### Windows prerequisites
- **Docker Desktop for Windows** with WSL 2 backend
- **Git Bash** (comes with Git for Windows) **or** **PowerShell 7+**
- Use the `.ps1` scripts in `scripts/` instead of the `.sh` scripts

## Setup for new team members

### Linux / macOS

```bash
# 1. Clone the repository
git clone https://github.com/Boutayna4321/magento2.git
cd magento2

# 2. Copy the environment template and adjust values if needed
cp .env.example .env

# 3. Start Docker containers and install Magento
chmod +x scripts/install.sh
./scripts/install.sh
```

### Windows (PowerShell)

```powershell
# 1. Clone the repository
git clone https://github.com/Boutayna4321/magento2.git
cd magento2

# 2. Copy the environment template
copy .env.example .env

# 3. Start Docker containers and install Magento
.\scripts\install.ps1
```

When prompted for Magento auth keys, paste your Marketplace **Public Key** and **Private Key**. The script will:
- Build and start all containers (PHP, Nginx, MySQL, Elasticsearch, Redis)
- Install Magento via Composer
- Run `setup:install` with database and cache configuration
- Deploy static content and reindex

### Access after installation
| Service         | URL / Host          |
|-----------------|---------------------|
| Storefront      | http://localhost:8080 |
| Admin Panel     | http://localhost:8080/admin |
| Admin User      | admin / admin123   |
| MySQL           | localhost:3306     |
| Elasticsearch   | localhost:9200     |
| Redis           | localhost:6379     |

## Useful commands

### Linux / macOS
| Command                    | Description              |
|----------------------------|--------------------------|
| `scripts/start.sh`         | Start containers         |
| `scripts/stop.sh`          | Stop containers          |
| `scripts/magento-cli.sh`   | Run Magento CLI command  |

Example: `./scripts/magento-cli.sh cache:flush`

### Windows (PowerShell)
| Command                    | Description              |
|----------------------------|--------------------------|
| `.\scripts\start.ps1`      | Start containers         |
| `.\scripts\stop.ps1`       | Stop containers          |
| `.\scripts\magento-cli.ps1`| Run Magento CLI command  |

Example: `.\scripts\magento-cli.ps1 cache:flush`

## Restoring from a backup

If you have a database dump or media backup:
1. Start containers: `docker compose up -d`
2. Import database: `docker compose exec mysql mysql -u root -pYOUR_MYSQL_ROOT_PASSWORD magento2 < backup.sql`
3. Copy media files: `cp -r /path/to/media/* src/pub/media/`
4. Reindex: `./scripts/magento-cli.sh indexer:reindex`
5. Flush cache: `./scripts/magento-cli.sh cache:flush`

## Git workflow

- **Never** commit `vendor/`, `var/`, `pub/media/`, `generated/`, or `app/etc/env.php`
- The `.gitignore` handles this automatically
- Custom modules live in `src/app/code/{Vendor}/{Module}/`
- Always test changes with `bin/magento setup:upgrade` after pulling new code
