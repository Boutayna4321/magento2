# Magento 2 — Introduction for Beginners

> **Target audience**: developers who have never used Magento. This guide
> explains the platform's architecture and key concepts needed to understand
> the AlpineCommerce project.

---

## Table of Contents

1. [What is Magento?](#1-what-is-magento)
2. [Magento architecture — the big picture](#2-magento-architecture--the-big-picture)
3. [Modules — the heart of Magento](#3-modules--the-heart-of-magento)
4. [Key directories](#4-key-directories)
5. [Configuration scopes](#5-configuration-scopes)
6. [EAV vs Flat tables](#6-eav-vs-flat-tables)
7. [Multi-store architecture](#7-multi-store-architecture)
8. [Themes](#8-themes)
9. [Layout XML](#9-layout-xml)
10. [UI Components (Admin)](#10-ui-components-admin)
11. [Service Contracts (interfaces)](#11-service-contracts-interfaces)
12. [Plugins (Interceptors)](#12-plugins-interceptors)
13. [Observers](#13-observers)
14. [Install/Upgrade scripts](#14-installupgrade-scripts)
15. [ACL and Admin Menu](#15-acl-and-admin-menu)
16. [CLI commands](#16-cli-commands)
17. [Summary](#17-summary)
18. [AlpineCommerce Reference](#18-alpinecommerce-reference)
19. [Next steps](#19-next-steps)

---

## 1. What is Magento?

**Magento** (now **Adobe Commerce**) is an open-source e-commerce platform
written in PHP. It is designed for medium-to-large online stores that need:
- Complex catalogs (thousands of products)
- Multi-store / multi-language / multi-currency
- Advanced promotions, customer segments, B2B features
- Deep customization via modules

**Versions:**
- **Magento Open Source** (free) — the version used by AlpineCommerce
- **Adobe Commerce** (paid) — adds B2B, Page Builder, Adobe Cloud support

**AlpineCommerce target**: Magento **2.4.8** (PHP 8.2).

**Official documentation**: [Adobe Commerce](https://developer.adobe.com/commerce/)

---

## 2. Magento architecture — the big picture

### 2.1 Request flow

```
HTTP Request
    ↓
Nginx (web server)
    ↓
index.php (entry point)
    ↓
Router (matches URL to controller)
    ↓
Controller (orchestrates, no business logic)
    ↓
Service Contract (business interface)
    ↓
Repository (data access)
    ↓
ResourceModel (SQL queries)
    ↓
Database (MySQL)
    ↓
Response (HTML, JSON)
```

**Source**: `src/vendor/magento/framework/App/FrontControllerInterface.php` — the front controller dispatches requests to the appropriate router based on the area.

### 2.2 Areas

Magento has multiple **areas** (application contexts):

| Area | URL | Purpose |
|------|-----|---------|
| `frontend` | `/` | Customer-facing store |
| `adminhtml` | `/admin` | Admin panel |
| `crontab` | CLI | Scheduled tasks |
| `webapi_rest` | `/rest/` | REST API |
| `webapi_soap` | `/soap/` | SOAP API |
| `graphql` | `/graphql` | GraphQL API |

The same module can behave differently depending on the area.

**Source**: `src/vendor/magento/framework/App/State.php` — defines the available areas and their initialization.

**Official documentation**: [Architecture Overview](https://developer.adobe.com/commerce/php/architecture/)

---

## 3. Modules — the heart of Magento

Everything in Magento is a **module**. A module is a folder that groups
code, configuration, and templates for a specific feature.

### 3.1 Module structure

```
Vendor/Module/
├── registration.php          # declares the module to Magento
├── etc/
│   ├── module.xml            # module name, version, dependencies
│   ├── frontend/
│   │   ├── routes.xml        # frontend URL routes
│   │   └── di.xml            # dependency injection config
│   ├── adminhtml/
│   │   ├── routes.xml        # admin URL routes
│   │   ├── menu.xml          # admin menu entries
│   │   └── di.xml
│   ├── webapi.xml            # REST API routes
│   └── crontab.xml           # cron jobs
├── Api/
│   ├── Data/                 # Data interfaces (property bags)
│   └── *.php                 # Service contracts (interfaces)
├── Model/
│   ├── Post.php              # Entity model
│   ├── PostInterface.php     # Entity interface
│   ├── ResourceModel/
│   │   └── Post.php          # SQL queries
│   ├── PostRepository.php    # Repository (business logic)
│   └── PostCollection.php    # Collection (lists of entities)
├── Controller/
│   ├── Frontend/
│   │   └── Index/Index.php   # /blog route
│   └── Adminhtml/
│       └── Post/
│           ├── Index.php     # admin listing
│           └── Edit.php      # admin form
├── Block/                    # Layout blocks (frontend + admin)
├── Ui/                       # UI Components (admin grids/forms)
├── view/
│   ├── frontend/
│   │   ├── layout/           # layout XML
│   │   ├── templates/        # .phtml templates
│   │   └── web/              # CSS, JS, images
│   └── adminhtml/
│       ├── layout/
│       ├── ui_component/     # UI Component XML
│       └── web/
├── Setup/
│   └── Patch/Data/           # Data patches (install/upgrade)
├── etc/
│   └── db_schema.xml         # Database schema
└── i18n/                     # Translation CSVs
```

### 3.2 Module registration

`registration.php`:
```php
<?php
declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Vendor_Module',
    __DIR__
);
```

`etc/module.xml`:
```xml
<?xml version="1.0"?>
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <module name="Vendor_Module" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Catalog"/>
            <module name="Magento_Customer"/>
        </sequence>
    </module>
</config>
```

- **`name`**: unique module name (`Vendor_Module`)
- **`setup_version`**: current schema version
- **`sequence`**: load order (this module loads AFTER the listed modules)

**Source**: `src/vendor/magento/module-backend/etc/module.xml` — Magento core modules follow the same registration pattern.

**Official documentation**: [Module File Structure](https://developer.adobe.com/commerce/php/architecture/modules/)

---

## 4. Key directories

| Directory | Role |
|-----------|------|
| `app/code/` | Custom modules (AlpineCommerce code) |
| `app/design/` | Custom themes |
| `vendor/` | Third-party libraries (Composer) |
| `pub/` | Web root (static files, media) |
| `var/` | Cache, logs, sessions, reports |
| `generated/` | Generated code (interceptors, proxies) |
| `app/etc/` | Global config (`config.php`, `env.php`) |

---

## 5. Configuration scopes

Magento configuration values can be set at different levels:

| Scope | Example |
|-------|---------|
| **Default** (global) | All websites |
| **Website** | Specific website (multi-store) |
| **Store View** | Specific store view (language) |

In `config.xml`, you set defaults. In the admin (`Stores > Configuration`),
admins override per scope.

**Source**: `src/vendor/magento/module-config/etc/system.xml` — Magento core defines all configuration sections here.

---

## 6. EAV vs Flat tables

### 6.1 EAV (Entity-Attribute-Value)

Magento's flexible data model for products, categories, customers:

```
eav_attribute
    ├── entity_type_id = 4 (product)
    ├── attribute_code = 'color'
    └── backend_type = 'varchar'

catalog_product_entity
    ├── entity_id = 1
    ├── sku = 'tshirt'
    └── attribute_set_id = 9

catalog_product_entity_varchar
    ├── value_id = 1
    ├── entity_id = 1
    ├── attribute_id = 123
    └── value = 'Red'
```

**Pros**: flexible (add attributes without altering schema)
**Cons**: complex queries, performance issues

### 6.2 Flat tables

For performance, Magento can flatten EAV into flat tables
(`catalog_product_flat_*`).

**Source**: `src/vendor/magento/module-catalog/Model/ResourceModel/Product/Flat/Indexer.php` — handles the EAV-to-flat transformation.

---

## 7. Multi-store architecture

Magento supports **multiple stores** from a single installation:

```
Website (base)
├── Store (group)
│   ├── Store View (English)
│   ├── Store View (French)
│   └── Store View (German)
└── Store (group)
    └── Store View (Spanish)
```

- **Website**: separate base URL, payment methods, shipping
- **Store**: groups store views, shares cart/customers
- **Store View**: language/currency

**Source**: `src/vendor/magento/module-store/Model/Website.php` — defines the Website model with stores and groups.

**Official documentation**: [Multi-Store](https://developer.adobe.com/commerce/php/architecture/modules/multi-stores/)

---

## 8. Themes

A **theme** defines the look and feel (layout, templates, CSS, JS).

- **Parent theme**: `Magento/luma` (or `Magento/blank`)
- **Child theme**: `Vendor/theme` (inherits from parent)

```
app/design/
├── frontend/
│   ├── Vendor/
│   │   └── theme/
│   │       ├── theme.xml          # parent theme declaration
│   │       ├── registration.php
│   │       ├── web/               # CSS, JS, images
│   │       └── templates/         # overrides
│   └── Magento/
│       └── luma/                  # base theme
```

**Fallback system**: if a template is not found in the child theme,
Magento looks in the parent, then in module `view/frontend/templates/`.

**Source**: `src/vendor/magento/theme-frontend-luma/theme.xml` — Luma theme declaration.

---

## 9. Layout XML

Layout XML defines the **page structure** (which blocks appear where).

```xml
<!-- view/frontend/layout/default.xml -->
<page xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <body>
        <referenceContainer name="content">
            <block class="Vendor\Module\Block\PostList"
                   name="blog.post.list"
                   template="Vendor_Module::post/list.phtml"/>
        </referenceContainer>
    </body>
</page>
```

- `<referenceContainer>`: target an existing container
- `<block>`: add a new block (PHP class + template)

**Source**: `src/vendor/magento/module-theme/view/frontend/layout/default.xml` — Magento core defines the default page containers here.

**Official documentation**: [Layout Instructions](https://developer.adobe.com/commerce/php/architecture/layouts/layout-instructions/)

---

## 10. UI Components (Admin)

The admin panel uses **UI Components** (XML → JS → HTML) instead of plain
layout XML. This powers grids, forms, filters.

```xml
<!-- view/adminhtml/ui_component/vendor_module_post_listing.xml -->
<listing xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <columns name="post_columns">
        <column name="title">
            <settings>
                <label translate="true">Title</label>
                <sortOrder>10</sortOrder>
            </settings>
        </column>
    </columns>
    <dataSource name="post_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">Vendor\Module\Ui\DataProvider\PostListingDataProvider</argument>
        </argument>
    </dataSource>
</listing>
```

Key concepts:
- `<listing>`: the grid
- `<columns>`: columns definition
- `<dataSource>`: links to a PHP `DataProvider` (fetches data)

**Source**: `src/vendor/magento/module-catalog/view/adminhtml/ui_component/product_listing.xml` — Magento core product listing uses the same UI Component structure.

**Official documentation**: [UI Components Overview](https://developer.adobe.com/commerce/php/tutorials/ui-components/)

---

## 11. Service Contracts (interfaces)

A **Service Contract** is a public interface for business logic.

```php
// Api/Data/PostInterface.php
interface PostInterface
{
    public function getId(): ?int;
    public function getTitle(): string;
    public function setTitle(string $title): void;
}

// Api/PostRepositoryInterface.php
interface PostRepositoryInterface
{
    public function save(PostInterface $post): PostInterface;
    public function getById(int $id): PostInterface;
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
    public function delete(PostInterface $post): bool;
}
```

**Why?**
- REST API, GraphQL, and admin all use the same interfaces
- You can swap implementations (e.g., add caching) without touching callers
- It is the **Magento standard** for all business logic

**Source**: `src/vendor/magento/module-catalog/Api/Data/ProductInterface.php` — Magento core defines Service Contracts for all major entities.

**Official documentation**: [Service Contracts](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/)

---

## 12. Plugins (Interceptors)

A **plugin** intercepts a method call to modify behavior without changing
the original class.

```php
// Plugin/Post/Slugify.php
class Slugify
{
    public function beforeSetTitle(Post $subject, string $title): array
    {
        return ['title' => strtolower(str_replace(' ', '-', $title))];
    }
}

// etc/di.xml
<type name="Vendor\Module\Model\Post">
    <plugin name="slugify" type="Vendor\Module\Plugin\Post\Slugify"/>
</type>
```

Plugin types:
- `before`: runs before the original method
- `after`: runs after the original method
- `around`: replaces the original method entirely

**Source**: `src/vendor/magento/framework/Interception/Interceptor.php` — the generated interceptor class that wraps original methods.

**Official documentation**: [Plugins (Interceptors)](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/plugins/)

---

## 13. Observers

An **observer** reacts to a Magento **event** (dispatched with `dispatch()`).

```php
// Observer/SavePostAfter.php
class SavePostAfter
{
    public function execute(Event $event): void
    {
        $post = $event->getData('post');
        // do something after post is saved
    }
}
```

```xml
<!-- etc/events.xml -->
<event name="model_save_after">
    <observer name="vendor_module_save_post_after" instance="Vendor\Module\Observer\SavePostAfter"/>
</event>
```

**Source**: `src/vendor/magento/module-backend/etc/events.xml` — Magento core uses observers extensively.

**Official documentation**: [Events and Observers](https://developer.adobe.com/commerce/php/architecture/event-driven-architecture/)

---

## 14. Install/Upgrade scripts

### 14.1 Declarative schema (`db_schema.xml`)

Since Magento 2.3, schema is declared in XML:

```xml
<!-- etc/db_schema.xml -->
<schema xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <table name="vendor_module_post" resource="default" engine="innodb">
        <column xsi:type="int" name="entity_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="entity_id"/>
        </constraint>
    </table>
</schema>
```

**Source**: `src/vendor/magento/module-catalog/etc/db_schema.xml` — Magento core defines all table schemas declaratively.

### 14.2 Data patches

Data patches insert or modify data during `setup:upgrade`:

```php
// Setup/Patch/Data/CreateDefaultCategory.php
class CreateDefaultCategory implements DataPatchInterface
{
    public function apply(): void
    {
        // create category, store views, etc.
    }
}
```

**Source**: `src/vendor/magento/module-catalog/Setup/Patch/Data/` — Magento core data patches for catalog initialization.

---

## 15. ACL and Admin Menu

**ACL** (Access Control List) controls which admin roles can access which
features.

```xml
<!-- etc/acl.xml -->
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="Vendor_Module::main" title="My Module" sortOrder="10">
                <resource id="Vendor_Module::post" title="Posts" sortOrder="10"/>
                <resource id="Vendor_Module::category" title="Categories" sortOrder="20"/>
            </resource>
        </resource>
    </resources>
</acl>
```

```xml
<!-- etc/adminhtml/menu.xml -->
<menu>
    <add id="Vendor_Module::main"
         title="My Module"
         module="Vendor_Module"
         sortOrder="100"
         parent="Magento_Backend::content"/>
</menu>
```

**Source**: `src/vendor/magento/module-backend/etc/acl.xml` and `menu.xml` — Magento core defines its own ACL and menu structure.

**Official documentation**: [ACL](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/acl/)

---

## 16. CLI commands

Magento CLI (`bin/magento`) is the Swiss Army knife for developers:

```bash
# Enable/disable modules
bin/magento module:enable Vendor_Module
bin/magento module:disable Vendor_Module

# Run database upgrades
bin/magento setup:upgrade

# Compile dependency injection (production)
bin/magento setup:di:compile

# Deploy static content
bin/magento setup:static-content:deploy -f

# Flush cache
bin/magento cache:flush

# Reindex
bin/magento indexer:reindex

# Enter maintenance mode
bin/magento maintenance:enable

# List all commands
bin/magento list
```

**Source**: `src/vendor/magento/framework/Console/CommandListInterface.php` — all Magento CLI commands are registered through the command list.

---

## 17. Summary

| Magento Core Concept | Explanation |
|----------------------|-------------|
| Module | Folder that groups code, config, templates for a feature |
| Area | Application context (frontend, adminhtml, webapi_rest, graphql) |
| Service Contract | Public interface for business logic (Repository pattern) |
| Plugin | Intercepts public methods to modify behavior |
| Observer | Reacts to events dispatched by Magento |
| Layout XML | Page structure: which blocks appear where |
| UI Component | Admin grids/forms (XML → JS → HTML) |
| db_schema.xml | Declarative database schema |
| Data Patch | Versioned PHP that modifies data during setup:upgrade |
| ACL | Access control for admin users |
| CLI | bin/magento commands for all operations |

---

## 18. AlpineCommerce Reference

The following sections show how AlpineCommerce applies Magento's core
concepts in its custom modules. These are **project-specific implementations**
built on top of Magento 2.4.8 Core.

### 18.1 Module structure

AlpineCommerce modules follow the standard Magento structure but with
project-specific conventions:

| Module | Registration | Key features |
|--------|-------------|-------------|
| Blog | `AlpineCommerce_Blog` | Admin CRUD for posts/categories, UI Components |
| Faq | `AlpineCommerce_Faq` | Admin CRUD for FAQ items |
| StorePickup | `AlpineCommerce_StorePickup` | Carrier plugin, checkout integration, KO component |
| CustomerCare | `AlpineCommerce_CustomerCare` | VIP levels, cron job, plugin on Order |
| LoyaltyProgram | `AlpineCommerce_LoyaltyProgram` | Total collector, minicart KO component, invoice plugin |
| Gdpr | `AlpineCommerce_Gdpr` | Consent logging, export functionality |
| StoreLocator | `AlpineCommerce_StoreLocator` | Store finder, map integration |
| AutoInvoice | `AlpineCommerce_AutoInvoice` | Observer on checkout success |
| CreditMemo | `AlpineCommerce_CreditMemo` | Plugin on Order cancellation |

**Sources**:
- `src/app/code/AlpineCommerce/Blog/etc/module.xml`
- `src/app/code/AlpineCommerce/StorePickup/etc/module.xml`
- `src/app/code/AlpineCommerce/CustomerCare/etc/module.xml`

### 18.2 AlpineCommerce module conventions

| Convention | AlpineCommerce approach |
|-----------|------------------------|
| Namespace | `AlpineCommerce\` |
| Module prefix | `AlpineCommerce_` |
| Admin menu | Content section (Blog, Faq, StorePickup, StoreLocator) |
| Admin menu | Marketing section (ProductReviews, ProductQuestions) |
| Admin menu | Catalog section (ProductLabels) |
| Admin menu | GDPR section (Gdpr) |
| Admin menu | Customers section (CustomerCare) |
| Frontend patterns | KO components (StorePickup, LoyaltyProgram) |
| Frontend patterns | jQuery AJAX (ProductReviews, ProductQuestions) |
| Frontend patterns | Vanilla JS (StoreLocator) |

### 18.3 AlpineCommerce-specific patterns

**StorePickup** — Shipping carrier plugin that modifies Flat Rate behavior:
- `Plugin/Shipping/FilterFlatRate.php` — filters flat rate when free shipping threshold met
- `Plugin/Shipping/FilterFreeShipping.php` — filters free shipping
- `view/frontend/web/js/view/store-pickup.js` — Knockout component for checkout
- `Model/Carrier/StorePickup.php` — custom carrier implementation

**CustomerCare** — VIP customer management:
- `Cron/UpdateVipLevels.php` — nightly cron to recalculate VIP tiers
- `Plugin/Order/AfterPlace.php` — recalculates VIP after order placement
- `Api/CustomerCareInterface.php` — public API for VIP status

**LoyaltyProgram** — Loyalty points system:
- `Plugin/Invoice/AfterSave.php` — awards points after invoice
- `Plugin/Order/AfterSave.php` — deducts points after order save
- `Plugin/Minicart/Incentive.php` — adds points display to minicart
- `view/frontend/web/js/view/loyalty-points.js` — checkout KO component

**AutoInvoice** — Automatic invoicing:
- `Observer/AutoInvoice.php` — listens to `checkout_onepage_controller_success_action`

**CreditMemo** — Automatic credit memo:
- `Plugin/OrderCancelPlugin.php` — creates credit memo on order cancellation

**Sources**:
- `src/app/code/AlpineCommerce/StorePickup/`
- `src/app/code/AlpineCommerce/CustomerCare/`
- `src/app/code/AlpineCommerce/LoyaltyProgram/`
- `src/app/code/AlpineCommerce/AutoInvoice/`
- `src/app/code/AlpineCommerce/CreditMemo/`

---

## 19. Next steps

Now that you understand Magento basics:
1. Read `docs/magento2/magento-components.md` for the request lifecycle
2. Read `docs/magento2/magento-events-observers-plugins.md` for extension mechanisms
3. Read `docs/ENGINEERING_GUIDE.md` for project standards
4. Explore `src/app/code/AlpineCommerce/Blog/` (the simplest module)
5. Read module-specific docs in `docs/modules/`

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| Architecture Overview | [developer.adobe.com/commerce/php/architecture/](https://developer.adobe.com/commerce/php/architecture/) |
| Module File Structure | [developer.adobe.com/commerce/php/architecture/modules/](https://developer.adobe.com/commerce/php/architecture/modules/) |
| Service Contracts | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/) |
| Multi-Stores | [developer.adobe.com/commerce/php/architecture/modules/multi-stores/](https://developer.adobe.com/commerce/php/architecture/modules/multi-stores/) |
| Layouts | [developer.adobe.com/commerce/php/architecture/layouts/](https://developer.adobe.com/commerce/php/architecture/layouts/) |
| UI Components | [developer.adobe.com/commerce/php/tutorials/ui-components/](https://developer.adobe.com/commerce/php/tutorials/ui-components/) |
| Events and Observers | [developer.adobe.com/commerce/php/architecture/event-driven-architecture/](https://developer.adobe.com/commerce/php/architecture/event-driven-architecture/) |
| Plugins (Interceptors) | [developer.adobe.com/commerce/php/architecture/modules/extension-attributes/plugins/](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/plugins/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under:

- `src/vendor/magento/framework/` — Magento framework
- `src/vendor/magento/module-*/` — Magento core modules

AlpineCommerce-specific implementations are referenced from:

- `src/app/code/AlpineCommerce/` — AlpineCommerce custom modules

*Last updated: 2026-09-07*
