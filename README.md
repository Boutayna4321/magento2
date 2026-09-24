# AlpineCommerce

[![CI](https://github.com/Boutayna4321/magento2/actions/workflows/ci.yml/badge.svg)](https://github.com/Boutayna4321/magento2/actions/workflows/ci.yml)
![Magento](https://img.shields.io/badge/Magento-2.4.8-orange)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4)
![Hyvä](https://img.shields.io/badge/Hyv%C3%A4-1.5.2-0A6EBD)

AlpineCommerce is a multi-store e-commerce platform built on **Magento 2.4.8**
(Adobe Commerce Open Source), with 20 custom modules. It also serves as a
documented reference for learning Magento 2 development.

## Tech stack

| Layer | Technology |
|---|---|
| Platform | Magento 2.4.8 Open Source |
| Runtime | PHP 8.2 (FPM), Nginx 1.25 |
| Data | MySQL 8.0, Elasticsearch 8.11, Redis 7.2 |
| Storefront | Hyvä 1.5.2 (Tailwind CSS, Alpine.js) + Luma theme fallback for the checkout |
| Environment | Docker Compose |
| CI | GitHub Actions ([`.github/workflows/ci.yml`](.github/workflows/ci.yml)) |

---

## Quick start

### Prerequisites

- Docker Engine (Linux) or Docker Desktop (macOS / Windows with WSL 2), with the Compose plugin
- A free Magento Marketplace account for the Composer auth keys —
  [get your access keys](https://developer.adobe.com/commerce/marketplace/)
- Windows: Git Bash or PowerShell 7+ (use the `.ps1` scripts)

### Install

**Linux / macOS**

```bash
git clone https://github.com/Boutayna4321/magento2.git
cd magento2
cp .env.example .env          # adjust values if needed
chmod +x scripts/install.sh
./scripts/install.sh
```

**Windows (PowerShell)**

```powershell
git clone https://github.com/Boutayna4321/magento2.git
cd magento2
copy .env.example .env
.\scripts\install.ps1
```

When prompted, paste your Marketplace **Public Key** and **Private Key**. The script builds and
starts the containers, installs Magento with Composer, runs `setup:install`, deploys static
content and reindexes.

### Access

| Service | URL / Host |
|---|---|
| Storefront | http://localhost:8080 |
| Admin panel | http://localhost:8080/admin |
| Health check | http://localhost:8080/rest/V1/alpinecommerce/health |
| MySQL | localhost:3306 |
| Elasticsearch | localhost:9200 |
| Redis | localhost:6379 |

> ⚠️ **Admin credentials**: the install script creates the admin user `admin` with the
> default password `admin123` (see `scripts/install.sh`). This is for **local development
> only** — change it immediately after installation and never use it on a shared or public server.

Ports can be changed in `.env` (`MAGENTO_PORT`, `MYSQL_PORT`, `ES_HTTP_PORT`, …).

---

## Project structure

```
.
├── .github/workflows/     CI pipeline
├── docs/                  Project documentation (see below)
├── nginx/                 Nginx configuration
├── php/                   PHP-FPM Dockerfile and php.ini
├── scripts/               install / start / stop / magento-cli (.sh and .ps1)
├── docker-compose.yml     Docker services
├── .env.example           Template for environment variables
└── src/                   Magento root
    ├── app/code/AlpineCommerce/    Custom modules
    ├── app/design/frontend/        Custom themes
    ├── app/etc/config.php          Enabled modules (committed)
    ├── composer.json / .lock
    └── bin/magento
```

**Not committed** (generated or environment-specific): `src/vendor/`, `src/generated/`,
`src/var/`, `src/pub/static/`, `src/pub/media/`, `src/app/etc/env.php`, `.env`.
See [`.gitignore`](.gitignore).

---

## Documentation

The full documentation index is in [`docs/README.md`](docs/README.md).

| Section | Content |
|---|---|
| [Project charter](docs/PROJECT_CHARTER.md) | Vision, scope, specifications |
| [Architecture](docs/ARCHITECTURE.md) | System overview, modules, database, APIs, ADR registry |
| [Engineering guide](docs/ENGINEERING_GUIDE.md) | Coding standards, module skeleton, workflow, checklist |
| [Roadmap](docs/ROADMAP.md) · [Changelog](docs/CHANGELOG.md) · [Backlog](docs/BACKLOG.md) | Planning, history, technical debt |
| [`docs/magento2/`](docs/magento2/) | Magento 2 reference guides (CLI, layout, JS, themes & styles, REST/GraphQL, security, testing…) |
| [`docs/modules/`](docs/modules/) | One document per AlpineCommerce module |
| [`docs/prerequisites/`](docs/prerequisites/) | Beginner guides: Docker, PHP OOP, Git/GitHub, CI/CD |
| [`docs/troubleshooting/`](docs/troubleshooting/) | Known issues and fixes |

**New to the project?** Read in this order: [Docker](docs/prerequisites/docker.md) →
[PHP OOP](docs/prerequisites/php-oop.md) → [Git/GitHub](docs/prerequisites/git-github.md) →
[Project charter](docs/PROJECT_CHARTER.md) → [Architecture](docs/ARCHITECTURE.md) →
[FAQ module](docs/modules/FAQ.md) (canonical module example).

---

## Modules

All modules live in [`src/app/code/AlpineCommerce/`](src/app/code/AlpineCommerce/) and are enabled in
[`src/app/etc/config.php`](src/app/etc/config.php).

| Domain | Module | Documentation | Status |
|---|---|---|---|
| Content | Blog | [BLOG.md](docs/modules/BLOG.md) | Stable |
| Content | Faq | [FAQ.md](docs/modules/FAQ.md) | Stable |
| Content | LegalPages | [LEGAL_PAGES.md](docs/modules/LEGAL_PAGES.md) | Stable |
| Catalog | ProductReviews | [PRODUCT_REVIEWS.md](docs/modules/PRODUCT_REVIEWS.md) | Stable |
| Catalog | ProductQuestions | [PRODUCT_QUESTIONS.md](docs/modules/PRODUCT_QUESTIONS.md) | Stable |
| Catalog | ProductLabels | [PRODUCT_LABELS.md](docs/modules/PRODUCT_LABELS.md) | Stable |
| Customer | CustomerGrid | [CUSTOMER_GRID.md](docs/modules/CUSTOMER_GRID.md) | Stable |
| Customer | CustomerCare | [CUSTOMER_CARE.md](docs/modules/CUSTOMER_CARE.md) | Stable |
| Customer | LoyaltyProgram | [LOYALTY_PROGRAM.md](docs/modules/LOYALTY_PROGRAM.md) | Complete |
| Customer | Gdpr | [GDPR.md](docs/modules/GDPR.md) | Complete |
| Orders | AutoInvoice | [AUTO_INVOICE.md](docs/modules/AUTO_INVOICE.md) | Complete |
| Orders | PartialInvoice | [PARTIAL_INVOICE.md](docs/modules/PARTIAL_INVOICE.md) | Complete |
| Orders | CreditMemo | [CREDIT_MEMO.md](docs/modules/CREDIT_MEMO.md) | Complete |
| Orders | Rma | [RMA.md](docs/modules/RMA.md) | Complete |
| Store | StoreSetup | [STORE_SETUP.md](docs/modules/STORE_SETUP.md) | Stable |
| Store | StorePickup | [STORE_PICKUP.md](docs/modules/STORE_PICKUP.md) | Complete |
| Store | StoreLocator | [STORE_LOCATOR.md](docs/modules/STORE_LOCATOR.md) | Complete |
| Store | EuVat | [EU_VAT.md](docs/modules/EU_VAT.md) | Complete |
| SEO | Hreflang | [HREFLANG.md](docs/modules/HREFLANG.md) | Complete |
| Security | Turnstile | [TURNSTILE.md](docs/modules/TURNSTILE.md) | Stable |
| Operations | HealthCheck | — (`GET /V1/alpinecommerce/health`, `GET /V1/alpinecommerce/metrics`) | Enabled, not documented yet |

**Stable**: released and in use. **Complete**: feature-complete, recently finalized.
How the order-related modules work together: [AlpineCommerce order lifecycle](docs/modules/alpinecommerce-order-lifecycle.md).

---

## Development

### Common commands

| Linux / macOS | Windows | Purpose |
|---|---|---|
| `./scripts/start.sh` | `.\scripts\start.ps1` | Start the containers |
| `./scripts/stop.sh` | `.\scripts\stop.ps1` | Stop the containers |
| `./scripts/magento-cli.sh <command>` | `.\scripts\magento-cli.ps1 <command>` | Run a `bin/magento` command |

Examples:

```bash
./scripts/magento-cli.sh cache:flush
./scripts/magento-cli.sh setup:upgrade        # after pulling new code
./scripts/magento-cli.sh indexer:reindex
```

### Frontend themes

The storefront runs on **Hyvä**; the checkout is served by the Luma-based theme
`AlpineCommerce/LumaCheckout` through the Hyvä Luma theme fallback. How themes, styles and the
fallback work: [Themes & styles guide](docs/magento2/magento-styles.md).

### Git workflow

- `main` is the only long-lived branch; changes are committed and pushed to `main`.
- Never commit `vendor/`, `var/`, `generated/`, `pub/static/`, `pub/media/`, `app/etc/env.php`
  or `.env` — `.gitignore` covers them, check `git status` before committing.
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/)
  (`feat(Module): …`, `fix(Module): …`, `docs: …`).
- CI runs on every push to `main`.

### Restoring a backup

```bash
docker compose up -d
docker compose exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' < backup.sql
cp -r /path/to/media/* src/pub/media/
./scripts/magento-cli.sh indexer:reindex
./scripts/magento-cli.sh cache:flush
```

---

## Troubleshooting

See [`docs/troubleshooting/`](docs/troubleshooting/) — for example
[file permissions and `var/` issues](docs/troubleshooting/magento-permissions-and-var.md) — and
the [debugging guide](docs/magento2/magento-debug.md).
