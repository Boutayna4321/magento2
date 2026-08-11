# AlpineCommerce Project Charter

> Founding document of the project: vision, mission, context, specifications v1.0
> and functional analysis. It combines the former `00_PROJECT_VISION.md`, the sprint
> finalization specifications (`12`) and the associated functional analysis (`13`).

---

## 1. Vision

AlpineCommerce has **two complementary objectives**:

### 1.1 A professional e-commerce platform

Professional e-commerce project based on **Adobe Commerce (Magento 2 Open Source)**.

We are not building a new e-commerce engine.
We are not replacing Magento.
We leverage Magento as the application core and add specific business features via proper modules.

### 1.2 An open source reference for learning Magento 2

The project aims to become **one of the best open source references** for
learning Adobe Commerce / Magento 2: a repository that a beginner or intermediate
developer can clone, read, browse, and understand **progressively**.

> **The repository must enable the transition from "I don't know Magento" to "I am capable
> of developing a professional module".**
>
> Each module is a chapter of the course. The documentation explains not only **what
> the code does**, but above all **why** this architecture choice was made, what the
> alternatives are, and what errors to avoid.

### 1.3 Execution roadmap (4 phases)

| Phase | Content | Status |
|---|---|---|
| **A — Standards** | Engineering Bible, Learning Path, Backlog, pedagogical README | ✅ in progress (2026-08) |
| **B — Business development** | Finalize the 14 modules of v1.0 (firm scope) | upcoming |
| **C — Harmonization** | Refactor old modules (1 sprint / module) | after v1.0 |
| **D — Pedagogical documentation** | Module READMEs, diagrams, exercises | after harmonization |

---

## 2. Context

Modern e-commerce requires advanced features that Magento does not offer natively in Open Source:

- A complete loyalty system
- A blog integrated into the catalog
- An SEO-optimized FAQ module
- Advanced GDPR management
- A physical store locator
- A store pickup option
- Dynamic legal pages
- Hreflang tags for multi-store SEO
- Automated European VAT validation

Instead of buying these modules from third-party vendors, we develop them in-house under the vendor `AlpineCommerce`.

---

## 3. Objectives

### Business objectives

- Provide a complete and professional e-commerce experience
- Have differentiating features (loyalty, blog, FAQ, GDPR)
- Master the entire Adobe Commerce stack
- Be able to maintain and evolve each module independently

### Technical objectives

- Produce clean, testable, and maintainable code
- Respect Adobe Commerce and PHP standards (PSR-12)
- Use official Magento patterns: Service Contracts, Repository, DI, Plugins, Observers
- Ensure compatibility with future Magento versions
- Guarantee performance and security

### Pedagogical objectives

- Understand Magento architecture in depth
- Know when to extend Magento vs when to create a new module
- Master the concepts: Service Contracts, Resource Models, UI Components, Layout XML
- Learn the best practices of a professional Adobe Commerce team

---

## 4. Philosophy

### Magento is the core

Magento provides natively:

- Product catalog
- Customer management
- Order process
- Payments and shipping
- CMS
- Inventory (MSI)
- REST API
- Indexers and cache

We **never** write code to replace these features.
We use them as-is and extend them only if necessary.

### Each module has a single responsibility

An AlpineCommerce module does only one thing and does it well.

```
AlpineCommerce_Blog          → Blog management
AlpineCommerce_Faq           → FAQ management
AlpineCommerce_Gdpr          → GDPR compliance
AlpineCommerce_LoyaltyProgram → Loyalty program
...
```

### Extend before creating

Before creating a module, we systematically check whether Magento native already provides the feature.

- If Magento does it → we extend via Plugin, Observer, Layout XML
- If Magento doesn't → we create an AlpineCommerce module

### Documentation as Source of Truth

Every architecture decision is documented.
All code must respect the documentation.
Any modification to the documentation is tracked and validated.

### Why AlpineCommerce exists?

- **Independence**: our modules don't depend on a third-party vendor
- **Intellectual property**: the code belongs to us
- **Scalability**: we control the roadmap and priorities
- **Learning**: building internal Adobe Commerce expertise
- **Reusability**: modules are designed to be deployed on other Magento projects
- **Community**: share a quality open source reference that is missing from the Magento community (the "why" of architecture choices)

### The definitive quality criterion

Before any implementation, the question to ask is:

> "Will a developer discovering Magento easily understand **why** this
> solution was chosen?"

If the answer is no, improve the code or documentation before considering the work complete.

---

## 5. Specifications v1.0

> **Sprint**: Functional finalization of the 13 AlpineCommerce modules
> **Target version**: v1.0 stable
> **Date**: 2026-08-06
> **Status**: Awaiting validation

### 5.1 Business need

AlpineCommerce has 13 functional modules on the code side, but several of them have **functional gaps** that prevent the platform from being usable in production.

The business need is to **finalize each module** so that it is:

- **Operational**: an administrator can use the backend interface to configure and manage the module.
- **Complete**: all business features defined in the roadmap are implemented and accessible.
- **Stable**: the module does not crash, produce PHP errors, or inconsistent behavior.
- **Compliant**: the admin interface respects Adobe Commerce 2.4.8 standards (UI Components, ACL, validation).

Today, 6 out of 14 modules have **functional blockers**:

| Module | Functional blocker |
|---|---|
| **Gdpr** | No admin interface to view/export consents. |
| **EuVat** | No admin interface to view VAT validation history. |
| **LoyaltyProgram** | No admin interface to configure the program or view customer balances. |
| **StorePickup** | No admin interface to manage pickup points. |
| **StoreLocator** | No admin interface to manage stores. Strong coupling with StorePickup. |
| **Hreflang** | Module 100% configuration, but its exact scope (pure SEO or entity management) is unclear. |

### 5.2 Expected features per module to finalize

#### GDPR — GDPR Consent Management

| Feature | Description | Priority |
|---|---|---|
| **Consent listing** | Admin interface listing all registered consents (customer, date, type, IP, status). | Critical |
| **Data export** | Admin interface allowing export of a customer's consents (GDPR: right to portability). | Critical |
| **Configuration** | System configuration page (retention duration, required consents, auto-anonymization). | High |
| **ACL** | Granular permissions: view logs, export, configure. | High |

#### EuVat — European VAT Validation

| Feature | Description | Priority |
|---|---|---|
| **Validation history** | Admin interface listing VAT validations performed (country, number, result, date). | High |
| **Configuration** | System configuration page (enabled countries, strict/validation mode, cache). | High |
| **ACL** | Permissions: view history, configure. | High |

#### LoyaltyProgram — Loyalty Program

| Feature | Description | Priority |
|---|---|---|
| **Program configuration** | Admin interface to define rules (points per euro spent, point value, thresholds). | Critical |
| **Balance consultation** | Admin interface to search for a customer and view their point balance, transaction history. | Critical |
| **Transaction management** | Admin interface to view, filter, cancel point transactions. | High |
| **ACL** | Permissions: configure, view balances, manage transactions. | High |

#### StorePickup — Store Pickup

| Feature | Description | Priority |
|---|---|---|
| **Pickup point management** | Full admin CRUD to manage stores (name, address, hours, capacities, status). | Critical |
| **Availability** | Admin interface to manage pickup slots (days, hours, capacity per slot). | High |
| **Configuration** | System configuration page (pickup fees, delay before pickup, activation/deactivation). | High |
| **ACL** | Permissions: manage stores, manage availability, configure. | High |

#### StoreLocator — Store Locator

| Feature | Description | Priority |
|---|---|---|
| **Store management** | Admin CRUD to manage stores (name, address, geolocation, hours, status). | Critical |
| **Frontend map** | Frontend page displaying the store map with search by geolocation or address. | High |
| **Configuration** | System configuration page (map provider, search radius, unit). | High |
| **ACL** | Permissions: manage stores, configure. | High |

#### Hreflang — Multi-store SEO

| Feature | Description | Priority |
|---|---|---|
| **Scope clarification** | Decide whether the module remains 100% configuration or manages business entities. | Critical |
| **Configuration** | System configuration page (activation by store view, default language, x-default). | High |
| **Hreflang tags** | Automatic injection of `<link rel="alternate" hreflang="...">` tags in the head. | High |
| **ACL** | Permissions: configure. | High |

#### StoreSetup — Store Configuration

| Feature | Description | Priority |
|---|---|---|
| **Configuration baseline** | Ship default store configuration (payment, shipping, currencies, store views). | Critical |
| **Consistent implementation** | Review Data Patch `CreateStores.php` for production use. | High |
| **Features** | Observers on product, order, checkout, customer login; frontend `StoreInfo` block. | High |

### 5.3 Already stable modules (8)

The following modules are considered functionally stable. No new feature is requested in this sprint:

| Module | Status |
|---|---|
| Blog | ✅ Stable |
| Faq | ✅ Stable |
| LegalPages | ✅ Stable |
| ProductQuestions | ✅ Stable |
| ProductReviews | ✅ Stable |
| ProductLabels | ✅ Stable |
| CustomerGrid | ✅ Stable |
| CustomerCare | ✅ Stable |
| StoreSetup | ✅ Stable |

### 5.4 Technical constraints

**Mandatory standards** (Project Charter):
- Magento 2.4.8, PSR-12, `declare(strict_types=1)`
- Dependency Injection only
- Service Contracts for all business logic
- Repository Pattern, ResourceModels, Collections
- `db_schema.xml` (no `InstallSchema`/`InstallData`)
- Full ACL, UI Components for the admin
- REST API if necessary, Layout XML for the frontend
- No ObjectManager, no legacy code

**Architecture constraints**:
- **Extend Magento, never replace it**
- **One module = one responsibility**
- **Homogeneity**: all modules must look like they were developed by a single team
- **Compatibility**: don't break existing functionality or data

**Security constraints**:
- All admin routes protected by ACL
- User input validated and escaped
- No sensitive information leak without access control
- GDPR compliance for the Gdpr module (right to be forgotten, right to portability)

**Data constraints**:
- Core table modifications (`quote`, `sales_order`) documented and secured
- Idempotent data patches
- No data loss during migrations

### 5.5 General acceptance criteria

| Criterion | Description | Acceptance |
|---|---|---|
| **Functional admin interface** | The administrator can access all module features via the backend. | Menu accessible, pages load without error, functional CRUD. |
| **Operational ACL** | Permissions are defined and applied. | A user without permission cannot access pages. |
| **Respect of standards** | The code respects the Project Charter. | `strict_types=1`, DI, no ObjectManager, no legacy. |
| **No regression** | Already stable modules continue to work. | Manual tests of the 6 stable modules. |
| **Visual consistency** | The admin interface looks like the other AlpineCommerce modules. | Same style as the canonical module (Faq). |

### 5.6 v1.0 out of scope

- **Automated tests** (Unit, Integration, Functional, API): dedicated sprint
- **Pedagogical documentation** (module READMEs, diagrams, exercises): dedicated sprint
- **Technical debt harmonization** (table prefix, data patches, form XSD audit): dedicated sprint
- **New modules**: forbidden until v1.0 stable
- **Performance optimization**: non-blocking for v1.0

---

## 6. Functional analysis v1.0

### 6.1 Analysis method

For each module, the following grid is applied:

| Criterion | Question |
|---|---|
| **Business need** | What problem does this feature solve? |
| **Native solution** | Does Magento already provide this feature? |
| **Essential v1.0** | Without this feature, is the platform usable in production? |
| **Deferrable** | Can it be added in a later sprint without blocking v1.0? |
| **User impact** | Who is affected and how? |
| **Architecture impact** | Does the feature modify the existing architecture? |
| **Priority** | Critical / High / Medium / Low |
| **Recommendation** | Include in v1.0 or defer? |

### 6.2 Summary by module

| Module | v1.0 Feature | v1.1 Feature | Overall justification |
|---|---|---|---|
| **GDPR** | Admin listing + Admin export + ACL | Admin anonymization + System config | The business module exists, the admin interface is missing to be usable. |
| **EuVat** | History + extended ACL | Advanced config | The business module exists, visibility on validations is missing. |
| **LoyaltyProgram** | Configuration + Balance consultation + ACL + Menu | Transaction management + Advanced config | The core works (checkout), admin configuration and visibility are missing. |
| **StorePickup** | Store CRUD + ACL + Menu | Availability/slots + Advanced config | Checkout works, stores to manage are missing. |
| **StoreLocator** | Architecture decision + Store CRUD + Frontend map + ACL + Menu | Proximity search + Config | Partially implemented with bad coupling. To restructure. |
| **Hreflang** | Scope clarification (config-only) + tag injection validation | Manual language-store mapping | Functionally complete module. Architecture decision to document. |
| **StoreSetup** | Configuration baseline + Data Patch review | Standards alignment | Production store configuration module. Scope clarified. |

### 6.3 v1.0 Scope (included features)

| ID | Module | Feature | Priority |
|---|---|---|---|
| V1-01 | GDPR | Admin consent listing (UI Component + controllers + ACL) | Critical |
| V1-02 | GDPR | GDPR admin export (button in listing) | High |
| V1-03 | EuVat | Admin validation history (UI Component + controllers + menu + ACL) | High |
| V1-04 | LoyaltyProgram | Rules configuration (system.xml) | Critical |
| V1-05 | LoyaltyProgram | Customer balance consultation (UI Component + controllers + ACL + menu) | Critical |
| V1-06 | StorePickup | Store CRUD (UI Component + controllers + ACL + menu) | Critical |
| V1-07 | StoreLocator | Documented architecture decision | Critical |
| V1-08 | StoreLocator | Store CRUD (UI Components + controllers + ACL + menu) | Critical |
| V1-09 | StoreLocator | Frontend map (block + layout + template) | High |
| V1-10 | Hreflang | Documented architecture decision (configuration-only) | Critical |
| V1-11 | Hreflang | Tag injection validation (already done, verification) | High |
| V1-12 | StoreSetup | Documented configuration baseline | Critical |
| V1-13 | StoreSetup | Review/remove Data Patch `CreateStores.php` | High |

### 6.4 v1.1 Deferred Scope

| ID | Module | Feature | Justification |
|---|---|---|---|
| V1.1-01 | GDPR | Admin anonymization | Console commands are sufficient. |
| V1.1-02 | GDPR | System configuration | Hardcoded default values acceptable. |
| V1.1-03 | EuVat | Advanced configuration | Basic config is sufficient. |
| V1.1-04 | LoyaltyProgram | Advanced transaction management | Balance consultation is sufficient. |
| V1.1-05 | LoyaltyProgram | Advanced configuration | Default values acceptable. |
| V1.1-06 | StorePickup | Availability/slot management | Generic schedules are sufficient. |
| V1.1-07 | StorePickup | Advanced configuration | Basic carrier config is sufficient. |
| V1.1-08 | StoreLocator | Proximity search | UX refinement. |
| V1.1-09 | StoreLocator | System configuration | Default values acceptable. |
| V1.1-10 | Hreflang | Manual language-store mapping | Automatic mapping is sufficient. |
| V1.1-11 | StoreSetup | Full standards alignment | Depends on data patch decision. |

### 6.5 Major architecture decisions

| # | Decision | Options | Recommendation | Impact if undecided |
|---|---|---|---|---|
| 1 | **StoreLocator**: coupling with StorePickup or independence? | A: Coupling (read-only on StorePickup) / B: Independence (own entity) | **Option B** | If undecided, the module remains with unmaintainable strong coupling. |
| 2 | **Hreflang**: configuration-only or business entities? | A: Configuration-only / B: Business entities (language-store mapping) | **Option A** | If undecided, the scope remains vague and risks ballooning. |
| 3 | **StoreSetup**: keep demo Data Patch or make it production-safe? | A: Keep as demo/reproducible script / B: Remove and replace with manual setup | **Option A** | If undecided, the module may create unwanted store views on deployment. |

> **Required validation**: v1.0 scope (V1-01 → V1-13), deferred v1.1 scope,
> and the 3 architecture decisions above must be validated by the product owner.

---

*Document compliant with the AlpineCommerce Project Charter.*
