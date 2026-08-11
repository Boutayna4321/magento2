# AlpineCommerce Documentation

Welcome to the official AlpineCommerce documentation — a professional e-commerce
platform built on **Magento 2.4.8** (Adobe Commerce Open Source) and open source
reference for learning Magento 2.

## Documentation Organization

```
docs/
├── README.md                 ← this file (documentation hub)
├── PROJECT_CHARTER.md        ← vision, mission, specifications v1.0, functional analysis
├── ARCHITECTURE.md           ← Magento + AlpineCommerce architecture + ADR registry
├── ENGINEERING_GUIDE.md      ← The Engineering Bible: standards, patterns, workflow, glossary
├── ROADMAP.md                ← v1.0 development plan and beyond
├── CHANGELOG.md              ← version history and fixes
├── BACKLOG.md                ← tracked technical debt (Phase C)
├── prerequisites/            ← foundational guides (Docker, PHP OOP, Git, Magento intro, Magento JS, Layout/Templates, CLI, Cron & Indexers, Events/Observers/Plugins, REST/GraphQL API, Composer, Debug, Admin, Security, Multi-Store, Testing, Coding Standards, CI/CD)
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
├── modules/                  ← each module's chapter (Phase D)
│   ├── BLOG.md
│   ├── FAQ.md
│   ├── ...
└── archive/sprints/          ← historical sprint reports (outside official docs)
```

## Main Documents

| Document | Role | Essential content |
|---|---|---|
| `PROJECT_CHARTER.md` | 🎯 The charter | Dual vision (platform + learning reference), philosophy, specifications and functional analysis v1.0, major architecture decisions |
| `ARCHITECTURE.md` | 🏗️ Architecture | Overview, Magento Core, 15 modules, DB tables, REST API, multi-store, security, performance, deployment, ADR registry (ADR-001 → 014) |
| `ENGINEERING_GUIDE.md` | 📐 The Engineering Bible | Canonical module skeleton, principles (SOLID/DRY/KISS/YAGNI), PSR-12, Adobe Commerce patterns, ACL/UI Components, sprint workflow, anti-patterns, checklist, glossary |
| `ROADMAP.md` | 🗺️ Roadmap | 6 stable modules, 7 in finalization, planned/future modules, Magento extensions, prioritization, version history |
| `CHANGELOG.md` | 📜 History | Versions 0.1.0 → 1.5.2, Phase 1 fixes (14 critical bugs), Sprint 6 integration, admin form resolution |
| `BACKLOG.md` | 🛠️ Technical debt | B-01 → B-09: XSD listings, missing Service Contracts, absence of tests, residual Phase 2 |

## Module Documents

Each AlpineCommerce module has its own document in `docs/modules/` — it is
**self-contained**: everything you need to know about the module is inside
(responsibility, scope, architecture, API, decisions, known bugs).

### Stable modules

| Module | Document |
|---|---|
| Blog | `modules/BLOG.md` |
| Faq | `modules/FAQ.md` |
| LegalPages | `modules/LEGAL_PAGES.md` |
| ProductReviews | `modules/PRODUCT_REVIEWS.md` |
| ProductQuestions | `modules/PRODUCT_QUESTIONS.md` |
| ProductLabels | `modules/PRODUCT_LABELS.md` |
| CustomerGrid | `modules/CUSTOMER_GRID.md` |
| CustomerCare | `modules/CUSTOMER_CARE.md` |
| StoreSetup | `modules/STORE_SETUP.md` |

### Modules in finalization

| Module | Document | Status |
|---|---|---|
| Gdpr | `modules/GDPR.md` | 🔄 Finalization (Sprint 1) |
| StorePickup | `modules/STORE_PICKUP.md` | 🔄 Finalization (Sprint 2) |
| StoreLocator | `modules/STORE_LOCATOR.md` | 🔄 Finalization (Sprint 3) |
| LoyaltyProgram | `modules/LOYALTY_PROGRAM.md` | ⏳ To be finalized |
| EuVat | `modules/EU_VAT.md` | ⏳ To be finalized |
| Hreflang | `modules/HREFLANG.md` | ⏳ To be finalized |

## Entry points by profile

- **Beginner developer**: start with `prerequisites/docker.md` and
  `prerequisites/php-oop.md`, then `prerequisites/git-github.md`, then
  `prerequisites/magento-intro.md`, then `prerequisites/magento-js.md`,
  then `prerequisites/magento-layout-templates.md`, then
  `prerequisites/magento-cli.md`, then `prerequisites/magento-cron-indexers.md`,
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

## Prerequisites

If you are new to the ecosystem, read these guides **in order** before
diving into Magento modules:

| Guide | Topic | Who needs it |
|---|---|---|
| `prerequisites/docker.md` | Docker installation, containers, volumes, docker-compose | Everyone (the project runs on Docker) |
| `prerequisites/php-oop.md` | Classes, objects, inheritance, interfaces, DI, namespaces | Developers new to OOP |
| `prerequisites/git-github.md` | Git commands, branching, pull requests, GitHub workflow | Anyone who will contribute code |
| `prerequisites/magento-intro.md` | Magento architecture, modules, EAV, multi-store, themes, UI Components | Everyone new to Magento |
| `prerequisites/magento-js.md` | RequireJS, KnockoutJS, jQuery, mage/* libraries, AlpineCommerce JS patterns | Everyone who wants to write or understand Magento frontend JS |
| `prerequisites/magento-layout-templates.md` | Layout XML, containers, blocks, PHTML templates, fallback system | Everyone who wants to modify the frontend |
| `prerequisites/magento-cli.md` | `bin/magento` commands: module, upgrade, compile, cache, deploy | Everyone who will develop on Magento |
| `prerequisites/magento-cron-indexers.md` | Cron jobs, indexers (realtime/schedule), flat tables, cron schedule | Everyone who wants to understand Magento automation and performance |
| `prerequisites/magento-events-observers-plugins.md` | Events, observers, plugins (interceptors), when to use which | Everyone who will extend Magento without modifying core |
| `prerequisites/magento-rest-graphql.md` | REST API, GraphQL, authentication, service contracts, AlpineCommerce examples | Everyone who will integrate or consume Magento APIs |
| `prerequisites/magento-composer.md` | Composer, packages, autoload, updating Magento and modules | Everyone who will install or update dependencies |
| `prerequisites/magento-debug.md` | Logs, developer mode, Xdebug, common errors, debugging workflow | Everyone (debugging is daily work) |
| `prerequisites/magento-admin.md` | Admin navigation, ACL, menus, system.xml, UI Components listings/forms | Everyone who needs to use or extend the admin |
| `prerequisites/magento-security.md` | Form keys, ACL, validation, XSS, CSRF, sanitization, secrets handling | Everyone who will write secure code |
| `prerequisites/magento-multistore.md` | Websites/stores/store views, scope hierarchy, config fallback, store switching | Everyone who will work with multiple stores or languages |
| `prerequisites/magento-testing.md` | Unit tests, integration tests, API functional tests, test framework | Everyone who will write or run tests |
| `prerequisites/magento-coding-standards.md` | PSR-12, naming conventions, module structure, git commit format | Everyone who will write code |
| `prerequisites/ci-cd.md` | CI/CD concepts, GitHub Actions, automated pipelines | Everyone who wants to understand how code is tested and deployed |

These guides are written for absolute beginners and use AlpineCommerce
examples throughout.

## Link to code

- Modules: `src/app/code/AlpineCommerce/*`
- Custom theme: `src/app/design/`
- The documentation is the **Source of Truth**: every architecture decision is tracked there,
  all code must respect it, any modification is validated.

---

*Last updated: 2026-08-11 (restructuring into product documentation).*
