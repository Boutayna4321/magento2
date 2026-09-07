# Magento 2 — Components and Interactions

> **Target audience**: beginners who want to understand **who calls who**
> in Magento 2. This guide shows the complete flow of a request, from the browser
> to the database, and how the components (Controller, Block,
> Template, Model, UI Component…) collaborate.

---

## Table of Contents

1. [Layer overview](#1-layer-overview)
2. [The complete flow of a Magento page](#2-the-complete-flow-of-a-magento-page)
3. [Magento components and their relationships](#3-magento-components-and-their-relationships)
4. [Detailed flow by page type](#4-detailed-flow-by-page-type)
5. [The 3 types of Magento requests](#5-the-3-types-of-magento-requests)
6. [The Layout system (page structure)](#6-the-layout-system-page-structure)
7. [UI Components (admin)](#7-ui-components-admin)
8. [Magento Design Patterns](#8-magento-design-patterns)
9. [The request lifecycle (visual summary)](#9-the-request-lifecycle-visual-summary)
10. [Summary](#10-summary)
11. [AlpineCommerce Reference](#11-alpinecommerce-reference)

---

## 1. Layer overview

```
┌─────────────────────────────────────────────────────────────┐
│                     BROWSER (client)                        │
│                   http://localhost:8080/blog                 │
└───────────────────────────┬─────────────────────────────────┘
                              │ HTTP Request
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  NGINX (web server)                                          │
│  - serves static files (CSS, JS, images)                     │
│  - forwards dynamic requests to PHP-FPM                      │
└───────────────────────────┬─────────────────────────────────┘
                              │ fastcgi
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  PHP-FPM 8.2                                                │
│  - executes index.php (single entry point)                   │
└───────────────────────────┬─────────────────────────────────┘
                              │ bootstrap
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  MAGENTO FRONT CONTROLLER                                   │
│  - identifies the area (frontend / adminhtml / webapi_rest)  │
│  - instantiates the Router                                   │
└───────────────────────────┬─────────────────────────────────┘
                              │ match URL
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  ROUTER                                                      │
│  - compares the URL to routes declared in routes.xml          │
│  - finds: module=Blog, controller=index, action=index        │
│  → class: Vendor\Module\Controller\Index\Index               │
└───────────────────────────┬─────────────────────────────────┘
                              │ dispatch
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  CONTROLLER                                                   │
│  - orchestrates the request                                   │
│  - does NOT contain business logic                            │
│  - calls the Repository (Service Contract)                    │
│  - returns a Result (page, JSON, redirect)                    │
└───────────────────────────┬─────────────────────────────────┘
                              │ result
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  RESPONSE                                                     │
│  - HTML (full page) / JSON (REST) / Redirect                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. The complete flow of a Magento page

### 2.1 Frontend page: `/blog`

```mermaid
flowchart TD
    A["Browser<br/>GET /blog"] --> B["Nginx"]
    B --> C["index.php<br/>(Magento bootstrap)"]
    C --> D["Front Controller<br/>(area = frontend)"]
    D --> E["Router<br/>(routes.xml)"]
    E --> F["Controller<br/>Vendor\\Module\\Controller\\Index\\Index"]
    F --> G["Repository<br/>PostRepository"]
    G --> H["ResourceModel<br/>Post"]
    H --> I["MySQL<br/>SELECT * FROM vendor_module_post"]
    I --> H
    H --> G
    G --> F
    F --> J["Block<br/>PostList"]
    J --> K["Template<br/>post/list.phtml"]
    K --> L["HTML<br/>(list of posts)"]
    L --> M["Browser"]
```

**Step by step:**

| # | Component | Role |
|---|-----------|------|
| 1 | **Nginx** | Receives the HTTP request, serves static files |
| 2 | **index.php** | Single entry point, bootstraps Magento |
| 3 | **Front Controller** | Identifies the area (`frontend`, `adminhtml`, `webapi_rest`) |
| 4 | **Router** | Matches the URL to a Controller class via `routes.xml` |
| 5 | **Controller** | Orchestrates, calls services, returns a Result |
| 6 | **Repository** | Business logic (save, getById, getList) |
| 7 | **ResourceModel** | Executes SQL queries |
| 8 | **Block** | Prepares data for the template |
| 9 | **Template** | Displays HTML (`.phtml`) |
| 10 | **Response** | Returns the complete HTML to the browser |

### 2.2 Admin page: edit form

```mermaid
flowchart TD
    A["Admin<br/>GET /admin/blog/post/edit/id/1"] --> B["Nginx"]
    B --> C["index.php"]
    C --> D["Front Controller<br/>(area = adminhtml)"]
    D --> E["Router<br/>(adminhtml/routes.xml)"]
    E --> F["Controller<br/>Vendor\\Module\\Adminhtml\\Post\\Edit"]
    F --> G["Repository<br/>PostRepository::getById(1)"]
    G --> H["MySQL"]
    H --> G
    G --> F
    F --> I["UI Component<br/>vendor_module_post_form"]
    I --> J["DataProvider<br/>PostFormDataProvider"]
    J --> K["Repository<br/>PostRepository"]
    K --> L["MySQL"]
    L --> K
    K --> J
    J --> I
    I --> M["Layout XML<br/>_edit.xml"]
    M --> N["Block Container"]
    N --> O["HTML<br/>(form with fields)"]
    O --> P["Browser"]
```

**Differences with the frontend:**
- The **area** is `adminhtml` (not `frontend`)
- Admin forms use **UI Components** (`<form>` in XML) instead of classic Blocks + Templates
- A **DataProvider** feeds the form with data (calls the Repository)

**Source**: `src/vendor/magento/module-catalog/Controller/Adminhtml/Product/Edit.php` — Magento core admin product edit controller follows the same pattern.

---

## 3. Magento components and their relationships

### 3.1 Responsibility map

```
┌──────────────────────────────────────────────────────────────┐
│                      BROWSER                                   │
│            (displays HTML, CSS, JS, images)                   │
└────────────────────────────┬─────────────────────────────────┘
                               │
                ┌──────────────┼──────────────┐
                ▼              ▼              ▼
          ┌──────────┐  ┌──────────┐  ┌──────────┐
          │  Nginx   │  │  Nginx   │  │  Nginx   │
          │ (:8080)  │  │ (:8080)  │  │ (:8080)  │
          └────┬─────┘  └────┬─────┘  └────┬─────┘
               │             │             │
               ▼             ▼             ▼
          ┌──────────────────────────────────────┐
          │          PHP-FPM (index.php)          │
          └──────────────────┬───────────────────┘
                             │
                             ▼
          ┌──────────────────────────────────────┐
          │      MAGENTO FRAMEWORK                │
          │  ┌────────────────────────────────┐  │
          │  │  Object Manager (DI Container) │  │
          │  │  - builds all objects          │  │
          │  │  - injects dependencies        │  │
          │  └──────────┬─────────────────────┘  │
          │             │                         │
          │  ┌──────────┴─────────────────────┐  │
          │  │                                 │  │
          │  ▼                                 ▼  │
          │ ┌─────────────┐          ┌──────────────┐
          │ │   Router     │          │  WebAPI      │
          │ │ (frontend,   │          │  (REST,      │
          │ │  adminhtml)  │          │   GraphQL)   │
          │ └──────┬──────┘          └──────────────┘
          │        │
          │        ▼
          │ ┌─────────────┐
          │ │  Controller  │
          │ │  (orchestrates) │
          │ └──────┬──────┘
          │        │
          │        ▼
          │ ┌─────────────┐      ┌──────────────┐
          │ │  Repository  │◄────►│   Block /    │
          │ │  (business)  │      │   UI DataProv│
          │ └──────┬──────┘      └──────┬───────┘
          │        │                    │
          │        ▼                    ▼
          │ ┌─────────────┐      ┌──────────────┐
          │ │ ResourceModel│      │  Template    │
          │ │  (SQL)       │      │  (.phtml)    │
          │ └──────┬──────┘      └──────┬───────┘
          │        │                    │
          │        ▼                    │
          │ ┌─────────────┐             │
          │ │    MySQL     │             │
          │ │  (data)      │             │
          │ └─────────────┘             │
          │                              │
          │        ┌─────────────────────┘
          │        ▼
          │ ┌─────────────┐
          │ │   Layout     │
          │ │  (structure) │
          │ └──────┬──────┘
          │        │
          │        ▼
          │ ┌─────────────┐
          │ │    HTML      │
          │ │  (Response)  │
          │ └─────────────┘
          │
          └──────────────────────────────────────┘
```

### 3.2 Who calls who? (reference table)

| Component | Calls | Called by | Role |
|-----------|-------|-----------|------|
| **Router** | Controller | Front Controller | Finds the right Controller based on the URL |
| **Controller** | Repository, Block, ResultFactory | Router | Orchestrates the request |
| **Repository** | ResourceModel, other Repositories | Controller, Block, DataProvider | Business logic |
| **ResourceModel** | Connection (MySQL) | Repository | SQL queries |
| **Block** | Repository, Helper, other Blocks | Layout XML | Prepares data for display |
| **Template (.phtml)** | Block (via `$this`) | Block | Displays HTML |
| **UI DataProvider** | Repository | UI Component XML | Feeds admin grids/forms |
| **Layout XML** | Block | Controller (via Result) | Defines page structure |
| **Plugin** | Method of a target class | Automatic (DI) | Modifies method behavior |
| **Observer** | Any service | Dispatched Event | Reacts to a business event |
| **Helper** | Other services | Block, Template | Cross-cutting tools (config, logs) |
| **ResultFactory** | N/A | Controller | Creates the response (page, JSON, redirect) |
| **Object Manager** | All classes | Automatic | Creates objects, injects dependencies |

**Source**: `src/vendor/magento/framework/App/FrontController.php` — dispatches requests through the component chain.

---

## 4. Detailed flow by page type

### 4.1 CMS page (e.g. `/about-us`)

```
Browser
  → Nginx
    → index.php
      → Router (cms_page_view)
        → Controller (Cms/Page/View)
          → PageRepository (retrieves the page from DB)
            → ResourceModel (SELECT FROM cms_page)
          → ResultPage (created via ResultFactory)
            → Layout (cms_page_view.xml)
              → Block (page)
                → Template (page.phtml)
          → Response (HTML)
    → Browser
```

**Source**: `src/vendor/magento/module-cms/Controller/Page/View.php` — Magento core CMS page controller.

### 4.2 REST API (e.g. `GET /rest/V1/blog/posts`)

```
REST Client
  → Nginx
    → index.php (area = webapi_rest)
      → WebAPI Router (reads webapi.xml)
        → Service Contract (PostRepositoryInterface)
          → Implementation (PostRepository)
            → ResourceModel (SELECT FROM vendor_module_post)
              → MySQL
        → JSON Response
    → REST Client
```

**Key difference**: no Controller, no Block, no Template.
The WebAPI Router calls the **Service Contract** directly.

**Source**: `src/vendor/magento/module-webapi/Controller/Rest.php` — handles REST API requests.

### 4.3 Admin form with UI Component

```
Admin GET /admin/blog/post/edit/id/1
  → Nginx
    → index.php (area = adminhtml)
      → Router
        → Controller (Vendor/Module/Adminhtml/Post/Edit)
          → ResultPage
            → Layout (_edit.xml)
              → UI Component (vendor_module_post_form)
                → DataProvider (PostFormDataProvider)
                  → Repository (PostRepository::getById)
                    → MySQL
                → Form fields (inputs generated by JS)
              → Block (container)
          → Response (HTML + JS that initializes the form)

Admin POST /admin/blog/post/save
  → Nginx
    → index.php (area = adminhtml)
      → Router
        → Controller (Vendor/Module/Adminhtml/Post/Save)
          → Repository (PostRepository::save)
            → ResourceModel (INSERT/UPDATE)
              → MySQL
          → ResultRedirect (to the list)
          → Response (redirect)
```

**Source**: `src/vendor/magento/module-catalog/Controller/Adminhtml/Product/Save.php` — Magento core product save controller.

---

## 5. The 3 types of Magento requests

| Type | Area | Entry point | Response | Example |
|------|------|-------------|----------|---------|
| **Frontend page** | `frontend` | Controller → Block → Template | Full HTML | `/blog`, `/catalog/product/view/id/1` |
| **Admin page** | `adminhtml` | Controller → UI Component → DataProvider | HTML + JS | `/admin/blog/post/edit` |
| **REST API** | `webapi_rest` | WebAPI Router → Service Contract | JSON | `/rest/V1/blog/posts` |
| **SOAP API** | `webapi_soap` | WebAPI Router → Service Contract | SOAP XML | `/soap/?wsdl` |
| **GraphQL API** | `graphql` | GraphQL Router → Resolver | JSON | `/graphql` |

---

## 6. The Layout system (page structure)

The **Layout** is the page skeleton. It defines which Blocks
appear and where.

### 6.1 Example: blog page

```xml
<!-- view/frontend/layout/blog_index_index.xml -->
<page xmlns:xsi="..." layout="1column">
    <body>
        <referenceContainer name="content">
            <block class="Vendor\Module\Block\PostList"
                   name="blog.post.list"
                   template="Vendor_Module::post/list.phtml"
                   before="-"/>
        </referenceContainer>
    </body>
</page>
```

**What happens:**
1. The Controller returns a `ResultPage`
2. Magento loads the layout XML corresponding to the route (`blog_index_index`)
3. The layout XML adds a block `blog.post.list` in the `content` container
4. The `PostList` block calls the Repository to retrieve the posts
5. The template `list.phtml` is rendered with the block's data

**Source**: `src/vendor/magento/module-theme/view/frontend/layout/default.xml` — Magento core defines the default page containers.

### 6.2 Containers

A **container** is an empty location in the page:

| Container | Contains | Defined in |
|-----------|----------|------------|
| `page.top` | Header | `Magento_Theme/layout/default.xml` |
| `content` | Main content | `Magento_Theme/layout/default.xml` |
| `page.bottom` | Footer | `Magento_Theme/layout/default.xml` |
| `sidebar.main` | Left sidebar (filters, categories) | `Magento_Theme/layout/default.xml` |
| `sidebar.additional` | Right sidebar (widgets) | `Magento_Theme/layout/default.xml` |

Modules use `<referenceContainer>` to add content in
these locations without rewriting the complete layout.

---

## 7. UI Components (admin)

In the admin, Magento uses **UI Components** instead of classic Blocks +
Templates. It is an XML → JavaScript → HTML system.

### 7.1 UI Component architecture

```
XML (listing/form)
    ↓
JS (Magento_Ui/js/core/app interprets the XML)
    ↓
JS Components (grid, form, columns, filters)
    ↓
KO Templates (knockout.js: bindings, conditional display)
    ↓
HTML (generated by the browser)
    ↓
AJAX (calls to the DataProvider for data)
```

### 7.2 Example: Admin grid

```xml
<!-- view/adminhtml/ui_component/vendor_module_post_listing.xml -->
<listing xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <dataSource name="post_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                Vendor\Module\Ui\DataProvider\PostListingDataProvider
            </argument>
            <argument name="name" xsi:type="string">post_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">entity_id</argument>
            <argument name="requestFieldName" xsi:type="string">id</argument>
        </argument>
    </dataSource>
    
    <columns name="post_columns">
        <column name="title">
            <settings>
                <label translate="true">Title</label>
                <sortOrder>10</sortOrder>
            </settings>
        </column>
        <column name="status">
            <settings>
                <label translate="true">Status</label>
                <sortOrder>20</sortOrder>
                <filter>select</filter>
            </settings>
        </column>
        <actions>
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="urlPath" xsi:type="string">module/post/edit</item>
                    <item name="paramName" xsi:type="string">id</item>
                </item>
            </argument>
        </actions>
    </columns>
</listing>
```

**UI Component flow:**

```mermaid
flowchart TD
    A["Admin opens<br/>/admin/blog/post"] --> B["Controller<br/>Vendor\\Module\\Adminhtml\\Post\\Index"]
    B --> C["ResultPage"]
    C --> D["Layout XML<br/>(_index.xml)"]
    D --> E["UI Component XML<br/>(listing)"]
    E --> F["JavaScript<br/>(Magento_Ui/js/core/app)"]
    F --> G["UI Components JS<br/>(grid, columns, filters)"]
    G --> H["KO Templates<br/>(HTML rendering)"]
    H --> I["Initial AJAX<br/>(loads data)"]
    I --> J["DataProvider<br/>(PostListingDataProvider)"]
    J --> K["Repository<br/>(getList)"]
    K --> L["Collection<br/>(SQL)"]
    L --> M["MySQL"]
    M --> L
    L --> K
    K --> J
    J --> N["JSON response"]
    N --> G
    G --> O["Grid displayed<br/>(with pagination, filters, sorting)"]
```

**Source**: `src/vendor/magento/module-catalog/view/adminhtml/ui_component/product_listing.xml` — Magento core product listing uses the same UI Component structure.

**Official documentation**: [UI Components Overview](https://developer.adobe.com/commerce/php/tutorials/ui-components/)

---

## 8. Magento Design Patterns

### 8.1 Service Contract Pattern

```
Controller / REST / GraphQL
         │
         ▼
   Interface (Api/PostRepositoryInterface.php)
         │
         ▼
   Implementation (Model/PostRepository.php)
         │
         ▼
   ResourceModel (Model/ResourceModel/Post.php)
         │
         ▼
   Database
```

**Advantage**: you can change the implementation without touching the Controller,
the REST API, or GraphQL.

**Source**: `src/vendor/magento/module-catalog/Api/ProductRepositoryInterface.php` — Magento core uses Service Contracts for all major entities.

### 8.2 Factory Pattern

```php
// Instead of new Post()
$post = $postFactory->create(); // PostFactory injected by DI
$post->setTitle('Hello');
$post->save();
```

**Factories** create objects dynamically. Magento generates
them automatically via `di.xml` or `codeGeneration`.

**Source**: `src/vendor/magento/framework/Factory/Factory.php` — base factory implementation.

### 8.3 Proxy Pattern

**Proxies** defer loading a dependency until it is actually used. Declared in `di.xml`:

```xml
<type name="Vendor\Module\Model\PostRepository">
    <arguments>
        <argument name="logger" xsi:type="object">Vendor\Module\Model\Logger\Proxy</argument>
    </arguments>
</type>
```

**Source**: `src/vendor/magento/framework/Proxy/Battery/Complex.php` — example proxy in Magento core.

### 8.4 Repository Pattern

The Repository is the **only entry point** for accessing data:

```php
interface PostRepositoryInterface
{
    public function save(PostInterface $post): PostInterface;
    public function getById(int $id): PostInterface;
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
    public function delete(PostInterface $post): bool;
}
```

Never use `$connection->fetchRow()` in a Controller or Block.

**Source**: `src/vendor/magento/module-catalog/Model/ProductRepository.php` — Magento core product repository.

### 8.5 Data Patch Pattern

```php
class CreateDefaultCategory implements DataPatchInterface
{
    public function apply(): void { /* insert data */ }
    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
```

Data Patches are versioned PHP classes that modify data
(or schema) during `bin/magento setup:upgrade`.

**Source**: `src/vendor/magento/module-catalog/Setup/Patch/Data/` — Magento core data patches.

---

## 9. The request lifecycle (visual summary)

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Browser  │────▶│  Nginx   │────▶│ index.php│────▶│   Area   │
│ (URL)    │     │          │     │          │     │Detection │
└──────────┘     └──────────┘     └──────────┘     └────┬─────┘
                                                         │
                      ┌──────────────────────────────────┼──────────┐
                      ▼                                  ▼          ▼
               ┌─────────────┐                  ┌─────────────┐ ┌──────────┐
               │   frontend   │                  │ adminhtml   │ │webapi_rest│
               └──────┬──────┘                  └──────┬──────┘ └────┬─────┘
                      ▼                               ▼             ▼
               ┌─────────────┐                  ┌─────────────┐ ┌──────────┐
               │   Router     │                  │   Router     │ │WebAPI    │
               └──────┬──────┘                  └──────┬──────┘ │Router    │
                      ▼                               ▼         └────┬─────┘
               ┌─────────────┐                  ┌─────────────┐        │
               │  Controller  │                  │  Controller  │       ▼
               └──────┬──────┘                  └──────┬──────┘ ┌──────────┐
                      ▼                               ▼         │ Service  │
               ┌─────────────┐                  ┌─────────────┐ │Contract  │
               │ Block/Template│                 │ UI Component │ └────┬─────┘
               └──────┬──────┘                  └──────┬──────┘      │
                      ▼                               ▼            ▼
               ┌─────────────┐                  ┌─────────────┐ ┌──────────┐
               │ Repository   │                  │ DataProvider │ │Repository│
               └──────┬──────┘                  └──────┬──────┘ └────┬─────┘
                      ▼                               ▼            ▼
               ┌─────────────┐                  ┌─────────────┐ ┌──────────┐
               │ ResourceModel │                 │ Repository   │ │ResourceModel│
               └──────┬──────┘                  └──────┬──────┘ └────┬─────┘
                      ▼                               ▼            ▼
               ┌─────────────┐                  ┌─────────────┐ ┌──────────┐
               │    MySQL      │                  │    MySQL     │ │   MySQL  │
               └──────────────┘                  └──────────────┘ └──────────┘
```

---

## 10. Summary

| Question | Answer |
|----------|--------|
| **Where does a request start?** | `index.php` → Front Controller → Router |
| **Who chooses which Controller?** | The `Router` reads `routes.xml` |
| **What does the Controller do?** | It orchestrates: calls services, returns a Result |
| **Where does business logic go?** | In the **Repository** (never in the Controller) |
| **How to access the DB?** | Repository → ResourceModel → MySQL |
| **How to display HTML?** | Controller → Block → Template (.phtml) |
| **How does the admin work?** | Controller → UI Component → DataProvider → Repository |
| **How to add behavior without modifying core?** | **Plugin** (intercepts a method) or **Observer** (reacts to an event) |
| **How to exchange data with the outside?** | **REST API** or **GraphQL** (call Service Contracts directly) |
| **Who builds all the objects?** | The **Object Manager** (DI Container) automatically |

### Design patterns summary

| Pattern | Role | Magento example |
|---------|------|----------------|
| Service Contract | Business interface | `ProductRepositoryInterface` |
| Factory | Create objects dynamically | `ProductFactory` |
| Proxy | Defer loading until needed | `Logger\Proxy` |
| Repository | Data access entry point | `ProductRepository` |
| Data Patch | Versioned data modification | `CreateDefaultCategory` |

---

## 11. AlpineCommerce Reference

### 11.1 Layer mapping

| Layer | Example file | Role in the project |
|-------|--------------|---------------------|
| **Router** | `Blog/etc/frontend/routes.xml` | Maps `/blog` to Controller `Blog\Index\Index` |
| **Controller** | `Blog/Controller/Index/Index.php` | Retrieves posts, returns a page |
| **Repository** | `Blog/Ui/DataProvider/PostListingDataProvider.php` | Fetches posts for admin grid |
| **ResourceModel** | `StorePickup/Model/ResourceModel/StoreInfo.php` | SQL queries for store info |
| **Block** | `Blog/Ui/Component/Listing/Column/PostActions.php` | Actions column in admin grid |
| **Template** | `StorePickup/view/frontend/web/template/store-pickup.html` | KO template for checkout |
| **Layout** | `StorePickup/view/frontend/layout/checkout_index_index.xml` | Places block in checkout |
| **UI DataProvider** | `StorePickup/Ui/DataProvider/StoreInfoListingDataProvider.php` | Feeds admin grid |
| **UI Component** | `StorePickup/view/adminhtml/ui_component/alphacommerce_pickup_store_info_listing.xml` | Admin grid definition |
| **Plugin** | `StorePickup/Plugin/Shipping/FilterFlatRate.php` | Modifies shipping carrier behavior |
| **Observer** | `AutoInvoice/Observer/AutoInvoice.php` | Creates invoice on checkout success |
| **Service Contract** | `StorePickup/Api/StoreInfoRepositoryInterface.php` | Public Repository interface |
| **JS Module** | `StorePickup/view/frontend/web/js/view/store-pickup.js` | KO component for checkout |

### 11.2 AlpineCommerce module responsibilities

| Module | Core pattern used | Key files |
|--------|------------------|-----------|
| Blog | UI Components, Layout | `Ui/DataProvider/`, `view/adminhtml/ui_component/` |
| Faq | UI Components | `Ui/DataProvider/`, `view/adminhtml/ui_component/` |
| StorePickup | Plugin, KO Component, REST | `Plugin/Shipping/`, `view/frontend/web/js/` |
| CustomerCare | Cron, Plugin, Repository | `Cron/`, `Plugin/Order/` |
| LoyaltyProgram | Plugin, KO Component | `Plugin/Invoice/`, `view/frontend/web/js/` |
| Gdpr | UI Components | `Ui/DataProvider/` |
| StoreLocator | Layout, Vanilla JS | `view/frontend/web/js/` |
| AutoInvoice | Observer | `Observer/AutoInvoice.php` |
| CreditMemo | Plugin | `Plugin/OrderCancelPlugin.php` |

### 11.3 AlpineCommerce request flows

**StorePickup checkout flow**:
1. Customer reaches checkout
2. `checkout_index_index.xml` loads `store-pickup.phtml`
3. KO component `store-pickup.js` initializes
4. Customer selects a store → `saveStore()` calls REST
5. REST controller updates checkout session
6. Shipping carrier plugin reads session value

**CustomerCare VIP flow**:
1. Order is placed
2. `CustomerCare/Plugin/Order/AfterPlace.php` intercepts `Order::place()`
3. Plugin recalculates VIP status
4. Nightly cron `UpdateVipLevels.php` recalculates all customers

**Sources**:
- `src/app/code/AlpineCommerce/Blog/`
- `src/app/code/AlpineCommerce/StorePickup/`
- `src/app/code/AlpineCommerce/CustomerCare/`
- `src/app/code/AlpineCommerce/LoyaltyProgram/`
- `src/app/code/AlpineCommerce/AutoInvoice/`
- `src/app/code/AlpineCommerce/CreditMemo/`

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| Architecture Overview | [developer.adobe.com/commerce/php/architecture/](https://developer.adobe.com/commerce/php/architecture/) |
| Layouts | [developer.adobe.com/commerce/php/architecture/layouts/](https://developer.adobe.com/commerce/php/architecture/layouts/) |
| UI Components | [developer.adobe.com/commerce/php/tutorials/ui-components/](https://developer.adobe.com/commerce/php/tutorials/ui-components/) |
| Service Contracts | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/service-contracts/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

AlpineCommerce-specific implementations are referenced from `src/app/code/AlpineCommerce/`.

*Last updated: 2026-09-07*
