# Engineering Bible — AlpineCommerce

> This document is the **absolute reference** for all development on AlpineCommerce.
> It is Phase A of the roadmap: the rules below are **frozen**. Any new
> module (Phase B) must respect them without exception. Existing debt is tracked in
> `BACKLOG.md`.
>
> This document combines the former `02_ENGINEERING_GUIDE.md`, `03_MODULE_GUIDELINES.md`,
> `04_SPRINT_WORKFLOW.md` and `07_GLOSSARY.md`.

## What this document contains

| Section | Content |
|---|---|
| Canonical module skeleton | The mandatory directory tree of a professional module |
| When to create a module | The question to ask before any creation |
| Fundamental principles | SOLID, DRY, KISS, YAGNI, Clean Code |
| Code standards | PSR-12, conventions, examples |
| Official Adobe Commerce patterns | Repository, Service Contracts, DI, Plugins, Observers, etc. |
| REST API | webapi.xml, authentication |
| ACL / Layout / UI Components | The three pillars of the admin |
| i18n / Logging / Errors / Tests | Good cross-cutting practices |
| Sprint workflow | The sprint life cycle and the role of the AI |
| ❌ What you must NEVER do | Anti-patterns, why, and the alternative |
| Validation checklist | To execute before each commit and each sprint |
| Glossary | Magento terms |

**Reference module**: `AlpineCommerce/Faq` is the canonical module — always compare
your code to its structure.

---

## Canonical Module Skeleton

> Any new business entity follows **exactly** this directory tree (derived from
> `AlpineCommerce/Faq` and `AlpineCommerce/ProductLabels`).

```
AlpineCommerce/{Module}/
├── registration.php                  # Composer registration
├── etc/
│   ├── module.xml                    # Declaration + sequence
│   ├── db_schema.xml                 # DB schema (never InstallSchema/InstallData)
│   ├── di.xml                        # preferences (interfaces → implementations)
│   ├── webapi.xml                    # REST routes (if API exposed)
│   ├── acl.xml                       # Admin resources (if admin)
│   ├── menu.xml                      # Admin menu (adminhtml/)
│   └── routes.xml                    # adminhtml/ or frontend/ depending on the area
├── Api/
│   ├── {Entity}Interface.php         # Data Interface (Service Contract)
│   ├── {Entity}SearchResultsInterface.php
│   ├── {Entity}RepositoryInterface.php
│   └── ...                           # Other business interfaces
├── Model/
│   ├── {Entity}.php                  # Model (data)
│   ├── {Entity}Repository.php        # Repository implementation
│   ├── {Entity}SearchResults.php
│   └── ResourceModel/
│       ├── {Entity}.php              # ResourceModel (table access)
│       └── {Entity}/Collection.php   # Collection (filtered/paginated lists)
├── Controller/
│   ├── Index/                        # Frontend controllers (frontend area)
│   └── Adminhtml/{Entity}/           # Admin controllers (adminhtml area)
│       ├── Index.php                 # Grid
│       ├── NewAction.php             # Blank form
│       ├── Edit.php                  # Pre-filled form
│       ├── Save.php                  # Persistence (delegates to Repository)
│       └── Delete.php / MassDelete.php
├── Block/                            # Frontend blocks (+ admin if needed)
├── Ui/
│   ├── DataProvider/                 # DataProviders for grids/forms
│   └── Component/                    # Custom columns (actions, ...)
├── Plugin/                           # Plugins (interception)
├── Observer/                         # Observers (events)
├── Console/                          # CLI commands
├── Setup/Patch/                      # Data/Schema Patches
├── view/
│   ├── adminhtml/ui_component/       # Grids + forms UI Components
│   ├── adminhtml/layout/             # Admin layouts
│   ├── frontend/layout/              # Frontend layouts
│   └── frontend/templates/           # PHTML templates
└── i18n/                             # CSV translations
```

**Associated rules**
- None of these folders is **optional by choice**: if one is missing, the justification must
  be written in the module documentation (assumed decision).
- The `Api/` folder is not a detail: it is **the public promise** of the module.

---

## When to create an AlpineCommerce module?

**Fundamental principle** — before creating a module, ask this question:

> Does Magento already have this feature?

- **If YES** → Extend Magento via Plugin, Observer, Layout XML, ViewModel
- **If NO** → Create an AlpineCommerce module

### Valid examples

| Feature | AlpineCommerce module | Justification |
|---|---|---|
| Blog | `AlpineCommerce_Blog` | Magento has no native blog |
| FAQ | `AlpineCommerce_Faq` | Magento has no native FAQ |
| Loyalty program | `AlpineCommerce_LoyaltyProgram` | Not in Open Source |
| Advanced GDPR | `AlpineCommerce_Gdpr` | Magento has basics but no complete management |
| Store Pickup | `AlpineCommerce_StorePickup` | Magento has no native store pickup |
| Store locator | `AlpineCommerce_StoreLocator` | Magento has no native store locator |
| EU VAT validation | `AlpineCommerce_EuVat` | Magento has no native VIES validation |
| Legal pages | `AlpineCommerce_LegalPages` | Magento has no dedicated legal page management |

### Invalid examples

| Proposed feature | Why it's invalid |
|---|---|
| `AlpineCommerce_Catalog` | Magento has `Magento_Catalog` → Extend |
| `AlpineCommerce_Customer` | Magento has `Magento_Customer` → Extend |
| `AlpineCommerce_Checkout` | Magento has `Magento_Checkout` → Extend |
| `AlpineCommerce_Sales` | Magento has `Magento_Sales` → Extend |
| `AlpineCommerce_Cms` | Magento has `Magento_Cms` → Extend |
| `AlpineCommerce_Payment` | Magento has `Magento_Payment` → Extend |
| `AlpineCommerce_Shipping` | Magento has `Magento_Shipping` → Extend |

---

## Fundamental Principles

### SOLID

- **S**ingle Responsibility: each class has one reason to change
- **O**pen/Closed: open for extension, closed for modification
- **L**iskov Substitution: any implementation can replace its interface
- **I**nterface Segregation: small, specific interfaces
- **D**ependency Inversion: depend on abstractions, not concrete classes

### DRY (Don't Repeat Yourself)

- No code duplication
- Extract common logic into services, helpers, or traits
- XML configurations should be factored

### KISS (Keep It Simple, Stupid)

- Favor simplicity
- Avoid over-engineering
- A simple solution > a complex solution

### YAGNI (You Ain't Gonna Need It)

- Don't develop features "just in case"
- Develop only what is needed now
- Remove dead code

### Clean Code

- Explicit names (variables, methods, classes)
- Short functions (< 20 lines ideally)
- No unnecessary comments (code should be self-explanatory)
- Explicit error handling
- No dead code

---

## Code Standards

### PSR-12

All PHP code must respect the **PSR-12** standard.

```bash
# Verification with PHP_CodeSniffer
vendor/bin/phpcs --standard=PSR12 app/code/AlpineCommerce/

# Automatic correction
vendor/bin/phpcbf --standard=PSR12 app/code/AlpineCommerce/
```

### Magento Conventions

- **Classes**: `PascalCase`
- **Methods**: `camelCase`
- **Variables**: `$camelCase`
- **Constants**: `UPPER_SNAKE_CASE`
- **Files**: match the class name
- **Namespaces**: `AlpineCommerce\Module\SubNamespace`

### Compliant code example

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface BlogPostRepositoryInterface
{
    public const ENTITY_ID = 'entity_id';

    public function getById(int $id): BlogPostInterface;

    public function getList(SearchCriteriaInterface $searchCriteria): BlogPostSearchResultsInterface;

    public function save(BlogPostInterface $blogPost): BlogPostInterface;

    public function delete(BlogPostInterface $blogPost): bool;
}
```

---

## Official Adobe Commerce Patterns

### Repository Pattern

**Usage**: Data access, masks the complexity of Resource Models.

**Structure**:

```
Api/
  └── EntityRepositoryInterface.php    # Interface (Service Contract)
Model/
  └── EntityRepository.php             # Implementation
```

**Example**:

```php
// Api/EntityRepositoryInterface.php
interface EntityRepositoryInterface
{
    public function getById(int $id): EntityInterface;
    public function save(EntityInterface $entity): EntityInterface;
    public function delete(EntityInterface $entity): bool;
}

// di.xml
<preference for="AlpineCommerce\Module\Api\EntityRepositoryInterface"
            type="AlpineCommerce\Module\Model\EntityRepository"/>
```

### Service Contracts

**Definition**: Interfaces defined in `Api/` that expose business features.

**Rules**:
- All business logic must be behind a Service Contract
- Controllers never do business logic directly
- Controllers delegate to Services/Repositories

**When to create one**: as soon as a feature exposes an API (REST, GraphQL, or internal usage).

### Dependency Injection

**Definition**: Injection of dependencies via the constructor.

**Rules**:
- Always type constructor parameters (`private readonly`)
- Never use `$objectManager->create()` in business code
- Use factories generated automatically by Magento

```php
public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly LoggerInterface $logger
) {}
```

### Plugins (Interceptors)

**Usage**: Modify the behavior of an existing method without touching it.

**When to use**:
- Add behavior before/after/around a Magento method
- Modify a return without class override
- Add business logic on existing code

**When NOT to use**:
- To completely replace a method → prefer a Preference
- For business logic → create a Service

```php
// etc/di.xml
<type name="Magento\Catalog\Model\Product">
    <plugin name="alpinecommerce_product_plugin"
            type="AlpineCommerce\Module\Plugin\ProductPlugin"
            sortOrder="10"/>
</type>

// Plugin/ProductPlugin.php
class ProductPlugin
{
    public function beforeGetName(\Magento\Catalog\Model\Product $subject): array
    {
        // Before calling getName()
    }

    public function afterGetName(\Magento\Catalog\Model\Product $subject, string $result): string
    {
        // After calling getName()
        return strtoupper($result);
    }

    public function aroundGetName(\Magento\Catalog\Model\Product $subject, \Closure $proceed): string
    {
        // Around the call to getName()
        return $proceed();
    }
}
```

### Observers

**Usage**: React to a Magento event.

**When to use**:
- React to a business event (order placed, invoice created)
- Decouple business logic
- Multiple listeners on the same event

**When NOT to use**:
- To modify behavior → prefer a Plugin
- For critical business logic → prefer a direct Service

```php
// etc/events.xml
<event name="sales_order_save_after">
    <observer name="alpinecommerce_order_save_observer"
              instance="AlpineCommerce\Module\Observer\OrderSaveObserver"/>
</event>

// Observer/OrderSaveObserver.php
class OrderSaveObserver
{
    public function execute(\Magento\Framework\Event\Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        // Business logic
    }
}
```

### Plugin vs Observer — golden rule

| Criterion | Plugin | Observer |
|---|---|---|
| **Usage** | Modify an existing method | React to an event |
| **Dependency** | Coupled to a specific class | Decoupled via event |
| **Priority** | `before` / `after` / `around` | Execution after the event |
| **Use case** | Add behavior on `Product::getName()` | React to `sales_order_save_after` |

- If you want to modify the behavior of a method → **Plugin**
- If you want to react to a business event → **Observer**

### Preference vs Factory — golden rule

| Criterion | Preference | Factory |
|---|---|---|
| **Usage** | Link an interface to an implementation | Create an object |
| **Scope** | Global (all DI) | Local (single call) |
| **Use case** | Service Contract → Implementation | Creation of business objects |

- Service Contract → **Preference** in `di.xml`
- Object creation → automatically generated **Factory**

### ViewModel vs Block — golden rule

| Criterion | ViewModel | Block |
|---|---|---|
| **Usage** | Presentation logic | Magento page structure |
| **Inheritance** | `\Magento\Framework\View\Element\Template` | `\Magento\Framework\View\Element\Template` |
| **Use case** | Format data for a template | Container in a layout XML |

- If you need a container in a layout → **Block**
- If you need presentation logic → **ViewModel**

### Preference (DI)

**Usage**: Link an interface to a concrete implementation in `di.xml`.

**Rules**:
- Use only for Service Contracts
- One Preference per interface
- Prefer factories for object creation

```xml
<!-- etc/di.xml -->
<preference for="AlpineCommerce\Module\Api\RepositoryInterface"
            type="AlpineCommerce\Module\Model\Repository"/>
```

### ViewModel

**Usage**: Presentation logic for PHTML templates.

```php
// Block/Product/ViewModel.php
class ViewModel extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function getProduct(): ProductInterface
    {
        return $this->productRepository->getById($this->getProductId());
    }
}
```

### Resource Models

**Usage**: CRUD operations on database tables.

**Rules**:
- Inherit from `\Magento\Framework\Model\ResourceModel\Db\AbstractDb`
- Define `_construct()` with `_init($tableName, $primaryKey)`
- Don't use direct SQL without justification

```php
class Entity extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('alphacommerce_entity', 'entity_id');
    }
}
```

### Collections

**Usage**: List of entities with filters, sorting, and pagination.

**Rules**:
- Always use `addFieldToFilter()` instead of manual WHERE
- Limit results with `setPageSize()` and `setCurPage()`
- Never load a complete collection without pagination

```php
$collection = $this->entityCollectionFactory->create();
$collection->addFieldToFilter('is_active', 1)
    ->setOrder('created_at', 'DESC')
    ->setPageSize(20)
    ->setCurPage(1);
```

### Component Relationships

```
Api/Data/EntityInterface.php      <- Entity interface
Api/EntityRepositoryInterface.php <- Service Contract (CRUD)
Model/Entity.php                  <- Business entity
Model/EntityRepository.php        <- Implementation
Model/ResourceModel/Entity.php    <- DB access
Model/ResourceModel/Entity/
    └── Collection.php             <- Entity list
```

---

## REST API

### webapi.xml

**Structure**:

```xml
<?xml version="1.0"?>
<routes xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Webapi:etc/webapi.xsd">
    <route url="/V1/alphacommerce/module/endpoint" method="GET">
        <service class="AlpineCommerce\Module\Api\ServiceInterface" method="getItems"/>
        <resources>
            <resource ref="self"/> <!-- or "anonymous" -->
        </resources>
    </route>
</routes>
```

### Authentication

| Value | Meaning |
|---|---|
| `self` | Connected customer (customer token) |
| `anonymous` | Public access |
| `admin` | Connected administrator |

### When to create a REST API?

**Create a REST route if:**
- The React frontend needs business data
- An external integration must consume the module
- The module exposes interactive features (e.g.: vote for a FAQ)

**Do NOT create a REST API if:**
- Data is already accessible via native Magento endpoints
- The module is purely backend (e.g.: Data Patch)
- Data is only used in PHTML templates

### Best practices

- All routes expose Service Contracts
- Parameter validation in the Service
- Return Data Interface objects (no arrays)
- Error handling with Magento exceptions

---

## ACL (Access Control List)

### Structure

```xml
<!-- etc/acl.xml -->
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_Module::menu" title="Menu Title" sortOrder="10">
                <resource id="AlpineCommerce_Module::entity" title="Manage Entities" sortOrder="10"/>
            </resource>
        </resource>
    </resources>
</acl>
```

### Rules

- One ACL per protected resource
- Admin controllers check `ADMIN_RESOURCE`
- Menus use the same ACL

---

## Layout XML

### Principles

- Layouts define the page structure
- Use `referenceContainer` and `referenceBlock` to modify
- Don't duplicate layouts, use `reference` to extend
- ⚠️ Check the target type: `referenceContainer` only works on a real
  `<container>`; on a `<block>` you must use `referenceBlock` (otherwise blocks are
  silently ignored — cf. bug fixed on `catalog_product_view.xml` of ProductLabels).

```xml
<!-- view/frontend/layout/cms_index_index.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\Listing"
                   name="alphacommerce.blog.listing"
                   template="AlpineCommerce_Blog::listing.phtml"/>
        </referenceContainer>
    </body>
</page>
```

---

## UI Components

### Usage

UI Components are used for grids and forms in the Magento admin.

**Main types**:
- `listing`: data grid
- `form`: edit form
- `dataSource`: data source

### Structure (Magento 2.4.x format)

> **⚠️ Warning**: this format changed in 2.4.x. The `<dataSource>` must contain a
> child `<dataProvider class="..." name="...">`. Without it, the grid crashes on load
> (exception `ConfigurableObject`). Functional reference: `productlabels_label_grid.xml`.

```xml
<!-- view/adminhtml/ui_component/entity_listing.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <settings>
        <buttons>
            <button name="add">
                <url path="*/entity/new"/>
                <class>primary</class>
                <label translate="true">Add New Entity</label>
            </button>
        </buttons>
    </settings>
    <dataSource name="entity_listing_data_source">
        <dataProvider class="AlpineCommerce\Module\Ui\DataProvider\EntityListingDataProvider"
                      name="entity_listing_data_source"/>
        <settings>
            <submitUrl path="mui/index/render"/>
            <updateUrl path="mui/index/render"/>
        </settings>
    </dataSource>
    <columns name="entity_columns">
        <column name="entity_id">
            <settings>
                <label translate="true">ID</label>
            </settings>
        </column>
    </columns>
</listing>
```

**What must NEVER appear** (obsolete format, cf. `BACKLOG.md` → B-01):
`<primaryDataSource>`, `<templates><filters><select customScope="...">`, and a
`<dataSource>` without `<dataProvider class=...>`.

**Form buttons**: never use `<container name="button_set" component="Magento_Ui/js/form/components/button-set">` —
this JS component **does not exist in Magento 2.4.8** and leaves the form empty in the browser.
Use `<settings><buttons>` + `ButtonProviderInterface` classes
(`{GenericButton,SaveButton,BackButton}.php`).

---

## i18n (Translations)

### Structure

```
i18n/
├── fr_FR.csv
├── en_US.csv
└── de_DE.csv
```

### CSV Format

```csv
"Original string","Translation"
"Save","Enregistrer"
"Delete","Supprimer"
```

### Usage in code

```php
// PHP
__('Save')

// PHTML
<?= __('Save') ?>
```

---

## Logging

### PSR-3

Use the `\Psr\Log\LoggerInterface` interface:

```php
public function __construct(
    private readonly LoggerInterface $logger
) {}

public function doSomething(): void
{
    $this->logger->info('Action performed', ['entity_id' => 123]);
    $this->logger->error('Error occurred', ['exception' => $e]);
}
```

---

## Error Handling

### Magento Exceptions

| Exception | Usage |
|---|---|
| `NoSuchEntityException` | Entity not found |
| `CouldNotSaveException` | Error during save |
| `CouldNotDeleteException` | Error during delete |
| `LocalizedException` | Generic business error |

### Best practices

- Always use Magento exceptions
- Never expose technical details in production
- Log errors with context

---

## Database

### When to create a table?

Create a table only if Magento does not have a suitable native entity.

- Prefer Magento EAV attributes if possible
- Create a custom table only for specific business entities
- Naming: `alphacommerce_{module}_{table}` or `alpinecommerce_{module}_{table}`

### When to use db_schema.xml?

Always. Never `InstallSchema.php` or `InstallData.php`.

- Declarative: Magento manages table creation/migration
- Versioned: changes are tracked
- Multi-environment: works on dev, staging, prod

### When to create a Repository?

Always, for any business entity with a dedicated table.

- The Repository is the sole data access point
- It implements a Service Contract
- It hides the complexity of Resource Models

---

## Tests

### Structure

```
Test/
├── Unit/
│   └── Service/
│       └── BlogPostServiceTest.php
├── Integration/
│   └── Repository/
│       └── BlogPostRepositoryTest.php
└── functional/
    └── BlogFrontendTest.php
```

### Rules

- Unit tests for pure business logic
- Integration tests for Repositories and Services
- Functional tests for user scenarios
- Minimum coverage: 80%

---

## Sprint Workflow

### Philosophy

Each sprint is a closed, traceable, and validated iteration.

We never develop multiple features in parallel.
We never do unrequested refactoring.
We never modify a module without validation.

### Sprint Life Cycle

```
┌─────────────┐
│   ANALYZE    │  Understand the business need
│   EXISTING   │  and technical state
└──────┬──────┘
       ▼
┌─────────────┐
│  PLAN &     │  Propose the architecture
│  APPROVE    │  and wait for validation
└──────┬──────┘
       ▼
┌─────────────┐
│  PROGRESSIVE │  Code the module or
│ DEVELOPMENT  │  the Magento extension
└──────┬──────┘
       ▼
┌─────────────┐
│ COMPILATION  │  setup:upgrade, di:compile,
│  & TESTS     │  cache:clean, indexer:reindex
└──────┬──────┘
       ▼
┌─────────────┐
│   TECHNICAL  │  Verify compliance
│   AUDIT      │  with standards
└──────┬──────┘
       ▼
┌─────────────┐
│   REPORT     │  Document what was
│  & STOP      │  done, then stop
└─────────────┘
```

### Role of the AI during a Sprint

The AI is a **Tech Lead and Software Architect**.

**Responsibilities**:
- Analyze the existing codebase
- Explain technical choices
- Propose the architecture
- Code validated features
- Verify compliance (PSR-12, Magento Best Practices)
- Produce audit reports
- Document decisions

**What the AI does NOT do**:
- Propose unrequested refactorings
- Modify code without validation
- Create modules without justification
- Develop multiple features in parallel

### Detailed Steps

**Step 1: Existing codebase analysis** — list files, verify dependencies,
identify patterns, detect issues. *Deliverable: full state of play.*

**Step 2: Planning and validation** — explain the need, propose the architecture,
wait for validation. *Deliverable: approved work plan.*

**Step 3: Progressive development** — one file at a time, validate each step. *Deliverable: working code.*

**Step 4: Compilation and tests** — mandatory commands:

```bash
# Database update
bin/magento setup:upgrade

# DI compilation
bin/magento setup:di:compile

# Static content deployment (if needed)
bin/magento setup:static-content:deploy

# Cache cleanup
bin/magento cache:clean
bin/magento cache:flush

# Reindexing
bin/magento indexer:reindex

# Module status check
bin/magento module:status
```

**Step 5: Technical audit** — PSR-12 compliance, Magento conventions, namespace
consistency, absence of dead code, absence of references to other AlpineCommerce modules.

**Step 6: Report and STOP** — produce a report (summary, modified/created
files, command validation, next steps), **then stop and wait for validation.**

### Strict rules

**Never**:
- ❌ Modify multiple features at the same time
- ❌ Do unrequested refactoring
- ❌ Modify another module without validation
- ❌ Propose to recreate Catalog, Customer, Checkout, Sales
- ❌ Ignore compilation errors

**Always**:
- ✅ Work module by module
- ✅ Wait for validation before each important step
- ✅ Verify that Magento doesn't already provide the feature
- ✅ Document decisions
- ✅ Produce a report after each sprint

**User validation**: the work plan (E2), the development (E3), the technical
audit (E5) and the final report (E6). **No step can be passed without explicit validation.**

---

## ❌ What you must NEVER do

> Each anti-pattern is listed with: **why it's bad** and **the right approach**.
> Code that falls into one of these traps is **rejected in review**, whatever the reason.

### 1. Use `ObjectManager` in business code

```php
// ❌ NEVER
$repo = $this->_objectManager->create(EntityRepository::class);
```

**Why it's bad**: bypasses the DI container, makes code untestable,
hides real dependencies, breaks plugins on this class.
**Good approach**: inject the dependency in the constructor (`private readonly ...`).

### 2. Create `InstallSchema.php` / `InstallData.php`

**Why it's bad**: obsolete and not executable in update cycles
(`setup:upgrade`). Doesn't handle incremental updates.
**Good approach**: `etc/db_schema.xml` for schema + `Setup/Patch/` for data.

### 3. Direct SQL in Controllers or Blocks

```php
// ❌ NEVER
$result = $connection->query('SELECT * FROM entity WHERE id = ' . $id);
```

**Why it's bad**: leaks business logic into the presentation layer,
vulnerable to injections if poorly concatenated, impossible to test.
**Good approach**: Repository → ResourceModel → Collection.

### 4. Business logic in Blocks / ViewModels

**Why it's bad**: the Block should only **prepare data for the
template**. Business logic must be reusable and testable.
**Good approach**: Service / Repository for logic, Block for display.

### 5. "Junk drawer" Helper

```php
// ❌ NEVER
class Data extends AbstractHelper { /* 200 disparate methods */ }
```

**Why it's bad**: anti-SOLID (Single Responsibility violated), class difficult to
read, test, replace.
**Good approach**: one service per responsibility (`GetActiveLabels`, `PriceCalculator`...).

### 6. Unnecessary Preferences (override Magento classes)

**Why it's bad**: global override, conflicts with other modules, core
updates impossible. The absolute last resort.
**Good approach** (in order): **Plugin → Observer → Layout XML → Preference**.

### 7. Rewrite Magento Core

**Why it's bad**: Magento provides Catalog, Customer, Sales, Checkout, Inventory,
CMS. Rewriting them = enormous cost + loss of security updates.
**Good approach**: **always extend before creating.** We only create a module for
**genuine new business value**.

### 8. Copy-paste Core code

**Why it's bad**: code maintained by others, incompatible with your versions,
impossible to update.
**Good approach**: extend via plugin/observer, or rewrite **minimally** and cleanly
for your need.

### 9. Bypass Service Contracts

```php
// ❌ NEVER
$model = $this->modelFactory->create()->load($id);
// ✅ ALWAYS
$entity = $this->entityRepository->getById($id);
```

**Why it's bad**: the Repository is the only official entry point.
**Good approach**: go through the `Api/{Entity}RepositoryInterface` interface everywhere.

### 10. The "blind commit" (without validation)

**Why it's bad**: a module not validated locally breaks the chain for everyone.
**Good approach**: execute the **validation checklist** below before each commit.

---

## Module Validation Checklist

### Before each commit

- [ ] `php -l` on all PHP files
- [ ] `phpcs` PSR-12 compliant
- [ ] No reference to another AlpineCommerce module (unless authorized)
- [ ] Service Contracts defined in `Api/`
- [ ] `db_schema.xml` valid and consistent with ResourceModels (no InstallSchema/InstallData)
- [ ] `module.xml` with correct sequences
- [ ] `di.xml` without error
- [ ] `webapi.xml` with correct authentication
- [ ] `acl.xml` defined if admin controller
- [ ] Frontend and admin routes defined
- [ ] Translations in `i18n/`
- [ ] No business logic in Controllers
- [ ] No `$objectManager->create()` in business code
- [ ] No direct SQL without justification
- [ ] No `preference` on a Magento core class
- [ ] No rewriting of existing core functionality (extend > create)
- [ ] UI Components in 2.4.x format (`<dataProvider class="...">` present, no `primaryDataSource`)
- [ ] No dead code (unused classes, methods, variables)

### Before each Sprint

- [ ] `setup:upgrade` passes without error
- [ ] `setup:di:compile` passes without error
- [ ] `module:status` displays the module correctly
- [ ] `cache:clean` and `cache:flush` pass
- [ ] `indexer:reindex` passes
- [ ] Module tested in frontend and/or backend
- [ ] No errors in `var/log/system.log` and `var/log/exception.log`

---

## Glossary

### A

**ACL (Access Control List)** — Magento's permission system that defines who can access what resources in the admin.

**Adobe Commerce** — Official name of Magento 2 (Enterprise Edition). In this project, we use Magento 2 Open Source.

**API REST** — Programming interface that allows interaction with Magento via HTTP requests. Defined in `etc/webapi.xml`.

**Area** — Magento concept that delimits the execution context: `frontend`, `adminhtml`, `crontab`, `webapi_rest`, `graphql`.

**Attribute** — Property of a product, customer, or category in Magento. Can be of type EAV (text, date, decimal) or Flat (varchar, int, text, decimal, datetime).

### B

**Block** — PHP class that provides data to a PHTML template. Inherits from `\Magento\Framework\View\Element\Template`.

**Bundle Product** — Magento product type composed of multiple options, each linked to a simple product.

### C

**Cache** — Magento mechanism for storing frequently used data. Types: `config`, `layout`, `block_html`, `collections`, `reflection`, `db_ddl`, `full_page` (Varnish), `translate`, `config_integration`, `config_integration_api`.

**Collection** — Class representing a list of entities with filters, sorting, and pagination.

**Composer** — PHP dependency manager used by Magento.

**ComponentRegistrar** — Magento class that registers modules, themes, and language packages.

**Controller** — Class that handles HTTP requests and returns a response. In Magento, controllers extend `\Magento\Framework\App\Action\Action`.

**Cron** — Scheduled tasks in Magento. Configured in `etc/crontab.xml`.

**Customer** — Entity representing a client in Magento.

### D

**Data Patch** — PHP script that modifies the database structure or data. Used for post-installation modifications.

**db_schema.xml** — Declarative XML file that defines tables, columns, indexes, and constraints of the database.

**Dependency Injection (DI)** — Pattern that allows injecting a class's dependencies via its constructor rather than creating them directly.

**di.xml** — Magento Dependency Injection Container configuration file.

**Directory** — Virtual directory of Magento (e.g.: `app/code`, `app/design`, `vendor`).

### E

**EAV (Entity-Attribute-Value)** — Magento data model for entities like products and customers. Allows dynamic attributes.

**Event** — Magento mechanism that allows reacting to specific actions (e.g.: `sales_order_save_after`).

**events.xml** — File that declares observers for Magento events.

**Extension Attribute** — Mechanism that allows adding attributes to an interface without modifying it. Used to extend Service Contracts.

### F

**Factory** — Class automatically generated by Magento to create object instances. Uses the Factory pattern.

**Frontend** — Application area visible to customers. Different from `adminhtml`.

**FrontName** — Route identifier in the URL (e.g.: `loyalty` in `/loyalty/customer/balance`).

### G

**GraphQL** — API query language for Magento (not used in this project for now).

**Group** — Configuration level in Magento: `default` (global), `websites` (per site), `stores` (per store).

### H

**Helper** — Utility class that provides reusable methods. In Magento, Helpers extend `\Magento\Framework\App\Helper\AbstractHelper`.

### I

**Indexer** — Magento process that keeps data updated to improve search and filter performance.

**Interface** — Contract that defines the methods a class must implement. In Magento, interfaces are in the `Api/` folder.

**Interceptor** — Class generated by `setup:di:compile` that implements Plugin logic.

### K

**Knockout.js** — JavaScript framework used by Magento for UI components (checkout, mini-cart). In this project, React replaces Knockout for the custom frontend.

### L

**Layout XML** — XML file that defines the structure of a Magento page (blocks, containers, templates).

**Logger** — Class that writes messages to log files. Uses PSR-3.

### M

**Magento 2** — Open-source e-commerce platform on which AlpineCommerce is based.

**Menu** — Admin menu item defined in `etc/adminhtml/menu.xml`.

**Module** — Functional unit of Magento. In AlpineCommerce, each module is a business feature.

**module.xml** — File that declares a Magento module with its name, version, and dependencies.

**MSI (Multi Source Inventory)** — Magento's multi-source inventory system that allows managing stock in multiple warehouses.

**Multi Store** — Magento feature that allows managing multiple stores with different configurations.

### O

**Observer** — Class that reacts to a Magento event. Declared in `etc/events.xml`.

**OOP (Object-Oriented Programming)** — Programming paradigm used by Magento: classes, interfaces, inheritance, polymorphism.

### P

**Patch** — Script that modifies the database. Can be of type `Data` (data) or `Schema` (structure).

**Permission** — Right to access a Magento resource, defined in `etc/acl.xml`.

**Plugin (Interceptor)** — Magento pattern that allows modifying the behavior of a method without touching it. Defined in `etc/di.xml`.

**Preference** — Link in `di.xml` that associates an interface with a concrete implementation.

**Product** — Entity representing a product in Magento.

**Proxy** — Class generated by Magento for lazy loading of dependencies.

**PSR-12** — PHP coding standard respected by the project.

**PHTML** — Magento template file extension (PHP HTML).

### Q

**Quote** — Entity representing a customer's cart before the order.

### R

**React** — JavaScript library used for AlpineCommerce's custom frontend.

**Registration** — `registration.php` file that registers a module, theme, or language package with Magento.

**Repository** — Class that provides data access via business methods (getById, getList, save, delete). Implements a Service Contract.

**Resource Model** — Class that performs CRUD operations on database tables.

**REST API** — HTTP-based programming interface for interacting with Magento.

**routes.xml** — File that declares frontend or admin routes of a module.

### S

**Sales** — Magento module that manages orders, invoices, credit memos, and shipments.

**Schema** — Database structure. In Magento, defined in `etc/db_schema.xml`.

**Scope** — Configuration scope in Magento: `default` (global), `website` (website), `store` (store).

**Search Criteria** — Magento object representing search criteria (filters, sorting, pagination).

**Service Contract** — Interface that defines the methods of a business service. Stored in `Api/`.

**Setup** — Directory containing database installation and update scripts.

**Shipping** — Magento module that manages shipping methods.

**Store** — Entity representing a store in Magento.

**Store View** — Lowest level of the Magento hierarchy: Global > Website > Store Group > Store > Store View.

### T

**Tailwind CSS** — Utility-first CSS framework used for the custom frontend.

**Template** — PHTML file containing the HTML of a page or block.

**Total Collector** — Magento class that calculates cart totals (subtotal, taxes, shipping fees, discounts).

### U

**UI Component** — Magento UI component for admin grids and forms. Defined in `view/adminhtml/ui_component/`.

**URL Rewrite** — Magento mechanism for customizing URLs for SEO.

### V

**Varnish** — Reverse proxy cache used in production to speed up page loading.

**ViewModel** — Class that provides data and logic to a template. Modern alternative to Blocks.

**VirtualType** — Virtual type in `di.xml` that allows configuring a class without explicitly declaring it.

### W

**webapi.xml** — File that declares REST API routes of a module.

**Website** — Entity representing a website in the Magento hierarchy.

### X-Y-Z

**XML** — Markup language used for Magento configurations (layouts, di, webapi, etc.).

**YAML** — File format used by Docker and some Magento configurations.

**Zone** — Magento concept that delimits the execution context (frontend, adminhtml, crontab, etc.).

---

*Last updated: 2026-08-06 (Phase A — standards frozen).*
