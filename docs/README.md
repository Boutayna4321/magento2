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
├── prerequisites/            ← foundational guides (Docker, PHP OOP, Git, Magento intro)
│   ├── docker.md
│   ├── php-oop.md
│   ├── git-github.md
│   └── magento-intro.md
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
| `ARCHITECTURE.md` | 🏗️ Architecture | Overview, Magento Core, 14 modules, DB tables, REST API, multi-store, security, performance, deployment, ADR registry (ADR-001 → 014) |
| `ENGINEERING_GUIDE.md` | 📐 The Engineering Bible | Canonical module skeleton, principles (SOLID/DRY/KISS/YAGNI), PSR-12, Adobe Commerce patterns, ACL/UI Components, sprint workflow, anti-patterns, checklist, glossary |
| `ROADMAP.md` | 🗺️ Roadmap | 6 stable modules, 7 in finalization, planned/future modules, Magento extensions, prioritization, version history |
| `CHANGELOG.md` | 📜 History | Versions 0.1.0 → 1.5.2, Phase 1 fixes (14 critical bugs), Sprint 6 integration, admin form resolution |
| `BACKLOG.md` | 🛠️ Technical debt | B-01 → B-09: XSD listings, missing Service Contracts, absence of tests, residual Phase 2 |

## Module Documents

Each AlpineCommerce module has its own document in `docs/modules/` — it is
**self-contained**: everything you need to know about the module is inside
(responsibility, scope, architecture, API, decisions, known bugs).

| Module | Document | Status |
||---|---|
| Blog | `modules/BLOG.md` | ✅ Stable |
| Faq (canonical module) | `modules/FAQ.md` | ✅ Stable |
| LegalPages | `modules/LEGAL_PAGES.md` | ✅ Stable |
| ProductReviews | `modules/PRODUCT_REVIEWS.md` | ✅ Stable |
| ProductQuestions | `modules/PRODUCT_QUESTIONS.md` | ✅ Stable |
| ProductLabels | `modules/PRODUCT_LABELS.md` | ✅ Stable |
| CustomerGrid | `modules/CUSTOMER_GRID.md` | ✅ Stable |
| CustomerCare | `modules/CUSTOMER_CARE.md` | ✅ Stable |
| Gdpr | `modules/GDPR.md` | 🔄 Finalization (Sprint 1) |
| StorePickup | `modules/STORE_PICKUP.md` | 🔄 Finalization (Sprint 2) |
| StoreLocator | `modules/STORE_LOCATOR.md` | 🔄 Finalization (Sprint 3) |
| LoyaltyProgram | `modules/LOYALTY_PROGRAM.md` | ⏳ To be finalized |
| EuVat | `modules/EU_VAT.md` | ⏳ To be finalized |
| Hreflang | `modules/HREFLANG.md` | ⏳ To be finalized |
| StoreSetup | `modules/STORE_SETUP.md` | ✅ Stable |

## Entry points by profile

- **Beginner developer**: start with `prerequisites/docker.md` and
  `prerequisites/php-oop.md`, then `prerequisites/git-github.md`, then
  `prerequisites/magento-intro.md`, then `PROJECT_CHARTER.md` (the "why"),
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
| `prerequisites/magento-intro.md` | Magento architecture, modules, EAV, multi-store, UI Components | Everyone new to Magento |

These guides are written for absolute beginners and use AlpineCommerce
examples throughout.

## Link to code

- Modules: `src/app/code/AlpineCommerce/*`
- Custom theme: `src/app/design/`
- The documentation is the **Source of Truth**: every architecture decision is tracked there,
  all code must respect it, any modification is validated.

---

*Last updated: 2026-08-11 (restructuring into product documentation).*
