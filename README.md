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
├── docs/modules/                       ← each module's chapter (Phase D)
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
├── docs/prerequisites/                 ← foundational guides
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

| Document | Role | Essential content |
|---|---|---|
| [`docs/PROJECT_CHARTER.md`](docs/PROJECT_CHARTER.md) | 🎯 The charter | Dual vision (platform + learning reference), philosophy, specifications and functional analysis v1.0, major architecture decisions |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | 🏗️ Architecture | Overview, Magento Core, 19 modules, DB tables, REST API, multi-store, security, performance, deployment, ADR registry (ADR-001 → 014) |
| [`docs/ENGINEERING_GUIDE.md`](docs/ENGINEERING_GUIDE.md) | 📐 The Engineering Bible | Canonical module skeleton, principles (SOLID/DRY/KISS/YAGNI), PSR-12, Adobe Commerce patterns, ACL/UI Components, sprint workflow, anti-patterns, checklist, glossary |
| [`docs/ROADMAP.md`](docs/ROADMAP.md) | 🗺️ Roadmap | 6 stable modules, 7 in finalization, planned/future modules, Magento extensions, prioritization, version history |
| [`docs/CHANGELOG.md`](docs/CHANGELOG.md) | 📜 History | Versions 0.1.0 → 1.5.2, Phase 1 fixes (14 critical bugs), Sprint 6 integration, admin form resolution |
| [`docs/BACKLOG.md`](docs/BACKLOG.md) | 🛠️ Technical debt | B-01 → B-09: XSD listings, missing Service Contracts, absence of tests, residual Phase 2 |

---

## 🧩 Module Documents

Each AlpineCommerce module has its own document in `docs/modules/` — it is
**self-contained**: everything you need to know about the module is inside
(responsibility, scope, architecture, API, decisions, known bugs).

### Stable Modules

| Module | Document | Status |
|---|---|---|
| Blog | [`BLOG.md`](docs/modules/BLOG.md) | ✅ Stable |
| Faq | [`FAQ.md`](docs/modules/FAQ.md) | ✅ Stable |
| LegalPages | [`LEGAL_PAGES.md`](docs/modules/LEGAL_PAGES.md) | ✅ Stable |
| ProductReviews | [`PRODUCT_REVIEWS.md`](docs/modules/PRODUCT_REVIEWS.md) | ✅ Stable |
| ProductQuestions | [`PRODUCT_QUESTIONS.md`](docs/modules/PRODUCT_QUESTIONS.md) | ✅ Stable |
| ProductLabels | [`PRODUCT_LABELS.md`](docs/modules/PRODUCT_LABELS.md) | ✅ Stable |
| CustomerGrid | [`CUSTOMER_GRID.md`](docs/modules/CUSTOMER_GRID.md) | ✅ Stable |
| CustomerCare | [`CUSTOMER_CARE.md`](docs/modules/CUSTOMER_CARE.md) | ✅ Stable |
| StoreSetup | [`STORE_SETUP.md`](docs/modules/STORE_SETUP.md) | ✅ Stable |

### Modules in Finalization

| Module | Document | Status |
|---|---|---|
| AutoInvoice | [`AUTO_INVOICE.md`](docs/modules/AUTO_INVOICE.md) | ✅ Done |
| CreditMemo | [`CREDIT_MEMO.md`](docs/modules/CREDIT_MEMO.md) | ✅ Done |
| PartialInvoice | [`PARTIAL_INVOICE.md`](docs/modules/PARTIAL_INVOICE.md) | ✅ Done |
| Rma | [`RMA.md`](docs/modules/RMA.md) | ✅ Done |
| Gdpr | [`GDPR.md`](docs/modules/GDPR.md) | 🔄 Finalization |
| StorePickup | [`STORE_PICKUP.md`](docs/modules/STORE_PICKUP.md) | 🔄 Finalization |
| StoreLocator | [`STORE_LOCATOR.md`](docs/modules/STORE_LOCATOR.md) | 🔄 Finalization |
| LoyaltyProgram | [`LOYALTY_PROGRAM.md`](docs/modules/LOYALTY_PROGRAM.md) | ⏳ To be finalized |
| EuVat | [`EU_VAT.md`](docs/modules/EU_VAT.md) | ⏳ To be finalized |
| Hreflang | [`HREFLANG.md`](docs/modules/HREFLANG.md) | ⏳ To be finalized |

### Cross-Cutting Documentation

| Document | What you'll learn |
|---|---|
| [`alpinecommerce-order-lifecycle.md`](docs/modules/alpinecommerce-order-lifecycle.md) | 🔄 Complete AlpineCommerce order lifecycle: AutoInvoice, PartialInvoice, CreditMemo, CustomerCare, LoyaltyProgram, Rma, StorePickup |

---

## 🎓 Prerequisites Guides ([`docs/prerequisites/`](docs/prerequisites/))

If you are new to Magento, start with these guides **in order**:

| Guide | Topic | Who needs it |
|---|---|---|
| [`docker.md`](docs/prerequisites/docker.md) | Docker installation, containers, volumes, docker-compose | Everyone |
| [`php-oop.md`](docs/prerequisites/php-oop.md) | Classes, objects, inheritance, interfaces, DI, namespaces | Developers new to OOP |
| [`git-github.md`](docs/prerequisites/git-github.md) | Git commands, branching, pull requests, GitHub workflow | Anyone contributing code |
| [`ci-cd.md`](docs/prerequisites/ci-cd.md) | CI/CD concepts, GitHub Actions, automated pipelines | Everyone understanding deployment |

These guides are written for absolute beginners and use AlpineCommerce
examples throughout.

For Magento-specific prerequisites, see the full reference docs in [`docs/magento2/`](docs/magento2/).

---

## 🚀 Entry points by profile

- **Beginner developer**: start with [`docs/prerequisites/docker.md`](docs/prerequisites/docker.md) and
  [`docs/prerequisites/php-oop.md`](docs/prerequisites/php-oop.md), then [`docs/prerequisites/git-github.md`](docs/prerequisites/git-github.md),
  then [`docs/prerequisites/ci-cd.md`](docs/prerequisites/ci-cd.md), then [`docs/PROJECT_CHARTER.md`](docs/PROJECT_CHARTER.md) (the "why"),
  then [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) (the "how"), then the canonical module document
  [`FAQ.md`](docs/modules/FAQ.md).
- **Intermediate developer**: [`docs/ENGINEERING_GUIDE.md`](docs/ENGINEERING_GUIDE.md) is your reference;
  compare each module to the canonical skeleton.
- **Contributor / maintainer**: [`docs/ENGINEERING_GUIDE.md`](docs/ENGINEERING_GUIDE.md) (validation checklist),
  [`docs/BACKLOG.md`](docs/BACKLOG.md) (debt to address), [`docs/CHANGELOG.md`](docs/CHANGELOG.md) (history of fix decisions).

---

## 🔗 Quick Links

### Documentation
- [📚 Documentation hub](README.md)
- [🏗️ Architecture](docs/ARCHITECTURE.md)
- [📐 Engineering Guide](docs/ENGINEERING_GUIDE.md)
- [🗺️ Roadmap](docs/ROADMAP.md)
- [📜 Changelog](docs/CHANGELOG.md)

### Magento 2 Reference
- [📦 Order Lifecycle](docs/magento2/magento-order-lifecycle.md)
- [💳 Payment Providers](docs/magento2/magento-payment-providers.md)
- [🎯 Events, Observers, Plugins](docs/magento2/magento-events-observers-plugins.md)
- [🔌 REST API & GraphQL](docs/magento2/magento-rest-graphql.md)
- [🖥️ Admin Panel](docs/magento2/magento-admin.md)

### AlpineCommerce Modules
- [🔄 Complete Order Lifecycle](docs/modules/alpinecommerce-order-lifecycle.md)
- [🛒 AutoInvoice](docs/modules/AUTO_INVOICE.md)
- [💬 CustomerCare](docs/modules/CUSTOMER_CARE.md)
- [🎁 LoyaltyProgram](docs/modules/LOYALTY_PROGRAM.md)
- [🚚 StorePickup](docs/modules/STORE_PICKUP.md)

### Code
- [📦 Modules](src/app/code/AlpineCommerce/)
- [🎨 Theme](src/app/design/)
- [⚙️ Module config](src/app/etc/config.php)

---

## 💻 Code

- **Modules**: [`src/app/code/AlpineCommerce/*`](src/app/code/AlpineCommerce/)
- **Custom theme**: [`src/app/design/`](src/app/design/)
- The documentation is the **Source of Truth**: every architecture decision is tracked there,
  all code must respect it, any modification is validated.

---

# Magento 2.4.8 - Docker Development Environment

## What's in the repository

This repository contains the source code for the Magento 2.4.8 project along with a full Docker development environment.

### Tracked (committed to git)
- `src/app/code/` — Custom docs/modules (AlpineCommerce/*, Cartware/*)
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

### Windows docs/prerequisites
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
| Service | URL / Host |
|---|---|
| Storefront | http://localhost:8080 |
| Admin Panel | http://localhost:8080/admin |
| Admin User | admin / admin123 |
| MySQL | localhost:3306 |
| Elasticsearch | localhost:9200 |
| Redis | localhost:6379 |

## Useful commands

### Linux / macOS
| Command | Description |
|---|---|
| `scripts/start.sh` | Start containers |
| `scripts/stop.sh` | Stop containers |
| `scripts/magento-cli.sh` | Run Magento CLI command |

Example: `./scripts/magento-cli.sh cache:flush`

### Windows (PowerShell)
| Command | Description |
|---|---|
| `.\scripts\start.ps1` | Start containers |
| `.\scripts\stop.ps1` | Stop containers |
| `.\scripts\magento-cli.ps1` | Run Magento CLI command |

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
- Custom docs/modules live in `src/app/code/{Vendor}/{Module}/`
- Always test changes with `bin/magento setup:upgrade` after pulling new code
