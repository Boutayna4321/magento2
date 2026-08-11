# AlpineCommerce Project Architecture

> Overview of Magento + AlpineCommerce architecture and architecture decision
> registry (ADR). Combines the former `01_ARCHITECTURE.md` and `06_DECISIONS.md`.

---

## 1. Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     AlpineCommerce E-Commerce                │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐     │
│  │   Frontend  │    │   Backend   │    │     API     │     │
│  │   (React)   │    │   (PHTML)   │    │   (REST)    │     │
│  └──────┬──────┘    └──────┬──────┘    └──────┬──────┘     │
│         │                  │                  │             │
│         └──────────────────┼──────────────────┘             │
│                            │                                │
│                    ┌───────▼───────┐                        │
│                    │   Magento 2   │                        │
│                    │   (Core)      │                        │
│                    └───────┬───────┘                        │
│                            │                                │
│  ┌─────────────────────────┼─────────────────────────┐     │
│  │                         │                         │     │
│  ┌───────▼───────┐  ┌───────▼───────┐  ┌───────▼───────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │     Blog      │  │     Faq       │  │    Gdpr       │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │   Hreflang    │  │    LegalPages │  │ StorePickup   │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐ │
│  │ AlpineCommerce│  │ AlpineCommerce│  │ AlpineCommerce│ │
│  │ StoreLocator  │  │   StoreSetup   │  │   EuVat       │ │
│  └───────────────┘  └───────────────┘  └───────────────┘ │
│  ┌───────────────────────────────────────────────────┐     │
│  │              AlpineCommerce LoyaltyProgram         │     │
│  └───────────────────────────────────────────────────┘     │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │                    Database (MySQL)                  │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

**Fundamental principle: Magento 2 is the core of the application.** AlpineCommerce
modules **complement** Magento, never replace it.

### Golden Rules

1. **Never recreate** an existing Magento module
2. **Extend** via Plugin, Observer, Layout XML, ViewModel
3. **Use** Magento Service Contracts
4. **Respect** Magento naming conventions

---

## 2. Magento Core

### Magento modules used

| Magento Module | Role in AlpineCommerce |
|---|---|
| `Magento_Catalog` | Product catalog |
| `Magento_Customer` | Customer management |
| `Magento_Checkout` | Order process |
| `Magento_Sales` | Orders, invoices, credit memos |
| `Magento_Quote` | Cart and quotes |
| `Magento_Cms` | Pages and CMS blocks |
| `Magento_Inventory` | MSI (Multi Source Inventory) |
| `Magento_Payment` | Payment methods |
| `Magento_Shipping` | Shipping methods |
| `Magento_Store` | Multi-store |
| `Magento_Backend` | Admin interface |
| `Magento_Webapi` | REST API |
| `Magento_Indexer` | Indexers |
| `Magento_Cache` | Cache |

---

## 3. AlpineCommerce Modules

### Existing modules

| Module | Responsibility | DB Tables | REST API |
|---|---|---|---|
| `AlpineCommerce_Blog` | Multi-store blog | `alphacommerce_blog_post`, `alphacommerce_blog_category` | `/V1/alphacommerce/blog/*` |
| `AlpineCommerce_Faq` | FAQ | `alphacommerce_faq` | `/V1/alphacommerce/faqs/*` |
| `AlpineCommerce_Gdpr` | GDPR compliance | `alphacommerce_gdpr_consent_log` | `/V1/alphacommerce/gdpr/*` |
| `AlpineCommerce_Hreflang` | SEO hreflang | None | None |
| `AlpineCommerce_LegalPages` | Legal pages | `alphacommerce_legal_page` | `/V1/alphacommerce/legal-pages/*` |
| `AlpineCommerce_StorePickup` | Store pickup | `alphacommerce_pickup_store_info` | `/V1/carts/mine/store-pickup` |
| `AlpineCommerce_StoreLocator` | Store locator | `alphacommerce_store_locator_store` | None |
| `AlpineCommerce_StoreSetup` | Store setup (config, observers, store views) | None | None |
| `AlpineCommerce_EuVat` | EU VAT validation | `alphacommerce_euvat_validation` | `/V1/alphacommerce/euvat/*` |
| `AlpineCommerce_LoyaltyProgram` | Loyalty program | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` | `/V1/carts/mine/loyalty-points` |
| `AlpineCommerce_ProductReviews` | Product reviews | `alphacommerce_product_review`, `alphacommerce_product_review_image`, `alphacommerce_product_review_helpful` | `/V1/alphacommerce/product-reviews/*` |
| `AlpineCommerce_ProductQuestions` | Product Q&A | `alphacommerce_product_question`, `alphacommerce_product_answer`, `alphacommerce_product_question_vote` | `/V1/alphacommerce/product-questions/*` |
| `AlpineCommerce_ProductLabels` | Product labels | `alphacommerce_product_label`, `alphacommerce_product_label_product` | `/V1/alphacommerce/product-labels/*` |
| `AlpineCommerce_CustomerGrid` | Admin customer grid (columns, labels, visibility) | None | None |
| `AlpineCommerce_CustomerCare` | Customer care (VIP levels, lifetime spend, attributes) | None (customer EAV attributes) | `/V1/customercare/*` |

### AlpineCommerce Module Principles

- **Single responsibility**: one module = one business feature
- **Independence**: no dependency between AlpineCommerce modules
- **Service Contracts**: each module exposes its interfaces in `Api/`
- **db_schema.xml**: no `InstallSchema` or `InstallData`
- **No business logic in Controllers**: Controllers delegate to Services

---

## 4. Backend: patterns used

| Pattern | Usage |
|---|---|
| **Service Contract** | Business interface exposed in `Api/` |
| **Repository** | Data access, implements the Service Contract |
| **Resource Model** | CRUD operations on tables |
| **Collection** | Entity list with filters and sorting |
| **Plugin** | Modify behavior of an existing method |
| **Observer** | React to a Magento event |
| **ViewModel** | Presentation logic for PHTML templates |
| **Factory** | Object creation (automatically generated) |
| **Preference** | Interface → implementation link in `di.xml` |
| **VirtualType** | Virtual type for complex DI configuration |

### Module structure

```
AlpineCommerce_Module/
├── registration.php              # Module registration
├── etc/
│   ├── module.xml                # Name, version, sequences
│   ├── db_schema.xml             # Tables and columns
│   ├── di.xml                    # Preferences, plugins, virtualTypes
│   ├── events.xml                # Observers
│   ├── webapi.xml                # REST API routes
│   ├── acl.xml                   # Admin permissions
│   ├── frontend/
│   │   ├── di.xml                # Frontend plugins
│   │   └── routes.xml            # Frontend routes
│   └── adminhtml/
│       ├── routes.xml            # Admin routes
│       ├── menu.xml              # Admin menu
│       └── system.xml            # Admin configuration
├── Api/
│   ├── ModuleInterface.php       # Main Service Contract
│   └── Data/                     # Data Interfaces
├── Model/
│   ├── Entity.php                # Business entity
│   ├── EntityRepository.php      # Repository (CRUD)
│   ├── ResourceModel/
│   │   ├── Entity.php            # Resource Model
│   │   └── Entity/
│   │       └── Collection.php    # Collection
│   └── Service.php               # Business logic
├── Block/                        # PHTML Blocks (backend)
├── Controller/                   # Controllers
├── Plugin/                       # Plugins
├── Observer/                     # Observers
├── Console/Command/              # CLI commands
├── view/
│   ├── adminhtml/                # Admin templates and layouts
│   └── frontend/                 # Frontend templates and layouts
└── i18n/                         # Translations
```

> ⚠️ Note: the full canonical reference (with `Ui/`, `Setup/Patch/`, etc.) is
> documented in `ENGINEERING_GUIDE.md` → "Canonical module skeleton".

---

## 5. Database

### Principles

- Use exclusively `db_schema.xml` (declarative)
- No `InstallSchema.php` or `InstallData.php`
- Prefer Data Patches (`Setup/Patch/Data/`) for initial inserts
- Naming: `alphacommerce_{module}_{table}`

### Tables by module

| Module | Tables |
|---|---|
| `Blog` | `alphacommerce_blog_post`, `alphacommerce_blog_category` |
| `Faq` | `alphacommerce_faq` |
| `Gdpr` | `alphacommerce_gdpr_consent_log` |
| `LegalPages` | `alphacommerce_legal_page` |
| `StorePickup` | `alphacommerce_pickup_store_info` (+ `quote`/`sales_order` columns) |
| `StoreLocator` | `alphacommerce_store_locator_store` |
| `ProductReviews` | `alphacommerce_product_review`, `alphacommerce_product_review_image`, `alphacommerce_product_review_helpful` |
| `ProductQuestions` | `alphacommerce_product_question`, `alphacommerce_product_answer`, `alphacommerce_product_question_vote` |
| `ProductLabels` | `alphacommerce_product_label`, `alphacommerce_product_label_product` |
| `LoyaltyProgram` | `alpinecommerce_loyalty_balance`, `alpinecommerce_loyalty_order_points` |
| `EuVat` | `alphacommerce_euvat_validation` |

---

## 6. REST API

### Principles

- REST API only (no GraphQL for now — see ADR-006)
- Routes defined in `etc/webapi.xml`
- Authentication: `self` (connected customer) or `anonymous`
- All routes expose Service Contracts

### Existing routes

| Module | Route | Method | Authentication |
|---|---|---|---|
| `Blog` | `/V1/alphacommerce/blog/*` | GET/POST | Mixed |
| `Faq` | `/V1/alphacommerce/faqs` | GET | Anonymous |
| `Gdpr` | `/V1/alphacommerce/gdpr/*` | POST/GET/DELETE | Mixed |
| `LegalPages` | `/V1/alphacommerce/legal-pages/*` | GET | Anonymous |
| `StorePickup` | `/V1/carts/mine/store-pickup` | GET/POST | Self |
| `LoyaltyProgram` | `/V1/carts/mine/loyalty-points` | POST | Self |
| `EuVat` | `/V1/alphacommerce/euvat/*` | GET/POST | Mixed |
| `ProductReviews` | `/V1/alphacommerce/product-reviews/*` | GET/POST | Mixed |
| `ProductQuestions` | `/V1/alphacommerce/product-questions/*` | GET/POST | Mixed |
| `ProductLabels` | `/V1/alphacommerce/product-labels/*` | GET/POST/DELETE | Mixed |

---

## 7. Multi Store

### Configuration

- Magento manages multi-store natively
- AlpineCommerce modules use Magento scopes
- Data can be filtered by `store_id` or `website_id`
- The `Hreflang` module manages hreflang tags for multi-store SEO

### Best practices

- Use `\Magento\Store\Model\StoreManagerInterface` to get the current store
- Never hardcode a `store_id`
- Prefer repositories with store filters

---

## 8. Security

### Principles

- **ACL**: each admin module protected by ACLs
- **Input validation**: systematic validation of inputs
- **Output escaping**: systematic escaping of outputs
- **Prepared statements**: via Resource Models
- **REST API**: authentication by token or session

### Checklist

- [ ] ACLs defined for each admin controller
- [ ] Input parameter validation
- [ ] HTML escaping in templates
- [ ] No sensitive data in logs
- [ ] Magento validation rules used

---

## 9. Performance

### Principles

- **Cache**: use Magento cache (config, layout, block_html, full_page)
- **Indexers**: no custom indexer without justification
- **Collections**: paginated loading, no complete `load()`
- **Queries**: no raw SQL without justification
- **EAV**: correct use of EAV attributes
- **Config cache**: `config.xml` for default values

### Tools

- `bin/magento cache:clean`
- `bin/magento indexer:reindex`
- `bin/magento setup:di:compile`
- Blackfire / Xdebug for profiling

---

## 10. Frontend

### Tech stack

- **Framework**: React
- **Bundler**: Vite
- **CSS**: Tailwind CSS

### Architecture

```
frontend/
├── src/
│   ├── components/      # Reusable React components
│   ├── pages/           # Application pages
│   ├── hooks/           # Custom hooks
│   ├── services/        # REST API calls
│   ├── store/           # State management
│   └── main.jsx         # Entry point
├── public/
├── vite.config.js
└── tailwind.config.js
```

### Principles

- **Separation of Concerns**: presentational components vs containers
- **Custom Hooks**: reusable logic
- **API Services**: centralization of REST calls
- **TypeScript**: strong typing (to be confirmed)

---

## 11. Deployment

### Environments

- **Development**: Docker, developer mode
- **Staging**: Pre-production, production mode
- **Production**: Live, production mode, cache enabled

### Process

1. Commit to Git
2. CI/CD (tests, lint, static analysis)
3. Deployment to staging
4. Functional validation
5. Production deployment

### Deployment commands

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
bin/magento cache:clean
bin/magento indexer:reindex
```

---

## 12. Architecture Decisions (ADR)

### Decision format

Each architecture decision is documented according to the ADR format (Architecture Decision Record).

```
ADR-XXX
Decision title

Status: Accepted | Rejected | Deprecated | Replaced by ADR-YYY
Date: YYYY-MM-DD
Deciders: [list]

Context:
Description of the context and problem.

Decision:
Description of the decision made.

Justification:
Why this decision was made.

Impact:
Consequences of this decision.
```

### Decision registry

| ADR | Title | Status |
|---|---|---|
| ADR-001 | Magento remains the core of the application | Accepted |
| ADR-002 | AlpineCommerce develops only business features | Accepted |
| ADR-003 | Extend Magento rather than replace it | Accepted |
| ADR-004 | Each module has a single responsibility | Accepted |
| ADR-005 | All APIs use Service Contracts | Accepted |
| ADR-006 | The project uses only REST API | Accepted |
| ADR-007 | Each Sprint ends with a complete audit | Accepted |
| ADR-008 | Any new decision must be added to this document | Accepted |
| ADR-009 | Migration from Cartware to AlpineCommerce | Accepted |
| ADR-014 | Architecture of ProductReviews and ProductQuestions modules | Accepted |
| ADR-010 | Frontend React vs PWA Studio | To decide |
| ADR-011 | GraphQL for public APIs | To decide |
| ADR-012 | Automated tests in CI/CD | To decide |
| ADR-013 | Deployment strategy | To decide |

### ADR-001: Magento remains the core of the application

- **Status**: Accepted — **Date**: 2024-01-01

Magento 2 Open Source remains the core of the application. All native Magento features are used as-is.
Justification: Magento is mature, secure, and proven; the community is active; native features (catalog, checkout, payment) are complex to rewrite.
Impact: no rewriting of Magento features; AlpineCommerce modules complement Magento; updates remain possible.

### ADR-002: AlpineCommerce develops only business features

- **Status**: Accepted — **Date**: 2024-01-01

AlpineCommerce modules only do business features that Magento does not provide natively.
Impact: no `AlpineCommerce_Catalog`, `AlpineCommerce_Customer`, etc.; modules are pure business extensions.

### ADR-003: Extend Magento rather than replace it

- **Status**: Accepted — **Date**: 2024-01-01

Extend Magento via Plugins, Observers, Layout XML, ViewModels before creating a new module.
Justification: less code to maintain, better compatibility with Magento updates, respect of conventions.
Impact: systematic use of Plugins and Observers; no Magento code duplication.

### ADR-004: Each module has a single responsibility

- **Status**: Accepted — **Date**: 2024-01-01

Each AlpineCommerce module has a single business responsibility and does not depend on other AlpineCommerce modules.
Impact: no dependencies between AlpineCommerce modules; each module can be activated/deactivated independently.

### ADR-005: All APIs use Service Contracts

- **Status**: Accepted — **Date**: 2024-01-01

All REST API routes expose Service Contracts (interfaces in `Api/`).
Impact: all modules with REST API have an `Api/` directory; Controllers use interfaces, not implementations.

### ADR-006: The project uses only REST API

- **Status**: Accepted — **Date**: 2024-01-01

The project uses only REST API for now. GraphQL is not excluded for the future.
Justification: REST is simpler to set up, the team masters REST, current needs are covered.
Impact: all routes are defined in `webapi.xml`; no GraphQL schema for now.

### ADR-007: Each Sprint ends with a complete audit

- **Status**: Accepted — **Date**: 2024-01-01

Each sprint ends with a complete technical audit before moving to the next.
Impact: audit time included in each sprint; no unaudited code is considered complete.

### ADR-008: Any new decision must be added to this document

- **Status**: Accepted — **Date**: 2024-01-01

Any new architecture decision will be added to this document in ADR format.
Impact: traceability of decisions; living documentation; reference for the whole team.

### ADR-009: Migration from Cartware to AlpineCommerce

- **Status**: Accepted — **Date**: 2024-01-01

All Cartware modules are migrated to AlpineCommerce with changes to the PHP namespace, module name, DB table names, `referenceIds` in `db_schema.xml`, and feature preservation.
Impact: 10 modules migrated; progressive migration module by module; Cartware modules remain active until full validation.

### ADR-014: Architecture of ProductReviews and ProductQuestions modules

- **Status**: Accepted — **Date**: 2026-08-04

Decisions:
1. **Isolated routes**: `productreviews` and `productquestions` as frontName to avoid any conflict with native Magento routes (`review`).
2. **Product injection**: `catalog_product_view.xml` to inject frontend blocks on the product page, without modifying the Core.
3. **3 separate tables**: each entity (review, image, vote / question, answer, vote) has its own table with foreign keys and indexes.
4. **Desynchronized helpful vote**: the `helpful_count` counter is incremented on the fly to avoid costly joins in read.
5. **Status moderation**: workflow (pending → approved/rejected).
6. **Official responses**: `is_official` field to distinguish admin responses from customer responses.

Impact: no conflict with native Magento modules; optimized read performance; complete admin moderation.

---

*Living decision registry: any new decision is added in ADR format (ADR-008).*
