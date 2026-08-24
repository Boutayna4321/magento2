# AlpineCommerce — Learn Magento 2 with a real e-commerce project

> **A repository for two purposes:** a real e-commerce platform built on
> **Magento 2.4.8** (Adobe Commerce Open Source), and a **complete practical course** that takes
> you from "I don't know Magento" to "I develop a professional module".

```
┌──────────────────────────────────────────────────────────────┐
│   AlpineCommerce                                              │
│   ├── E-commerce platform (15 business modules)               │
│   └── Magento 2 Course                                        │
│       ├── docs/README.md             → the documentation hub  │
│       ├── docs/ENGINEERING_GUIDE.md  → the standards          │
│       └── docs/modules/{Module}.md   → each module's chapter  │
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
- **15 business modules** in `src/app/code/AlpineCommerce/` — each illustrates specific Magento
  concepts (see `docs/README.md` → "Module documents")
- **Frontend**: custom Luma-based theme in `src/app/design/`
- **API**: REST only for now (GraphQL documented but unused — assumed choice)
- **Environment**: Docker (PHP-FPM, Nginx, MySQL, Redis, Elasticsearch)

## Documentation

| Document | Role |
|---|---|
| `docs/README.md` | 🎯 The official documentation hub |
| `docs/PROJECT_CHARTER.md` | 🎓 Vision, philosophy, specifications and functional analysis v1.0 |
| `docs/ENGINEERING_GUIDE.md` | 📐 The Engineering Bible: standards and anti-patterns |
| `docs/ARCHITECTURE.md` | 🏗️ Magento and AlpineCommerce architecture + ADR registry |
| `docs/ROADMAP.md` | 🗺️ The product roadmap and version history |
| `docs/CHANGELOG.md` | 📜 Version history and fixes |
| `docs/BACKLOG.md` | 🛠️ Tracked technical debt (Phase C) |
| `docs/modules/*.md` | 📚 Each module's chapter (15 documents) |

## Prerequisites for new developers

If you are new to Magento, start with the **prerequisites guides** in `docs/prerequisites/`:
Docker, PHP OOP, Git, Magento intro, JavaScript, Layout/Templates, CLI, Cron & Indexers,
Events/Observers/Plugins, REST/GraphQL API, Composer, Debug, Admin, Security, Multi-Store,
Testing, Coding Standards, and CI/CD.

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
