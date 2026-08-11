# Magento 2 — Templates (PHTML) & Layout XML

> **Objective**: learn to read and write Magento template and layout files.
> These are the files that control **what is displayed** in the browser and **where** it is displayed.

---

## 1. The Layout concept

### 1.1 What is Layout?

The **Layout** is the skeleton of a Magento page. It answers two questions:
- Which **blocks** (PHP) must be created?
- **Where** to place them on the page?

The Layout is defined in **XML**. Each page has its own XML file.

### 1.2 The structure of a Magento page

```
HTML Page
├── <html>
├── <head>               ← title, CSS, JS (managed automatically by Magento)
└── <body>
    ├── page.top         ← header, logo, menu
    │   └── header        ← Magento block
    ├── content           ← main content (varies by page)
    │   └── blog.post.list ← custom block (AlpineCommerce Blog)
    ├── sidebar.main      ← left column (filters, categories)
    ├── sidebar.additional ← right column (widgets)
    └── page.bottom       ← footer
```

The names `page.top`, `content`, `sidebar.main` are **containers**
defined by Magento. Modules add **blocks** into these containers.

### 1.3 Containers vs Blocks

| Concept | Role | Example |
|---------|------|---------|
| **Container** | Empty location (like an empty panel) | `content`, `page.top` |
| **Block** | Concrete element (PHP class + template) | `blog.post.list`, `store.info` |

---

## 2. Anatomy of a Layout XML file

### 2.1 Minimal example

```xml
<!-- view/frontend/layout/blog_index_index.xml -->
<page xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\PostList"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::post/list.phtml"
                   before="-"/>
        </referenceContainer>
    </body>
</page>
```

### 2.2 Line-by-line explanation

```xml
<page>                                          ← Root: an entire page
    <body>                                       ← Page body
        <referenceContainer name="content">      ← Target: the "content" container
            <block                                ← Adds a new block
                class="AlpineCommerce\Blog\Block\PostList"   ← PHP class
                name="blog.post.list"                             ← Unique identifier
                template="AlpineCommerce_Blog::post/list.phtml"   ← .phtml template
                before="-"/>                                      ← Position: before everything
        </referenceContainer>
    </body>
</page>
```

### 2.3 `<block>` attributes

| Attribute | Mandatory | Role | Example |
|-----------|-----------|------|---------|
| `class` | Yes | PHP class that provides data | `AlpineCommerce\Blog\Block\PostList` |
| `name` | Yes | Unique identifier in the page | `blog.post.list` |
| `template` | No | Path to the `.phtml` file | `AlpineCommerce_Blog::post/list.phtml` |
| `before` | No | Position BEFORE another block | `before="-"` (first) |
| `after` | No | Position AFTER another block | `after="page.bottom"` |
| `ifConfig` | No | Display if a config is enabled | `ifConfig="blog/general/enabled"` |
| `if` | No | Display according to an expression | `if="1 == 1"` |

---

## 3. Layout files in Magento

### 3.1 Where are layout files located?

```
Module/
├── view/
│   ├── frontend/
│   │   └── layout/                    ← Frontend layouts
│   │       ├── default.xml            ← Applied to ALL frontend pages
│   │       ├── catalog_product_view.xml ← Product page
│   │       ├── catalog_category_view.xml ← Category page
│   │       └── blog_index_index.xml   ← /blog page (route: blog/index/index)
│   └── adminhtml/
│       └── layout/                    ← Admin layouts
│           ├── adminhtml_dashboard_index.xml ← Admin dashboard
│           └── alphacommerce_blog_post_index.xml ← Admin Blog listing
```

### 3.2 How Magento finds the right layout file

Magento builds the filename from the URL:

| URL | Route | Layout file |
|-----|-------|-------------|
| `/blog` | `blog/index/index` | `blog_index_index.xml` |
| `/blog/post/view/id/1` | `blog/post/view` | `blog_post_view.xml` |
| `/catalog/product/view/id/1` | `catalog/product/view` | `catalog_product_view.xml` |
| `/admin/blog/post/index` | `adminhtml/blog/post/index` | `adminhtml_blog_post_index.xml` |

**Rule**: `{frontName}_{controller}_{action}.xml`

### 3.3 Layout fallback (cascade)

Magento applies multiple layout files in a specific order:

```
1. default.xml                  (all pages)
2. {module}_default.xml        (e.g. blog_default.xml)
3. {area}_default.xml          (e.g. frontend_default.xml)
4. {full_action_name}.xml      (e.g. blog_index_index.xml)
```

Files are **merged**: what is declared in `blog_index_index.xml`
is added to what is in `default.xml`.

---

## 4. Essential XML instructions

### 4.1 `<referenceContainer>` and `<referenceBlock>`

To add content into an existing container or block:

```xml
<!-- Add a block in the "content" container -->
<referenceContainer name="content">
    <block class="AlpineCommerce\Blog\Block\PostList"
           name="blog.post.list"
           template="AlpineCommerce_Blog::post/list.phtml"/>
</referenceContainer>

<!-- Add a block AFTER the "page.main.title" block -->
<referenceBlock name="page.main.title">
    <block class="AlpineCommerce\Blog\Block\Breadcrumbs"
           name="blog.breadcrumbs"
           template="AlpineCommerce_Blog::breadcrumbs.phtml"
           after="-"/>
</referenceBlock>
```

**Difference**:
- `<referenceContainer>`: for containers (`content`, `page.top`, etc.)
- `<referenceBlock>`: for existing blocks (`page.main.title`, `product.info.main`, etc.)

### 4.2 Standalone `<block>`

To create a block without reference (fully custom page):

```xml
<page>
    <body>
        <block class="Magento\Framework\View\Element\Template"
               name="my.custom.page"
               template="AlpineCommerce_Blog::custom/page.phtml"/>
    </body>
</page>
```

### 4.3 `<container>`

To create a new container (rare, reserved for advanced cases):

```xml
<referenceContainer name="content">
    <container name="blog.container" label="Blog Container" htmlTag="div" htmlClass="blog-container">
        <block class="AlpineCommerce\Blog\Block\PostList"
               name="blog.post.list"
               template="AlpineCommerce_Blog::post/list.phtml"/>
    </container>
</referenceContainer>
```

### 4.4 `<move>`

To move an existing block:

```xml
<!-- Move the "product.info.main" block into "sidebar.main" -->
<move element="product.info.main" destination="sidebar.main" before="-"/>
```

### 4.5 `<remove>`

To remove a block:

```xml
<!-- Remove the "breadcrumbs" block from this page -->
<referenceBlock name="breadcrumbs" remove="true"/>
```

---

## 5. Block arguments

### 5.1 Simple arguments

```xml
<block class="AlpineCommerce\Blog\Block\PostList"
       name="blog.post.list">
    <arguments>
        <argument name="page_size" xsi:type="number">10</argument>
        <argument name="show_date" xsi:type="boolean">true</argument>
        <argument name="title" xsi:type="string">Latest Posts</argument>
    </arguments>
</block>
```

In the PHP Block:
```php
public function getPageSize(): int
{
    return (int) $this->getData('page_size'); // 10
}
```

### 5.2 Complex arguments

```xml
<arguments>
    <argument name="js_config" xsi:type="array">
        <item name="component" xsi:type="string">alphacommerceStorePickup</item>
    </argument>
    <argument name="data" xsi:type="array">
        <item name="availableStores" xsi:type="object">
            AlpineCommerce\StorePickup\Block\Adminhtml\Store\Source\StoreInfo
        </item>
    </argument>
</arguments>
```

### 5.3 The `data` argument (data model)

When a block receives a `data` argument, Magento merges it into the
block model. The keys become accessible via `$block->getData()`:

```xml
<block class="..." name="...">
    <arguments>
        <argument name="data" xsi:type="array">
            <item name="available_stores" xsi:type="object">StoreInfo</item>
            <item name="carrier_code" xsi:type="string">storepickup</item>
        </item>
    </arguments>
</block>
```

```php
// In the .phtml template:
$availableStores = $block->getAvailableStores(); // StoreInfo object
$carrierCode = $block->getCarrierCode(); // 'storepickup'
```

---

## 6. PHTML files (templates)

### 6.1 What is a PHTML?

A **PHTML** file = **P**HP + **H**TML. It is the file that generates
the final HTML. It contains:
- HTML
- PHP to display variables
- Calls to the Block (`$block->getSomething()`)
- PHP loops and conditions

### 6.2 Template path

```xml
template="AlpineCommerce_Blog::post/list.phtml"
```

Decomposes into:
```
AlpineCommerce_Blog  ← Module (Vendor_Module)
::                   ← Separator
post/list.phtml      ← Path in view/frontend/templates/
```

**Full path on disk**:
```
src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml
```

### 6.3 PHTML example

```php
<?php /** @var $block AlpineCommerce\Blog\Block\PostList */ ?>
<?php /** @var $posts AlpineCommerce\Blog\Model\Post[] */ ?>

<div class="blog-post-list">
    <?php if ($posts = $block->getPosts()): ?>
        <?php foreach ($posts as $post): ?>
            <article class="blog-post-item">
                <h2>
                    <a href="<?= $block->escapeUrl($post->getUrl()) ?>">
                        <?= $block->escapeHtml($post->getTitle()) ?>
                    </a>
                </h2>
                
                <div class="post-content">
                    <?= $block->escapeHtml($post->getContent()) ?>
                </div>
                
                <div class="post-meta">
                    <span><?= $block->formatDate($post->getCreatedAt()) ?></span>
                </div>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-posts"><?= $block->escapeHtml(__('No posts found.')) ?></p>
    <?php endif; ?>
</div>
```

### 6.4 Essential methods in PHTML

| Method | Role | Example |
|---------|------|---------|
| `$block->getData('key')` | Read a layout argument | `$block->getData('page_size')` |
| `$block->getUrl('route/path')` | Generate a URL | `$block->getUrl('blog/index/view')` |
| `$block->escapeHtml($str)` | Escape HTML (security) | `$block->escapeHtml($title)` |
| `$block->escapeUrl($url)` | Escape a URL | `$block->escapeUrl($post->getUrl())` |
| `$block->formatDate($date)` | Format a date | `$block->formatDate($post->getCreatedAt())` |
| `$block->formatPrice($amount)` | Format a price | `$block->formatPrice(29.99)` |
| `__('string')` | Translate | `__('No posts found')` |

### 6.5 Shortcuts in PHTML

```php
<?= /* equivalent to <?php echo */ ?>
<?php /* ... */ ?>       ← PHP comment
<?= $block->... ?>       ← Block call
```

### 6.6 Security: always escape

```php
<!-- ❌ DANGEROUS: XSS possible -->
<p><?= $post->getTitle() ?></p>

<!-- ✅ SAFE: escaped -->
<p><?= $block->escapeHtml($post->getTitle()) ?></p>
```

**Golden rule**: everything coming from the database or the user
must be escaped before being displayed.

---

## 7. Template fallback system

### 7.1 Search order

When Magento looks for a template `AlpineCommerce_Blog::post/list.phtml`:

```
1. Active theme:
    src/app/design/frontend/AlpineCommerce/theme/AlpineCommerce/Blog/templates/post/list.phtml

2. Parent module:
    src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml

3. Magento module (fallback):
    src/app/code/Magento/Theme/view/frontend/templates/html/header.phtml
```

### 7.2 Override a template in the theme

To modify a template **without touching the module**, copy it to the theme:

```bash
# Original (module)
cp src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml \
   src/app/design/frontend/AlpineCommerce/theme/AlpineCommerce/Blog/templates/post/list.phtml

# Then modify the copy in the theme
```

Magento will automatically use the theme version.

---

## 8. AlpineCommerce concrete examples

### 8.1 Blog Layout + Template

**Layout** (`view/frontend/layout/blog_index_index.xml`):
```xml
<page xmlns:xsi="..." layout="1column">
    <body>
        <referenceContainer name="content">
            <block class="AlpineCommerce\Blog\Block\PostList"
                   name="blog.post.list"
                   template="AlpineCommerce_Blog::post/list.phtml"/>
        </referenceContainer>
    </body>
</page>
```

**PHP Block** (`Block/PostList.php`):
```php
class PostList extends Template
{
    private PostRepositoryInterface $postRepository;
    
    public function getPosts(): array
    {
        return $this->postRepository->getList($searchCriteria)->getItems();
    }
}
```

**Template** (`templates/post/list.phtml`):
```php
<?php /** @var $block AlpineCommerce\Blog\Block\PostList */ ?>
<?php $posts = $block->getPosts(); ?>

<div class="blog-posts">
    <?php foreach ($posts as $post): ?>
        <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
        <p><?= $block->escapeHtml($post->getContent()) ?></p>
    <?php endforeach; ?>
</div>
```

### 8.2 Admin Layout + Template

**Layout** (`view/adminhtml/layout/alphacommerce_blog_post_index.xml`):
```xml
<page xmlns:xsi="...">
    <body>
        <referenceContainer name="content">
            <uiComponent name="alphacommerce_blog_post_listing"/>
        </referenceContainer>
    </body>
</page>
```

Here, no classic `.phtml`: it is a **UI Component** (admin grid)
defined in XML (`ui_component/alphacommerce_blog_post_listing.xml`).

### 8.3 Layout with arguments

**Layout** (`view/frontend/layout/checkout_index_index.xml`):
```xml
<referenceContainer name="checkout.cart.totals">
    <block class="Magento\Checkout\Block\Cart\Totals"
           name="store.pickup"
           template="AlpineCommerce_StorePickup::store-pickup.phtml">
        <arguments>
            <argument name="js_config" xsi:type="array">
                <item name="component" xsi:type="string">alphacommerceStorePickup</item>
            </argument>
        </arguments>
    </block>
</referenceContainer>
```

---

## 9. Advanced XML instructions

### 9.1 `<update>` — include another layout

```xml
<!-- In catalog_product_view.xml, include the entire default.xml layout -->
<update handle="default"/>
```

### 9.2 `<reference name="head">` — add CSS/JS

```xml
<page>
    <head>
        <css src="AlpineCommerce_Blog::css/blog.css"/>
        <js src="AlpineCommerce_Blog::js/blog.js"/>
        <link src="https://fonts.googleapis.com/css?family=Roboto" src_type="url"/>
    </head>
</page>
```

### 9.3 `<block>` with `t:type`

To use a VirtualType (defined in `di.xml`):

```xml
<block class="Magento\Framework\View\Element\Template"
       name="my.block"
       template="...::template.phtml">
    <arguments>
        <argument name="data" xsi:type="object">myVirtualType</argument>
    </arguments>
</block>
```

---

## 10. Correspondence table

| Layout element | PHP element | HTML element |
|----------------|-------------|--------------|
| `<page>` | — | `<html>`, `<head>`, `<body>` |
| `<referenceContainer name="content">` | `content` container | `<div class="columns">` |
| `<block class="...">` | PHP Block class | `<div>` generated by the template |
| `template="Vendor::path/template.phtml"` | PHP Block | HTML content of the block |
| `<arguments>` | `$block->getData()` | Variables in the template |

---

## 11. Common errors

### 11.1 "Template file not found"

**Cause**: incorrect template path.

**Check**:
```xml
template="AlpineCommerce_Blog::post/list.phtml"
```

Must correspond to:
```
src/app/code/AlpineCommerce/Blog/view/frontend/templates/post/list.phtml
```

### 11.2 Block not displaying

**Possible causes**:
- The block `name` is duplicated (conflict)
- The layout XML is not loaded (wrong filename)
- `before`/`after` places the block outside the visible area
- The block is removed by another layout (`remove="true"`)

### 11.3 Empty variable in template

**Cause**: the argument is not passed correctly.

**Check**:
```xml
<!-- Layout -->
<argument name="my_var" xsi:type="string">value</argument>

<!-- PHP Block -->
public function getMyVar(): string
{
    return $this->getData('my_var'); // 'value'
}

<!-- Template -->
<?= $block->escapeHtml($block->getMyVar()) ?>
```

---

## 12. Summary

| Question | Answer |
|----------|---------|
| **What is a layout XML?** | The page skeleton: which blocks to display and where |
| **Where to put them?** | `view/frontend/layout/` or `view/adminhtml/layout/` |
| **How to name the file?** | `{frontName}_{controller}_{action}.xml` |
| **What is a container?** | An empty location (`content`, `page.top`) |
| **What is a block?** | A concrete element (PHP class + template) |
| **How to add a block?** | `<referenceContainer name="content"><block .../></referenceContainer>` |
| **What is a PHTML?** | A PHP file that generates HTML |
| **Where to find templates?** | `view/frontend/templates/` or `view/adminhtml/templates/` |
| **How to call a block from the template?** | `$block->getPosts()`, `$block->getUrl()`, etc. |
| **How to secure display?** | `$block->escapeHtml()`, `$block->escapeUrl()` |

---

*Last updated: 2026-08-11.*
