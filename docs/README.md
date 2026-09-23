# AlpineCommerce Documentation

Index of all project documentation. For installation and a project overview, see the
[main README](../README.md).

## Where to start

| Profile | Reading path |
|---|---|
| Beginner | [Docker](prerequisites/docker.md) → [PHP OOP](prerequisites/php-oop.md) → [Git & GitHub](prerequisites/git-github.md) → [CI/CD](prerequisites/ci-cd.md) → [Magento intro](magento2/magento-intro.md) → [Project charter](PROJECT_CHARTER.md) → [Architecture](ARCHITECTURE.md) → [FAQ module](modules/FAQ.md) |
| Intermediate | [Engineering guide](ENGINEERING_GUIDE.md) — compare each module to the canonical skeleton |
| Maintainer | [Engineering guide](ENGINEERING_GUIDE.md) (checklist) · [Backlog](BACKLOG.md) · [Changelog](CHANGELOG.md) |

## Project

| Document | Content |
|---|---|
| [PROJECT_CHARTER.md](PROJECT_CHARTER.md) | Vision, mission, specifications v1.0, major decisions |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Architecture overview, modules, database, REST API, multi-store, ADR registry |
| [ENGINEERING_GUIDE.md](ENGINEERING_GUIDE.md) | Standards, canonical module skeleton, patterns, workflow, anti-patterns, glossary |
| [ROADMAP.md](ROADMAP.md) | Development plan and future modules |
| [CHANGELOG.md](CHANGELOG.md) | Version history and fixes |
| [BACKLOG.md](BACKLOG.md) | Tracked technical debt |

## Prerequisites (`prerequisites/`)

| Guide | Topic |
|---|---|
| [docker.md](prerequisites/docker.md) | Docker, containers, volumes, Docker Compose |
| [php-oop.md](prerequisites/php-oop.md) | Classes, interfaces, dependency injection, namespaces |
| [git-github.md](prerequisites/git-github.md) | Git commands and GitHub workflow |
| [ci-cd.md](prerequisites/ci-cd.md) | CI/CD concepts and GitHub Actions |

## Magento 2 reference (`magento2/`)

| Guide | Topic |
|---|---|
| [magento-intro.md](magento2/magento-intro.md) | Introduction for beginners |
| [magento-components.md](magento2/magento-components.md) | Components and how they interact |
| [magento-composer.md](magento2/magento-composer.md) | Composer and dependency management |
| [magento-cli.md](magento2/magento-cli.md) | `bin/magento` CLI |
| [magento-layout-templates.md](magento2/magento-layout-templates.md) | PHTML templates and layout XML |
| [magento-styles.md](magento2/magento-styles.md) | Themes and styles: Luma, Hyvä, Luma fallback, custom themes |
| [magento-js.md](magento2/magento-js.md) | JavaScript in Magento |
| [magento-events-observers-plugins.md](magento2/magento-events-observers-plugins.md) | Events, observers and plugins |
| [magento-admin.md](magento2/magento-admin.md) | Admin basics, UI components |
| [magento-rest-graphql.md](magento2/magento-rest-graphql.md) | REST and GraphQL APIs |
| [magento-cron-indexers.md](magento2/magento-cron-indexers.md) | Cron and indexers |
| [magento-multistore.md](magento2/magento-multistore.md) | Websites, stores and store views |
| [magento-order-lifecycle.md](magento2/magento-order-lifecycle.md) | Order lifecycle |
| [magento-payment-providers.md](magento2/magento-payment-providers.md) | Payment providers |
| [magento-security.md](magento2/magento-security.md) | Security basics |
| [magento-coding-standards.md](magento2/magento-coding-standards.md) | Coding standards |
| [magento-testing.md](magento2/magento-testing.md) | Testing |
| [magento-debug.md](magento2/magento-debug.md) | Debugging and workflow |

## AlpineCommerce modules (`modules/`)

| Module | Document |
|---|---|
| AutoInvoice | [AUTO_INVOICE.md](modules/AUTO_INVOICE.md) |
| Blog | [BLOG.md](modules/BLOG.md) |
| CreditMemo | [CREDIT_MEMO.md](modules/CREDIT_MEMO.md) |
| CustomerCare | [CUSTOMER_CARE.md](modules/CUSTOMER_CARE.md) |
| CustomerGrid | [CUSTOMER_GRID.md](modules/CUSTOMER_GRID.md) |
| EuVat | [EU_VAT.md](modules/EU_VAT.md) |
| Faq | [FAQ.md](modules/FAQ.md) — canonical module example |
| Gdpr | [GDPR.md](modules/GDPR.md) |
| Hreflang | [HREFLANG.md](modules/HREFLANG.md) |
| LegalPages | [LEGAL_PAGES.md](modules/LEGAL_PAGES.md) |
| LoyaltyProgram | [LOYALTY_PROGRAM.md](modules/LOYALTY_PROGRAM.md) |
| PartialInvoice | [PARTIAL_INVOICE.md](modules/PARTIAL_INVOICE.md) |
| ProductLabels | [PRODUCT_LABELS.md](modules/PRODUCT_LABELS.md) |
| ProductQuestions | [PRODUCT_QUESTIONS.md](modules/PRODUCT_QUESTIONS.md) |
| ProductReviews | [PRODUCT_REVIEWS.md](modules/PRODUCT_REVIEWS.md) |
| Rma | [RMA.md](modules/RMA.md) |
| StoreLocator | [STORE_LOCATOR.md](modules/STORE_LOCATOR.md) |
| StorePickup | [STORE_PICKUP.md](modules/STORE_PICKUP.md) |
| StoreSetup | [STORE_SETUP.md](modules/STORE_SETUP.md) |
| *Cross-cutting* | [alpinecommerce-order-lifecycle.md](modules/alpinecommerce-order-lifecycle.md) — how AutoInvoice, PartialInvoice, CreditMemo, CustomerCare, LoyaltyProgram, Rma and StorePickup change the order lifecycle |

`HealthCheck` has no document yet.

## Troubleshooting (`troubleshooting/`)

| Guide | Topic |
|---|---|
| [magento-permissions-and-var.md](troubleshooting/magento-permissions-and-var.md) | File permissions and `var/` issues |
