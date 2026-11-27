# Magento 2 from Scratch — Inside a Module

> **Series**: [Learning Path](../README.md) → Phase 1, Article 2 of 5
> **Previous**: [`01-what-is-magento.md`](01-what-is-magento.md) — What even is Magento?
> **Next**: [`03-request-lifecycle.md`](03-request-lifecycle.md) — How a request flows through Magento
> **Deep dive**: [`../magento-intro.md`](../magento-intro.md) — module structure reference

---

A Magento **module** is a folder that contains everything for one feature:
PHP code, database schema, admin menus, routes, CSS, JS, translations.

The simplest module (AlpineCommerce's `Blog`) has this structure. Let's walk
through it.

---

## 1. The three required files

Every module starts with three files:

```
src/app/code/AlpineCommerce/Blog/
├── registration.php          ← tells Magento "I exist"
├── etc/
│   ├── module.xml            ← declares name, version, dependencies
│   └── ...
```

**`registration.php`** — registers the module component:

```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'AlpineCommerce_Blog',
    __DIR__
);
```

**`etc/module.xml`** — declares the module and its load order:

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <module name="AlpineCommerce_Blog" setup_version="1.1.0">
        <sequence>
            <module name="Magento_Framework"/>
        </sequence>
    </module>
</config>
```

- **`setup_version`** — used for data patches (more on that later).
- **`<sequence>`** — this module must load **after** the listed modules. If
  `Magento_Catalog` is listed, `AlpineCommerce_Blog` can rely on catalog tables
  being available.

---

## 2. The folder structure (mapped to purpose)

```
AlpineCommerce/Blog/
├── registration.php
├── etc/                  ← ALL configuration (XML)
│   ├── module.xml        ← module declaration + dependencies
│   ├── frontend/
│   │   └── routes.xml    ← frontend URLs (/blog)
│   ├── adminhtml/
│   │   ├── routes.xml    ← admin URLs (/admin/blog)
│   │   ├── menu.xml      ← admin menu entry
│   │   ├── acl.xml       ← who can access what
│   │   └── system.xml    ← admin configuration fields
│   ├── di.xml            ← dependency injection (factories, plugins, etc.)
│   ├── db_schema.xml     ← database tables (declarative schema)
│   └── webapi.xml        ← REST API endpoints
├── Api/                  ← Service Contracts (interfaces)
│   ├── Data/
│   │   ├── BlogPostInterface.php
│   │   └── BlogPostSearchResultsInterface.php
│   └── BlogPostRepositoryInterface.php
├── Model/                ← Concrete implementations
│   ├── BlogPost.php      ← Entity model (implements the interface)
│   ├── BlogPostRepository.php  ← Repository (business logic)
│   └── ResourceModel/
│       ├── BlogPost.php  ← SQL queries (INSERT, SELECT, UPDATE)
│       └── Collection.php  ← SQL results as a collection
├── Controller/           ← URL handlers (frontend + admin)
│   ├── Frontend/
│   │   ├── Index/Index.php  ← /blog
│   │   └── Index/View.php   ← /blog/post/view
│   └── Adminhtml/
│       ├── Post/Index.php   ← admin grid
│       └── Post/Edit.php    ← admin form
├── Block/                ← Prepares data for templates
│   ├── List.php
│   ├── Detail.php
│   └── Adminhtml/
│       └── Post/Edit/
│           ├── BackButton.php
│           ├── GenericButton.php
│           └── SaveButton.php
├── Ui/                   ← Admin grids & forms (UI Components)
│   ├── DataProvider/
│   │   ├── PostListingDataProvider.php
│   │   └── PostFormDataProvider.php
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── PostActions.php
├── view/                 ← Templates, layouts, CSS, JS
│   ├── frontend/
│   │   ├── layout/
│   │   │   ├── blog_index_index.xml
│   │   │   └── blog_index_view.xml
│   │   ├── templates/
│   │   │   ├── listing.phtml
│   │   │   └── view.phtml
│   └── adminhtml/
│       ├── layout/
│       │   ├── blog_post_index.xml
│       │   ├── blog_post_edit.xml
│       │   └── blog_post_new.xml
│       └── ui_component/
│           ├── blog_post_form.xml
│           └── blog_post_listing.xml
├── Setup/
│   └── Patch/
│       └── Data/
├── i18n/                 ← Translation CSVs (de_DE, fr_FR, ...)
└── composer.json
```

---

## 3. How the pieces talk to each other

The **Service Contract pattern** is the backbone of every Magento module:

```
Controller / REST / GraphQL
         │
         ▼
  Interface (Api/BlogPostRepositoryInterface.php)  ← "what" it does
         │
         ▼
  Implementation (Model/BlogPostRepository.php)  ← "how" it does it
         │
         ▼
  ResourceModel (Model/ResourceModel/BlogPost.php) ← SQL
         │
         ▼
  MySQL database
```

Why the interface? So the REST API, the admin controller and a test can all
call the **same RepositoryInterface**. If you later add caching, you swap the
implementation without touching callers.

---

## 4. Routes — how URLs map to controllers

**`etc/frontend/routes.xml`** declares the URL prefix:

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <router id="standard">
        <route id="blog" route_type="frontend" frontName="blog">
            <module name="AlpineCommerce_Blog"/>
        </route>
    </router>
</config>
```

Now `/blog` maps to `Blog\Controller\Index\Index`. The naming rule is:

```
frontName (/blog)  →  {Vendor}\{Module}\Controller\{Controller}\{Action}
/blog              →  AlpineCommerce\Blog\Controller\Index\Index
/blog/post/view    →  AlpineCommerce\Blog\Controller\Post\View
```

The layout file is found by convention: `blog_index_index.xml` for the
`Index/Index` action, `blog_post_view.xml` for `Post/View`.

---

## 5. Database schema — declarative, not imperative

Gone are the old `InstallSchema.php` scripts. Since Magento 2.3, schema is
declared in XML:

**`etc/db_schema.xml`** (simplified):

```xml
<schema>
    <table name="alpinecommerce_blog_post">
        <column xsi:type="int" name="entity_id" nullable="false"
                identity="true" unsigned="true"/>
        <column xsi:type="varchar" name="title" nullable="false" length="255"/>
        <column xsi:type="varchar" name="content" nullable="true"/>
        <column xsi:type="int" name="status" nullable="false" default="0"/>
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="entity_id"/>
        </constraint>
    </table>
</schema>
```

Magento compares this XML to the live database and applies the diff. You never
write `ALTER TABLE` by hand.

---

## 6. Service Contracts — the public API

**`etc/webapi.xml`** exposes the Repository as a REST endpoint:

```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <service class="AlpineCommerce\Blog\Api\BlogPostRepositoryInterface"
             method="getList"
             alias="alpineBlogGetList"/>
</config>
```

This automatically creates `GET /rest/V1/blog/posts`. No controller code needed.

---

## 7. What you should remember

1. **Three required files**: `registration.php`, `etc/module.xml`, and your
   first feature file.
2. **Folders are purpose-driven**: `Api/` = interfaces, `Model/` = code,
  `view/` = templates/CSS/JS, `etc/` = all configuration.
3. **Service Contract pattern**: interface → implementation → ResourceModel →
   SQL. Never skip the interface.
4. **Routes** map `frontName` to `Controller\Class\Action`; layout files follow
   the same naming convention.
5. **Database** is declared in `db_schema.xml`, not imperative PHP.
6. **REST APIs** are exposed through `webapi.xml` — no controller needed.

---

## 8. Where to go next

- **Article 3**: [Request Lifecycle](03-request-lifecycle.md) — trace a real
  request from URL to HTML.
- **Article 1** reviewed: [What is Magento?](01-what-is-magento.md)
- **Deep dive**: [`../magento-intro.md`](../magento-intro.md) — canonical
  module skeleton with every folder explained.

---

*Sources: `src/app/code/AlpineCommerce/Blog/`, `src/vendor/magento/framework/`*
