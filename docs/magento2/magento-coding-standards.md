# Magento 2 — Coding Standards

> **Objective**: know the mandatory coding rules in Magento 2
> and in the AlpineCommerce project. These standards ensure that the code
> is readable, maintainable, and compatible with Magento tools.
>
> **Scope of this document**:
>
> - **Magento Core Standards** (Sections 2–6, 8): mandatory conventions
>   for all Magento 2 / Adobe Commerce development.
> - **AlpineCommerce Project Standards** (Section 9): team preferences
>   and workflows specific to this project.
>
> AlpineCommerce examples are used throughout for illustration, but the
> underlying standards are Magento requirements unless explicitly marked
> as "Team Convention" or "Project-Specific."

---

## Table of Contents

1. [Why standards?](#1-why-standards)
2. [PHP — PSR-12 + Magento](#2-php--psr-12--magento)
3. [XML — Magento conventions](#3-xml--magento-conventions)
4. [JavaScript — Magento conventions](#4-javascript--magento-conventions)
5. [PHTML — Conventions](#5-phtml--conventions)
6. [Module — Canonical structure](#6-module--canonical-structure)
7. [AlpineCommerce — Git Commit Conventions](#7-alpinecommerce--git-commit-conventions)
8. [Validation tools](#8-validation-tools)
9. [Pre-commit checklist](#9-pre-commit-checklist)
10. [Summary](#10-summary)
11. [Official Documentation & Sources](#11-official-documentation--sources)

---

## 1. Why standards?

| Without standards | With standards |
|-------------------|----------------|
| Every developer writes differently | Everyone writes the same way |
| Difficult to read others' code | Homogeneous, readable code |
| Validation tools fail | Tools pass on the first try |
| Non-portable code | Portable code between projects |

---

## 2. PHP — PSR-12 + Magento

### 2.1 PSR-12: the base

Magento 2 follows **PSR-12** (PHP-FIG standard). Essential rules:

**Indentation**: 4 spaces (no tabs)
```php
// Correct
class Post
{
    public function getTitle(): string
    {
        return $this->title;
    }
}

// Wrong
class Post {
public function getTitle(): string {
return $this->title;
}}
```

**Braces**: K&R style (opening brace on the same line)
```php
// Correct
if ($condition) {
    // ...
}

// Wrong
if ($condition)
{
    // ...
}
```

**Spaces**:
```php
// Correct
$sum = $a + $b;
$array = ['key' => 'value'];

// Wrong
$sum=$a+$b;
$array=['key'=>'value'];
```

### 2.2 `declare(strict_types=1)` — MANDATORY

Every Magento PHP file must start with:

```php
<?php
declare(strict_types=1);

namespace Vendor\Module\Model;

use Magento\Framework\Model\AbstractModel;

class Post extends AbstractModel
{
    // ...
}
```

**Effect**:
- Function arguments are strictly typed (no automatic cast)
- `int $id` refuses a string `'123'` → `TypeError`
- Makes the code more reliable

**Source**: All Magento 2 core PHP files begin with `declare(strict_types=1)`.

### 2.3 Namespaces and PSR-4

The namespace corresponds to the folder structure:

```
File: app/code/Vendor/Module/Model/Post.php
Namespace: Vendor\Module\Model
```

### 2.4 Classes: naming conventions

| Type | Convention | Example |
|------|-----------|---------|
| **Entity Model** | `Model/{Entity}.php` | `Model/Post.php` |
| **ResourceModel** | `Model/ResourceModel/{Entity}.php` | `Model/ResourceModel/Post.php` |
| **Collection** | `Model/ResourceModel/{Entity}/Collection.php` | `Model/ResourceModel/Post/Collection.php` |
| **Repository** | `Model/{Entity}Repository.php` | `Model/PostRepository.php` |
| **Interface** | `Api/Data/{Entity}Interface.php` | `Api/Data/PostInterface.php` |
| **Repository Interface** | `Api/{Entity}RepositoryInterface.php` | `Api/PostRepositoryInterface.php` |
| **Block** | `Block/{Name}.php` | `Block/PostList.php` |
| **Controller** | `Controller/{Area}/{Controller}/{Action}.php` | `Controller/Index/Index.php` |
| **Plugin** | `Plugin/{Entity}/{Method}.php` | `Plugin/Post/Slugify.php` |
| **Observer** | `Observer/{Event}.php` | `Observer/SavePostAfter.php` |
| **Service** | `Service/{Name}.php` | `Service/Config.php` |
| **Data Patch** | `Setup/Patch/Data/{Name}.php` | `Setup/Patch/Data/CreateDefaultCategory.php` |

**Source**: `src/vendor/magento/module-catalog/Model/Product.php` — follows the `Model/{Entity}.php` convention.

### 2.5 Property visibility

```php
// Correct: private properties + public getters/setters
class Post
{
    private int $id;
    private string $title;
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
}

// Wrong: public properties
class Post
{
    public $id;
    public $title;
}
```

### 2.6 Dependency injection (constructor)

```php
// Correct: all dependencies as constructor parameters
class PostRepository
{
    private PostRepositoryInterface $repository;
    private ResourceModel\Post $resource;
    
    public function __construct(
        PostRepositoryInterface $repository,
        ResourceModel\Post $resource
    ) {
        $this->repository = $repository;
        $this->resource = $resource;
    }
}

// Wrong: explicit new (anti-pattern)
class PostRepository
{
    public function __construct()
    {
        $this->resource = new ResourceModel\Post();
    }
}
```

### 2.7 Documentation comments (PHPDoc)

```php
/**
 * Save a post.
 *
 * @param PostInterface $post
 * @return PostInterface
 * @throws CouldNotSaveException
 */
public function save(PostInterface $post): PostInterface
{
    // ...
}
```

**Mandatory tags**:
- `@param` for each parameter
- `@return` for the return value
- `@throws` for exceptions raised

---

## 3. XML — Magento conventions

### 3.1 Indentation

```xml
<!-- Correct: 4 spaces -->
<?xml version="1.0"?>
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <module name="Vendor_Module" setup_version="1.0.0">
        <sequence>
            <module name="Magento_Catalog"/>
        </sequence>
    </module>
</config>

<!-- Wrong: tabs or 2 spaces -->
<config>
    <module>
```

### 3.2 XML attributes

```xml
<!-- Correct: attribute order, double quotes -->
<block class="Vendor\Module\Block\PostList"
       name="blog.post.list"
       template="Vendor_Module::post/list.phtml"
       before="-"/>

<!-- Wrong: single quotes, random order -->
<block template="Vendor_Module::post/list.phtml" 
       name="blog.post.list" 
       class="Vendor\Module\Block\PostList"/>
```

### 3.3 Strings and translations

```xml
<!-- Correct: translate="true" for displayed labels -->
<item name="label" xsi:type="string" translate="true">Title</item>

<!-- Wrong: no translate for a displayed label -->
<item name="label" xsi:type="string">Title</item>
```

**Source**: `src/vendor/magento/module-backend/etc/menu.xml` — Magento core uses `translate="true"` for all displayed labels.

---

## 4. JavaScript — Magento conventions

### 4.1 File structure

```js
// Correct
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';
    
    return {
        init: function () {
            // ...
        }
    };
});
```

### 4.2 'use strict' mandatory

```js
// Correct
define(['jquery'], function ($) {
    'use strict';
    
    // code
});

// Wrong
define(['jquery'], function ($) {
    // code without 'use strict'
});
```

### 4.3 Variable naming

```js
// Correct: camelCase
var selectedStore = ko.observable('');
var isSaving = ko.observable(false);
var syncMessage = ko.observable('');

// Wrong: snake_case or PascalCase
var selected_store = ko.observable('');
var SelectedStore = ko.observable('');
```

**Source**: `src/vendor/magento/module-theme/view/frontend/web/js/theme.js` — Magento core JS follows these conventions.

---

## 5. PHTML — Conventions

### 5.1 Minimal structure

```php
<?php /** @var $block Vendor\Module\Block\PostList */ ?>
<?php /** @var $posts Vendor\Module\Model\Post[] */ ?>

<div class="blog-post-list">
    <?php foreach ($posts as $post): ?>
        <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
    <?php endforeach; ?>
</div>
```

### 5.2 Security: always escape

```php
<!-- Correct -->
<?= $block->escapeHtml($title) ?>
<?= $block->escapeUrl($url) ?>
<?= $block->escapeJs($js) ?>

<!-- DANGEROUS: XSS possible -->
<?= $title ?>
<?= $url ?>
```

### 5.3 No complex logic in PHTML

```php
<!-- Correct: logic in the Block, simple template -->
<?= $block->getPosts() ?>
<?php foreach ($block->getPosts() as $post): ?>
    <h2><?= $block->escapeHtml($post->getTitle()) ?></h2>
<?php endforeach; ?>

<!-- Wrong: business logic in the template -->
<?php
$posts = [];
$collection = $objectManager->create(\Vendor\Module\Model\ResourceModel\Post\Collection::class);
foreach ($collection as $post) {
    if ($post->getIsActive()) {
        $posts[] = $post;
    }
}
?>
```

---

## 6. Module — Canonical structure

### 6.1 Mandatory tree

> **Note**: The tree below uses `Vendor/Module` as a generic example.
> The same pattern applies to any module in Magento.

```
Vendor/Module/
├── registration.php          ← MANDATORY
├── etc/
│   ├── module.xml            ← MANDATORY (name, version, sequence)
│   ├── db_schema.xml         ← MANDATORY if tables
│   ├── acl.xml               ← MANDATORY if admin
│   ├── adminhtml/
│   │   ├── routes.xml        ← MANDATORY if admin
│   │   ├── menu.xml          ← MANDATORY if admin
│   │   └── system.xml        ← MANDATORY if config
│   ├── frontend/
│   │   ├── routes.xml        ← MANDATORY if frontend
│   │   └── di.xml            ← Optional
│   ├── webapi.xml            ← MANDATORY if REST API
│   ├── events.xml            ← Optional (observers)
│   ├── di.xml                ← Optional (plugins, preferences)
│   └── crontab.xml           ← Optional (cron)
├── Api/
│   ├── Data/
│   │   └── PostInterface.php ← MANDATORY if entity
│   └── PostRepositoryInterface.php ← MANDATORY if repository
├── Model/
│   ├── Post.php              ← Entity Model
│   ├── PostInterface.php     ← Interface
│   ├── PostRepository.php    ← Repository
│   ├── ResourceModel/
│   │   ├── Post.php          ← ResourceModel
│   │   └── Post/
│   │       └── Collection.php ← Collection
│   └── ...
├── Block/                    ← Frontend + admin blocks
├── Controller/
│   ├── Frontend/             ← Frontend controllers
│   └── Adminhtml/            ← Admin controllers
├── Ui/
│   ├── DataProvider/         ← Admin DataProviders
│   └── Component/            ← UI Component columns
├── view/
│   ├── frontend/
│   │   ├── layout/           ← Frontend layouts
│   │   ├── templates/        ← .phtml templates
│   │   └── web/              ← CSS, JS, images
│   └── adminhtml/
│       ├── layout/           ← Admin layouts
│       ├── ui_component/     ← UI Components XML
│       └── web/              ← CSS, admin JS
├── Setup/
│   └── Patch/
│       ├── Data/             ← Data Patches
│       └── Schema/           ← Schema Patches
└── i18n/                     ← CSV translations
```

### 6.2 Filenames

| Type | Convention | Example |
|------|-----------|---------|
| Classes | PascalCase + `.php` | `PostRepository.php` |
| Layouts | `{frontName}_{controller}_{action}.xml` | `blog_index_index.xml` |
| UI Components | `{module}_{entity}_{type}.xml` | `vendor_module_post_listing.xml` |
| Templates | `{entity}/{action}.phtml` | `post/list.phtml` |
| JS | `{type}/{name}.js` | `view/store-pickup.js` |
| CSS/Less | `_module.less` | `_module.less` |
| i18n | `{locale}.csv` | `fr_FR.csv` |

**Source**: `src/vendor/magento/module-catalog/` — Magento core modules follow the same canonical structure.

---

## 7. AlpineCommerce — Git Commit Conventions

### 7.1 Message format

```
type(scope): description

[optional: body]

[optional: footer]
```

**Types**:

| Type | Usage | Example |
|------|-------|---------|
| `feat` | New feature | `feat(blog): add category management` |
| `fix` | Bug fix | `fix(blog): prevent XSS in post title` |
| `docs` | Documentation | `docs: add StoreSetup module doc` |
| `style` | Formatting (no logic change) | `style: fix indentation in PostRepository` |
| `refactor` | Refactoring (no bug fix, no feature) | `refactor(blog): extract slugify to plugin` |
| `test` | Add/modify tests | `test(blog): add unit test for PostRepository` |
| `chore` | Maintenance | `chore: update composer dependencies` |
| `ci` | CI/CD | `ci: add PHP lint to GitHub Actions` |

### 7.2 AlpineCommerce examples

```
feat(blog): add REST API endpoints for posts
fix(storepickup): correct carrier plugin TypeError
docs: add StoreSetup module documentation
refactor(loyalty): move discount logic to total collector
test(faq): add integration test for REST API
ci: add markdown lint to GitHub Actions
```

---

## 8. Validation tools

### 8.1 PHP Lint

```bash
# Check a file's syntax
php -l app/code/Vendor/Module/Model/PostRepository.php

# Check all PHP files in a module
find app/code/Vendor/Module -name '*.php' -print0 | xargs -0 -n1 php -l
```

### 8.2 XML Lint

```bash
# Validate an XML file
xmllint --noout app/code/Vendor/Module/etc/module.xml

# Validate all XML in a module
find app/code/Vendor/Module -name '*.xml' -print0 | xargs -0 -n1 xmllint --noout
```

### 8.3 PHPStan (static analysis)

```bash
# Install PHPStan
composer require --dev phpstan/phpstan

# Run analysis
vendor/bin/phpstan analyse app/code/Vendor/Module --level=5
```

### 8.4 PHP_CodeSniffer (PSR-12)

```bash
# Install
composer require --dev magento/magento-coding-standard

# Check code
vendor/bin/phpcs --standard=PSR12 app/code/Vendor/Module/

# Auto-fix
vendor/bin/phpcbf --standard=PSR12 app/code/Vendor/Module/
```

**Source**: `src/vendor/magento/magento-coding-standard/` — Magento's own coding standard ruleset.

---

## 9. Pre-commit checklist

| Check | Command |
|--------------|----------|
| PHP syntax | `find app/code/Vendor/Module -name '*.php' -print0 \| xargs -0 -n1 php -l` |
| Valid XML | `find app/code/Vendor/Module -name '*.xml' -print0 \| xargs -0 -n1 xmllint --noout` |
| PSR-12 | `vendor/bin/phpcs --standard=PSR12 app/code/Vendor/Module/` |
| Module enabled | `php bin/magento module:status` |
| DB up to date | `php bin/magento setup:upgrade` (if db_schema.xml modified) |
| Cache flushed | `php bin/magento cache:flush` |
| Commit message | Format `type(scope): description` |

---

## 11. Summary

| Rule | Why |
|-------|----------|
| `declare(strict_types=1)` | Strict typing, no implicit cast |
| 4 spaces, no tabs | Consistency with PSR-12 |
| `private` + getters/setters | Encapsulation |
| DI injection in constructor | Testability, flexibility |
| `escapeHtml()` in PHTML | XSS security |
| `translate="true"` in XML | Internationalization |
| Complete PHPDoc | Documentation, IDE support |
| Formatted commit message | Readable history |

---

## 11. AlpineCommerce Reference

### 11.1 AlpineCommerce Git Commit Conventions

The AlpineCommerce project follows these commit conventions:

```
feat(blog): add category management
fix(storepickup): correct carrier plugin TypeError
docs: add StoreSetup module documentation
refactor(loyalty): move discount logic to total collector
test(faq): add integration test for REST API
ci: add markdown lint to GitHub Actions
```

### 11.2 AlpineCommerce coding patterns

| Pattern | AlpineCommerce approach |
|---------|------------------------|
| Namespace | `AlpineCommerce\` |
| Module prefix | `AlpineCommerce_` |
| Admin menu | Content section (Blog, Faq, StorePickup, StoreLocator) |
| Admin menu | Marketing section (ProductReviews, ProductQuestions) |
| Admin menu | Catalog section (ProductLabels) |
| Frontend patterns | KO components (StorePickup, LoyaltyProgram) |
| Frontend patterns | jQuery AJAX (ProductReviews, ProductQuestions) |
| Log prefix | Module name prefix (e.g., `StoreSetup/AfterSave`) |

### 11.3 AlpineCommerce module structure example

```
AlpineCommerce/Blog/
├── registration.php
├── etc/
│   ├── module.xml
│   ├── adminhtml/
│   │   ├── routes.xml
│   │   ├── menu.xml
│   │   └── acl.xml
│   └── db_schema.xml
├── Controller/
│   └── Adminhtml/
│       └── Post/
│           ├── Index.php
│           ├── Edit.php
│           └── Save.php
├── Ui/
│   ├── DataProvider/
│   │   └── PostListingDataProvider.php
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── PostActions.php
├── view/
│   └── adminhtml/
│       ├── layout/
│       │   └── blog_post_index.xml
│       └── ui_component/
│           └── blog_post_listing.xml
└── Setup/
    └── Patch/
        └── Data/
```

**Sources**:
- `src/app/code/AlpineCommerce/Blog/`
- `src/app/code/AlpineCommerce/StorePickup/`
- `src/app/code/AlpineCommerce/CustomerCare/`

---

## 12. Official Documentation & Sources

| Topic | Link |
|-------|------|
| Magento Coding Standards | [developer.adobe.com/commerce/php/architecture/coding-standards/](https://developer.adobe.com/commerce/php/architecture/coding-standards/) |
| PSR-12 | [www.php-fig.org/psr/psr-12/](https://www.php-fig.org/psr/psr-12/) |
| PHP_CodeSniffer | [github.com/squizlabs/PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) |
| PHPStan | [phpstan.org](https://phpstan.org/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

**Sources**:
- `src/vendor/magento/magento-coding-standard/` — Magento's coding standard ruleset
- `src/vendor/magento/module-*/` — Magento core module implementations
- `src/app/code/AlpineCommerce/` — AlpineCommerce custom modules

*Last updated: 2026-09-07*
