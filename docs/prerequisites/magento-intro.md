# Magento 2 — Introduction for Beginners

> **Target audience**: developers who have never used Magento. This guide
> explains the platform's architecture and key concepts needed to understand
> the AlpineCommerce project.

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

---

## 3. Modules — the heart of Magento

Everything in Magento is a **module**. A module is a folder that groups
code, configuration, and templates for a specific feature.

### 3.1 Module structure

```
AlpineCommerce/Blog/
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
    'AlpineCommerce_Blog',
    __DIR__
);
```

`etc/module.xml`:
```xml
<?xml version="1.0"?>
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <module name="AlpineCommerce_Blog" setup_version="1.0.0">
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

---

## 4. Key directories

| Directory | Role |
|-----------|------|
| `src/app/code/AlpineCommerce/` | Custom modules (AlpineCommerce code) |
| `src/app/design/` | Custom themes |
| `src/vendor/` | Third-party libraries (Composer) |
| `src/pub/` | Web root (static files, media) |
| `src/var/` | Cache, logs, sessions, reports |
| `src/generated/` | Generated code (interceptors, proxies) |
| `src/app/etc/` | Global config (`config.php`, `env.php`) |

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
(`catalog_product_flat_*`). AlpineCommerce modules use standard SQL
tables (no EAV) for simplicity.

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

AlpineCommerce uses 4 store views (English, French, German, Spanish)
configured in `StoreSetup/etc/config.xml`.

---

## 8. Themes

A **theme** defines the look and feel (layout, templates, CSS, JS).

- **Parent theme**: `Magento/luma` (or `Magento/blank`)
- **Child theme**: `AlpineCommerce/theme` (inherits from parent)

```
src/app/design/
├── frontend/
│   ├── AlpineCommerce/
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

---

## 9. Layout XML

Layout XML defines the **page structure** (which blocks appear where).

```xml
<!-- view/frontend/layout/default.xml -->
<page xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\PostList"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::post/list.phtml"/>
        </referenceContainer>
    </body>
</page>
```

- `<referenceContainer>`: target an existing container
- `<block>`: add a new block (PHP class + template)

---

## 10. UI Components (Admin)

The admin panel uses **UI Components** (XML → JS → HTML) instead of plain
layout XML. This powers grids, forms, filters.

```xml
<!-- view/adminhtml/ui_component/alphacommerce_blog_post_listing.xml -->
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
            <argument name="class" xsi:type="string">AlpineCommerce\Blog\Ui\DataProvider\PostListingDataProvider</argument>
        </argument>
    </dataSource>
</listing>
```

Key concepts:
- `<listing>`: the grid
- `<columns>`: columns definition
- `<dataSource>`: links to a PHP `DataProvider` (fetches data)

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
<type name="AlpineCommerce\Blog\Model\Post">
    <plugin name="slugify" type="AlpineCommerce\Blog\Plugin\Post\Slugify"/>
</type>
```

Plugin types:
- `before`: runs before the original method
- `after`: runs after the original method
- `around`: replaces the original method entirely

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
    <observer name="blog_post_save_after" instance="AlpineCommerce\Blog\Observer\SavePostAfter"/>
</event>
```

---

## 14. Install/Upgrade scripts

### 14.1 Declarative schema (`db_schema.xml`)

Since Magento 2.3, schema is declared in XML:

```xml
<!-- etc/db_schema.xml -->
<schema xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <table name="alphacommerce_blog_post" resource="default" engine="innodb">
        <column xsi:type="int" name="entity_id" nullable="false" identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="entity_id"/>
        </constraint>
    </table>
</schema>
```

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

---

## 15. ACL and Admin Menu

**ACL** (Access Control List) controls which admin roles can access which
features.

```xml
<!-- etc/acl.xml -->
<acl>
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_Blog::main" title="Blog" sortOrder="10">
                <resource id="AlpineCommerce_Blog::post" title="Posts" sortOrder="10"/>
                <resource id="AlpineCommerce_Blog::category" title="Categories" sortOrder="20"/>
            </resource>
        </resource>
    </resources>
</acl>
```

```xml
<!-- etc/adminhtml/menu.xml -->
<menu>
    <add id="AlpineCommerce_Blog::main"
         title="Blog"
         module="AlpineCommerce_Blog"
         sortOrder="100"
         parent="Magento_Backend::content"/>
</menu>
```

---

## 16. CLI commands

Magento CLI (`bin/magento`) is the Swiss Army knife for developers:

```bash
# Enable/disable modules
bin/magento module:enable AlpineCommerce_Blog
bin/magento module:disable AlpineCommerce_Blog

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

---

## 17. Summary — Magento concepts taught in AlpineCommerce

| Concept | Where used in AlpineCommerce |
|---------|------------------------------|
| Module structure | All modules |
| Service Contracts | Blog, Faq, Gdpr, StorePickup… |
| Repository pattern | All modules with database |
| UI Components (admin) | Gdpr, StorePickup, StoreLocator… |
| Plugins | StorePickup (shipping filter), LoyaltyProgram (minicart) |
| Observers | StoreSetup, LoyaltyProgram, Training |
| Data Patches | StoreSetup (CreateStores), all modules |
| ACL + Menu | Gdpr, StorePickup, StoreLocator… |
| REST API | Blog, Faq, Gdpr, StorePickup… |
| Multi-store | StoreSetup config, Blog categories |
| Themes | Custom Luma-based theme |

---

## 18. Next steps

Now that you understand Magento basics:
1. Start with the **canonical module**: `docs/modules/FAQ.md`
2. Read `docs/ENGINEERING_GUIDE.md` for project standards
3. Explore `src/app/code/AlpineCommerce/Blog/` (the simplest module)

---

*Last updated: 2026-08-11.*
