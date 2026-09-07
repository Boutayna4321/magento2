# Magento 2 — Admin Basics

> **Objective**: understand the Magento administration panel:
> navigation, ACL, system configuration, menus, UI Components, and how to
> extend the admin without touching core code.
> This guide covers **Magento 2.4.8 Core** first, then shows AlpineCommerce
> project-specific implementations.

---

## Table of Contents

1. [Access the admin](#1-access-the-admin)
2. [Key admin concepts](#2-key-admin-concepts)
3. [Stores > Configuration](#3-stores--configuration)
4. [Admin listings (UI Components)](#4-admin-listings-ui-components)
5. [Admin forms (UI Components)](#5-admin-forms-ui-components)
6. [Create a new admin entry](#6-create-a-new-admin-entry)
7. [AlpineCommerce reference](#7-alpinecommerce-reference)
8. [Summary](#8-summary)

---

## 1. Access the admin

### 1.1 URL and credentials

```
Admin URL : http://localhost:8080/admin
Login     : admin / admin123
```

The `/admin` path is defined in `app/etc/env.php`:
```php
'backend' => [
    'frontName' => 'admin'
]
```

**Source**: `app/etc/env.php` — Magento core admin front name configuration.

### 1.2 Admin panel structure

```
Admin
├── Dashboard                    ← Overview (orders, customers)
├── Sales                        ← Sales
│   ├── Orders
│   ├── Invoices
│   ├── Shipments
│   └── Credit Memos
├── Catalog                      ← Catalog
│   ├── Products
│   ├── Categories
│   └── Attributes
├── Customers                     ← Customers
│   ├── All Customers
│   ├── Customer Groups
│   └── Now Online
├── Marketing                     ← Marketing
│   ├── Promotions
│   ├── SEO & Search
│   └── Communications
├── Content                       ← Content
│   ├── Pages
│   ├── Blocks
│   └── Widgets
├── Stores                        ← Stores
│   ├── Settings > Configuration   ← Global configuration
│   ├── All Stores                ← Store management
│   ├── Attributes                ← Product/customer attributes
│   └── Taxes
└── System                        ← System
    ├── Tools > Cache Management
    ├── Tools > Index Management
    ├── Permissions > All Users
    └── Permissions > User Roles
```

**Official documentation**: [Admin Panel](https://developer.adobe.com/commerce/admin/)

---

## 2. Key admin concepts

### 2.1 ACL (Access Control List)

**ACL** controls what each admin user can do.

**ACL structure**:

```xml
<!-- etc/acl.xml -->
<acl xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:Acl/etc/acl.xsd">
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

**Explanation**:
- `Vendor_Module::main`: parent resource (appears in the menu)
- `Vendor_Module::post`: child resource (permission for posts)
- The user must have the `Vendor_Module::post` permission to access posts

**Source**: `vendor/magento/module-backend/etc/acl.xml` — Magento core defines its own ACL structure.

**Official documentation**: [ACL]

### 2.2 Admin menu

```xml
<!-- etc/adminhtml/menu.xml -->
<menu xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:Menu/etc/menu.xsd">
    <add id="Vendor_Module::main"
         title="My Module"
         module="Vendor_Module"
         sortOrder="100"
         parent="Magento_Backend::content"
         resource="Vendor_Module::main"/>
</menu>
```

**Attributes**:
- `id`: must match the ACL
- `title`: text displayed in the menu
- `parent`: where to place the item (`Magento_Backend::content` = main menu)
- `resource`: required ACL resource
- `sortOrder`: position (smaller = higher)

**Source**: `vendor/magento/module-backend/etc/menu.xml` — Magento core menu definition.

**Official documentation**: [Admin Menu]

### 2.3 Route protection

Each admin Controller must check the ACL:

```php
// Controller/Adminhtml/Post/Index.php
namespace Vendor\Module\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Module::post';
    
    public function execute: void
    {
        // If the user does not have permission, Magento automatically displays 403
        // ...
    }
}
```

**Source**: `vendor/magento/module-backend/Controller/Adminhtml/Index.php` — Magento core admin controllers use the same pattern.

---

## 3. Stores > Configuration

### 3.1 Access configuration

```
Admin → Stores → Settings → Configuration
```

### 3.2 Configuration sections

Configuration is organized into **sections**:

```
Stores > Configuration
├── General                     ← General settings
│   ├── General
│   ├── Web
│   ├── Currency Setup
│   └── Store Email Addresses
├── Catalog                     ← Catalog
│   ├── Catalog
│   ├── Price
│   └── Inventory
├── Customers                   ← Customers
│   ├── Customer Configuration
│   ├── Customer Groups
│   └── Login
├── Sales                       ← Sales
│   ├── Checkout
│   ├── Shipping Settings
│   └── Tax
└── Advanced                     ← Advanced
    ├── Admin
    └── System
```

**Source**: `vendor/magento/module-config/etc/system.xml` — Magento core defines all its configuration sections here.

### 3.3 system.xml — Define your own config

```xml
<!-- etc/adminhtml/system.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Config:etc/system_file.xsd">
    <system>
        <section id="vendor_module" translate="label" type="text" sortOrder="100" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>My Module</label>
            <tab>general</tab>
            <resource>Vendor_Module::config</resource>
            
            <group id="general" translate="label" type="text" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                <label>General Configuration</label>
                
                <field id="enabled" translate="label" type="select" sortOrder="10" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Enabled</label>
                    <source_model>Magento\Config\Model\Config\Source\Yesno</source_model>
                    <default>1</default>
                </field>
                
                <field id="posts_per_page" translate="label" type="text" sortOrder="20" showInDefault="1" showInWebsite="1" showInStore="1">
                    <label>Posts Per Page</label>
                    <default>10</default>
                    <validate>validate-digits</validate>
                </field>
            </group>
        </section>
    </system>
</config>
```

**Structure**:
- `<section>`: a section in the config
- `<group>`: a group in the section
- `<field>`: a configuration field

**Source**: `vendor/magento/module-config/etc/system.xml` — Magento core configuration definition.

**Official documentation**: [System Configuration]

### 3.4 Read configuration in code

```php
// In a Block, Helper, Model...
$isEnabled = $this->scopeConfig->isSetFlag('vendor_module/general/enabled');
$postsPerPage = $this->scopeConfig->getValue('vendor_module/general/posts_per_page');

// With constructor injection (recommended)
public function __construct(
    private readonly ScopeConfigInterface $scopeConfig
) {}

$isEnabled = $this->scopeConfig->isSetFlag('vendor_module/general/enabled');
```

**Source**: `vendor/magento/module-config/Model/Config.php` — Magento core reads configuration using `ScopeConfigInterface`.

### 3.5 Configuration scopes

| Scope | Level | Example |
|-------|--------|---------|
| **Default** | Global | All websites |
| **Website** | Per website | UK site vs FR site |
| **Store View** | Per language | English vs French |

In `system.xml`:
- `showInDefault="1"`: visible at global level
- `showInWebsite="1"`: visible per website
- `showInStore="1"`: visible per store view

---

## 4. Admin listings (UI Components)

### 4.1 Admin listing structure

```
Vendor/Module/
├── Controller/Adminhtml/Post/
│   ├── Index.php          ← Controller: displays the grid
│   ├── Edit.php           ← Controller: displays the form
│   ├── Save.php           ← Controller: save
│   └── Delete.php         ← Controller: delete
├── Ui/
│   ├── DataProvider/
│   │   ├── PostListingDataProvider.php  ← Grid data
│   │   └── PostFormDataProvider.php     ← Form data
│   └── Component/Listing/Column/
│       └── Actions.php     ← Actions column (Edit/Delete)
└── view/adminhtml/
    ├── layout/
    │   ├── vendor_module_post_index.xml  ← Listing layout
    │   └── vendor_module_post_edit.xml   ← Form layout
    └── ui_component/
        ├── vendor_module_post_listing.xml ← Grid UI Component
        └── vendor_module_post_form.xml     ← Form UI Component
```

**Source**: `vendor/magento/module-catalog/view/adminhtml/ui_component/product_listing.xml` — Magento core uses the same pattern for product listings.

### 4.2 Listing UI Component example

```xml
<!-- view/adminhtml/ui_component/vendor_module_post_listing.xml -->
<listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
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

**Source**: `vendor/magento/module-catalog/view/adminhtml/ui_component/product_listing.xml` — Magento core product listing uses the same UI Component structure.

**Official documentation**: [UI Components Listing](ui-component-listing/)

### 4.3 The DataProvider

```php
// Ui/DataProvider/PostListingDataProvider.php
namespace Vendor\Module\Ui\DataProvider;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Vendor\Module\Model\ResourceModel\Post\CollectionFactory;

class PostListingDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        private readonly CollectionFactory $collectionFactory,
        array $meta = ,
        array $data = 
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $this->collectionFactory->create;
    }
    
    public function getData: array
    {
        return [
            'items' => $this->collection->getItems,
            'totalRecords' => $this->collection->getSize
        ];
    }
}
```

**Source**: `vendor/magento/module-catalog/Ui/DataProvider/Product/Listing/DataProvider.php` — Magento core uses the same DataProvider pattern.

---

## 5. Admin forms (UI Components)

### 5.1 Form structure

```xml
<!-- view/adminhtml/ui_component/vendor_module_post_form.xml -->
<form xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Ui:etc/ui_configuration.xsd">
    <dataSource name="post_form_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                Vendor\Module\Ui\DataProvider\PostFormDataProvider
            </argument>
            <argument name="name" xsi:type="string">post_form_data_source</argument>
            <argument name="primaryFieldName" xsi:type="string">entity_id</argument>
            <argument name="requestFieldName" xsi:type="string">id</argument>
        </argument>
    </dataSource>
    
    <fieldset name="general">
        <field name="title">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string">Title</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">input</item>
                    <item name="sortOrder" xsi:type="number">10</item>
                </item>
            </argument>
        </field>
        <field name="content">
            <argument name="data" xsi:type="array">
                <item name="config" xsi:type="array">
                    <item name="label" xsi:type="string">Content</item>
                    <item name="dataType" xsi:type="string">text</item>
                    <item name="formElement" xsi:type="string">textarea</item>
                    <item name="sortOrder" xsi:type="number">20</item>
                </item>
            </argument>
        </field>
    </fieldset>
</form>
```

**Source**: `vendor/magento/module-catalog/view/adminhtml/ui_component/product_form.xml` — Magento core product form uses the same UI Component structure.

**Official documentation**: [UI Components Form](ui-component-form/)

### 5.2 Form buttons

In the `_edit.xml` layout:

```xml
<referenceContainer name="content">
    <block class="Vendor\Module\Block\Adminhtml\Post\Edit\GenericButton" name="edit_form"/>
</referenceContainer>
```

Buttons are defined via `ButtonProviderInterface`:

```php
// Block/Adminhtml/Post/Edit/GenericButton.php
namespace Vendor\Module\Block\Adminhtml\Post\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class GenericButton implements ButtonProviderInterface
{
    public function getButtonData: array
    {
        return [
            'back' => [
                'label' => __('Back'),
                'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/')),
                'class' => 'back',
                'sort_order' => 10
            ],
            'delete' => [
                'label' => __('Delete'),
                'on_click' => 'deleteConfirm("Are you sure?")',
                'class' => 'delete',
                'sort_order' => 20
            ],
            'save' => [
                'label' => __('Save'),
                'on_click' => 'saveAndContinueEdit',
                'class' => 'save primary',
                'sort_order' => 90
            ]
        ];
    }
}
```

**Source**: `vendor/magento/module-backend/Block/Widget/Button.php` — Magento core button widget implements the same interface.

---

## 6. Create a new admin entry

### 6.1 Steps

1. **Create the ACL** (`etc/acl.xml`)
2. **Create the menu** (`etc/adminhtml/menu.xml`)
3. **Create the routes** (`etc/adminhtml/routes.xml`)
4. **Create the Controllers** (`Controller/Adminhtml/...`)
5. **Create the layouts** (`view/adminhtml/layout/...`)
6. **Create the UI Components** (`view/adminhtml/ui_component/...`)
7. **Create the DataProviders** (`Ui/DataProvider/...`)

### 6.2 Example: routes.xml

```xml
<!-- etc/adminhtml/routes.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:App/etc/routes.xsd">
    <router id="admin">
        <route id="vendor_module" frontName="vendor_module">
            <module name="Vendor_Module" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

The admin URL will be: `/admin/vendor_module/post/index`

**Source**: `vendor/magento/module-catalog/etc/adminhtml/routes.xml` — Magento core admin routes follow the same pattern.

### 6.3 Example: Controller

```php
// Controller/Adminhtml/Post/Index.php
namespace Vendor\Module\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Vendor_Module::post';
    
    public function execute: \Magento\Backend\Model\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create;
        $resultPage->setActiveMenu('Vendor_Module::post');
        $resultPage->getConfig->getTitle->prepend(__('Blog Posts'));
        return $resultPage;
    }
}
```

**Source**: `vendor/magento/module-catalog/Controller/Adminhtml/Product/Index.php` — Magento core product controller follows the same pattern.

### 6.4 Admin blocks with collection reuse

Magento core reuses collections in admin blocks. For example, the product grid
reuses the product collection without custom filtering:

```php
// Block/Adminhtml/Product/Grid.php
$collection = $this->collectionFactory->create;
// Add filters, sorting, pagination
```

**Source**: `vendor/magento/module-catalog/Block/Adminhtml/Product/Grid.php`

---

## 7. Summary

| Concept | Role |
|---------|------|
| ACL | Controls permissions |
| Menu | Entry in the admin sidebar |
| Routes | Admin URLs |
| Controller | Admin logic |
| Admin layout | Admin page structure |
| UI Component | Admin grid |
| DataProvider | Grid/form data |
| system.xml | Configuration in Stores > Configuration |

---

## 8. AlpineCommerce reference

The following sections show how AlpineCommerce implements admin features
in its custom modules. These are **project-specific implementations**
built on top of Magento 2 Core admin mechanisms.

### 8.1 Modules with admin interface

| Module | Menu | ACL | Listing | Form |
|--------|------|-----|---------|------|
| Blog | Content > Blog | `AlpineCommerce_Blog::post`, `::category` | ✅ | ✅ |
| Faq | Content > FAQ | `AlpineCommerce_Faq::main` | ✅ | ✅ |
| LegalPages | Content > Legal Pages | `AlpineCommerce_LegalPages::main` | ✅ | ✅ |
| ProductReviews | Marketing > Product Reviews | `AlpineCommerce_ProductReviews::main` | ✅ | ✅ |
| ProductQuestions | Marketing > Product Questions | `AlpineCommerce_ProductQuestions::main` | ✅ | ✅ |
| ProductLabels | Catalog > Product Labels | `AlpineCommerce_ProductLabels::main` | ✅ | ✅ |
| Gdpr | GDPR > Consent Log | `AlpineCommerce_Gdpr::consent_log`, `::export` | ✅ | ❌ |
| StorePickup | Content > Store Pickup | `AlpineCommerce_StorePickup::main` | ✅ | ✅ |
| StoreLocator | Content > Store Locator | `AlpineCommerce_StoreLocator::main` | ✅ | ✅ |
| CustomerCare | Customers > Customer Care | `AlpineCommerce_CustomerCare::manage` | ✅ | ✅ |
| CustomerGrid | (none — native override) | (none — uses native ACL) | ✅ | ❌ |

### 8.2 Modules without admin interface

| Module | Role | Admin |
|--------|------|-------|
| StoreSetup | Configuration + observers | System.xml only |
| LoyaltyProgram | Total collector + minicart | System.xml only |
| EuVat | Validation + CLI | System.xml only |
| Hreflang | SEO tags | System.xml only |
| AutoInvoice | Auto-invoicing | System.xml only |
| CreditMemo | Auto credit memo | System.xml only |
| PartialInvoice | Auto partial invoice | System.xml only |
| Rma | Return management | System.xml only |

### 8.3 AlpineCommerce admin patterns

- **Blog, Faq, LegalPages**: CRUD with UI Component listing + form
- **ProductReviews, ProductQuestions**: Marketing-section listings
- **StorePickup, StoreLocator**: Content-section store management
- **Gdpr**: Custom DataProvider (`AbstractDataProvider`) for consent log
- **CustomerGrid**: Native customer grid override (no custom ACL)
- **Rma**: Full admin workflow (approve, reject, refund, close)

**Sources**:
- `src/app/code/AlpineCommerce/Blog/`
- `src/app/code/AlpineCommerce/Faq/`
- `src/app/code/AlpineCommerce/LegalPages/`
- `src/app/code/AlpineCommerce/ProductReviews/`
- `src/app/code/AlpineCommerce/ProductQuestions/`
- `src/app/code/AlpineCommerce/ProductLabels/`
- `src/app/code/AlpineCommerce/Gdpr/`
- `src/app/code/AlpineCommerce/StorePickup/`
- `src/app/code/AlpineCommerce/StoreLocator/`
- `src/app/code/AlpineCommerce/CustomerCare/`
- `src/app/code/AlpineCommerce/CustomerGrid/`
- `src/app/code/AlpineCommerce/Rma/`

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| Admin Panel | [developer.adobe.com/commerce/admin/](https://developer.adobe.com/commerce/admin/) |
| ACL | developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/acl/ |
| Admin Menu | developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/menu/ |
| System Configuration | developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/system-configuration/ |
| UI Components | developer.adobe.com/commerce/php/tutorials/ui-components/ |
| Admin Routes | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/routes/](https://developer.adobe.com/commerce/php/development/components/routing/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

AlpineCommerce-specific implementations are referenced from `src/app/code/AlpineCommerce/`.

*Last updated: 2026-09-07*
