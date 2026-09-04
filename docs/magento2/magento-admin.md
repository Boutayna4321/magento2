# Magento 2 — Admin Basics

> **Objective**: understand the Magento administration panel:
> navigation, ACL, system configuration, menus, and how AlpineCommerce
> extends the admin with its own modules.

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

---

## 2. Key admin concepts

### 2.1 ACL (Access Control List)

**ACL** controls what each admin user can do.

**ACL structure**:

```xml
<!-- etc/acl.xml -->
<acl xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
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

**Explanation**:
- `AlpineCommerce_Blog::main`: parent resource (appears in the menu)
- `AlpineCommerce_Blog::post`: child resource (permission for posts)
- The user must have the `AlpineCommerce_Blog::post` permission to
  access posts

### 2.2 Admin menu

```xml
<!-- etc/adminhtml/menu.xml -->
<menu xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <add id="AlpineCommerce_Blog::main"
         title="Blog"
         module="AlpineCommerce_Blog"
         sortOrder="100"
         parent="Magento_Backend::content"
         resource="AlpineCommerce_Blog::main"/>
</menu>
```

**Attributes**:
- `id`: must match the ACL
- `title`: text displayed in the menu
- `parent`: where to place the item (`Magento_Backend::content` = main menu)
- `resource`: required ACL resource
- `sortOrder`: position (smaller = higher)

### 2.3 Route protection

Each admin Controller must check the ACL:

```php
// Controller/Adminhtml/Post/Index.php
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute(): void
    {
        // If the user does not have permission, Magento automatically displays 403
        // ...
    }
}
```

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

### 3.3 system.xml — Define your own config

```xml
<!-- etc/adminhtml/system.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <system>
        <section id="alphacommerce_blog" translate="label" type="text" sortOrder="100" showInDefault="1" showInWebsite="1" showInStore="1">
            <label>Blog</label>
            <tab>general</tab>
            <resource>AlpineCommerce_Blog::config</resource>
            
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
- `<section>`: a section in the config (`AlpineCommerce_Blog`)
- `<group>`: a group in the section (`General Configuration`)
- `<field>`: a configuration field

### 3.4 Read configuration in code

```php
// In a Block, Helper, Model...
$isEnabled = $this->scopeConfig->isSetFlag('alphacommerce_blog/general/enabled');
$postsPerPage = $this->scopeConfig->getValue('alphacommerce_blog/general/posts_per_page');

// With the helper (recommended)
$helper = \Magento\Framework\App\Config\ScopeConfigInterface::class;
$isEnabled = $helper->isSetFlag('alphacommerce_blog/general/enabled');
```

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
AlpineCommerce/Blog/
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
├── view/adminhtml/
│   ├── layout/
│   │   ├── alphacommerce_blog_post_index.xml  ← Listing layout
│   │   └── alphacommerce_blog_post_edit.xml   ← Form layout
│   └── ui_component/
│       ├── alphacommerce_blog_post_listing.xml ← Grid UI Component
│       └── alphacommerce_blog_post_form.xml     ← Form UI Component
```

### 4.2 Listing UI Component example

```xml
<!-- view/adminhtml/ui_component/alphacommerce_blog_post_listing.xml -->
<listing xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <dataSource name="post_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                AlpineCommerce\Blog\Ui\DataProvider\PostListingDataProvider
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
                    <item name="urlPath" xsi:type="string">blog/post/edit</item>
                    <item name="paramName" xsi:type="string">id</item>
                </item>
            </argument>
        </actions>
    </columns>
</listing>
```

### 4.3 The DataProvider

```php
// Ui/DataProvider/PostListingDataProvider.php
class PostListingDataProvider extends AbstractDataProvider
{
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        PostRepositoryInterface $postRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->postRepository = $postRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collection = $postRepository->getList($searchCriteriaBuilder->create())->getItems();
    }
    
    public function getData(): array
    {
        return [
            'items' => $this->collection,
            'totalRecords' => count($this->collection)
        ];
    }
}
```

---

## 5. Admin forms (UI Components)

### 5.1 Form structure

```xml
<!-- view/adminhtml/ui_component/alphacommerce_blog_post_form.xml -->
<form xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <dataSource name="post_form_data_source">
        <argument name="dataProvider" xsi:type="configurableObject">
            <argument name="class" xsi:type="string">
                AlpineCommerce\Blog\Ui\DataProvider\PostFormDataProvider
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

### 5.2 Form buttons

In the `_edit.xml` layout:

```xml
<referenceContainer name="content">
    <block class="AlpineCommerce\Blog\Block\Adminhtml\Post\Edit\GenericButton" name="edit_form"/>
</referenceContainer>
```

Buttons are defined via `ButtonProviderInterface`:

```php
// Block/Adminhtml/Post/Edit/GenericButton.php
class GenericButton implements ButtonProviderInterface
{
    public function getButtonData(): array
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
                'on_click' => 'saveAndContinueEdit()',
                'class' => 'save primary',
                'sort_order' => 90
            ]
        ];
    }
}
```

---

## 6. AlpineCommerce modules in the admin

### 6.1 Modules with admin interface

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

### 6.2 Modules without admin interface

| Module | Role | Admin |
|--------|------|-------|
| StoreSetup | Configuration + observers | System.xml only |
| LoyaltyProgram | Total collector + minicart | System.xml only |
| EuVat | Validation + CLI | System.xml only |
| Hreflang | SEO tags | System.xml only |

---

## 7. Create a new admin entry

### 7.1 Steps

1. **Create the ACL** (`etc/acl.xml`)
2. **Create the menu** (`etc/adminhtml/menu.xml`)
3. **Create the routes** (`etc/adminhtml/routes.xml`)
4. **Create the Controllers** (`Controller/Adminhtml/...`)
5. **Create the layouts** (`view/adminhtml/layout/...`)
6. **Create the UI Components** (`view/adminhtml/ui_component/...`)
7. **Create the DataProviders** (`Ui/DataProvider/...`)

### 7.2 Example: routes.xml

```xml
<!-- etc/adminhtml/routes.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <router id="admin">
        <route id="alphacommerce_blog" frontName="alphacommerce_blog">
            <module name="AlpineCommerce_Blog" before="Magento_Backend"/>
        </route>
    </router>
</config>
```

The admin URL will be: `/admin/alphacommerce_blog/post/index`

### 7.3 Example: Controller

```php
// Controller/Adminhtml/Post/Index.php
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute(): \Magento\Backend\Model\View\Result\Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('AlpineCommerce_Blog::post');
        $resultPage->getConfig()->getTitle()->prepend(__('Blog Posts'));
        return $resultPage;
    }
}
```

---

## 8. Summary

| Concept | Role | AlpineCommerce example |
|---------|------|------------------------|
| ACL | Controls permissions | `etc/acl.xml` |
| Menu | Entry in the admin sidebar | `etc/adminhtml/menu.xml` |
| Routes | Admin URLs | `etc/adminhtml/routes.xml` |
| Controller | Admin logic | `Controller/Adminhtml/Post/Index.php` |
| Admin layout | Admin page structure | `view/adminhtml/layout/` |
| UI Component | Admin grid | `view/adminhtml/ui_component/listing.xml` |
| DataProvider | Grid/form data | `Ui/DataProvider/` |
| system.xml | Configuration in Stores > Configuration | `etc/adminhtml/system.xml` |

---

*Last updated: 2026-08-11.*
