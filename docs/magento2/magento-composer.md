# Composer & Dependency Management

> **Objective**: master Composer, the PHP package manager used by Magento
> 2. Learn to install, update, and manage dependencies, and understand how
> Magento's `composer.json` works.

---

## Table of Contents

1. [What is Composer?](#1-what-is-composer)
2. [Composer Basics](#2-composer-basics)
3. [Magento's composer.json](#3-magentos-composerjson)
4. [Common Composer Workflows in Magento](#4-common-composer-workflows-in-magento)
5. [composer.lock](#5-composerlock)
6. [Version Constraints](#6-version-constraints)
7. [Composer Scripts](#7-composer-scripts)
8. [Autoloading in Magento](#8-autoloading-in-magento)
9. [Common Issues](#9-common-issues)
10. [Best Practices](#10-best-practices)
11. [Summary](#11-summary)
12. [AlpineCommerce Reference](#12-alpinecommerce-reference)

---

## 1. What is Composer?

**Composer** is the **dependency manager** for PHP. It downloads libraries
(packages) that your project needs and manages their versions.

**In Magento 2**:
- Composer installs Magento core (`magento/product-community-edition`)
- Composer installs all third-party libraries (`monolog/monolog`, `twig/twig`, etc.)
- Composer generates the autoloader (`vendor/autoload.php`)

### 1.1 Key concepts

| Concept | Explanation | Example |
|---------|-------------|---------|
| **Package** | A library distributed via Composer | `magento/framework` |
| **Version** | Specific release of a package | `103.0.8`, `^2.4.8`, `~1.0.0` |
| **Dependency** | A package that another package needs | `magento/framework` needs `monolog/monolog` |
| **Lock file** | Exact versions installed | `composer.lock` |
| **Autoloader** | Auto-loads classes without `require` | `vendor/autoload.php` |

**Source**: `composer.json` at the Magento root — defines all project dependencies.

---

## 2. Composer Basics

### 2.1 Installation

```bash
# Linux (Ubuntu/Debian)
sudo apt update
sudo apt install -y composer

# macOS
brew install composer

# Verify
composer --version
```

### 2.2 Core commands

```bash
# Install dependencies from composer.json
composer install

# Install dependencies + dev dependencies
composer install --with-dev

# Install without dev dependencies (production)
composer install --no-dev

# Add a package
composer require vendor/package-name

# Add a package for development only
composer require --dev vendor/package-name

# Remove a package
composer remove vendor/package-name

# Update all packages
composer update

# Update a specific package
composer update vendor/package-name

# Show installed packages
composer show

# Show a specific package
composer show magento/framework

# Validate composer.json
composer validate
```

---

## 3. Magento's composer.json

### 3.1 Main composer.json

```json
{
    "name": "magento/project-community-edition",
    "description": "eCommerce Platform for Growth (Community Edition)",
    "type": "project",
    "version": "2.4.8",
    "require": {
        "magento/product-community-edition": "2.4.8",
        "magento/framework": "103.0.8",
        "monolog/monolog": "^2.0",
        "twig/twig": "^3.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^9.0",
        "squizlabs/php_codesniffer": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "Magento\\Framework\\": "lib/internal/Magento/Framework/",
            "Magento\\Setup\\": "setup/src/Magento/Setup/"
        }
    }
}
```

**Source**: `composer.json` at the Magento project root.

### 3.2 Autoloading (PSR-4)

Composer maps namespaces to directories:

```json
"autoload": {
    "psr-4": {
        "Vendor\\Module\\": "app/code/Vendor/Module/"
    }
}
```

This means:
- Class `Vendor\Module\Model\Post`
- File: `app/code/Vendor/Module/Model/Post.php`

**After modifying `composer.json`**:
```bash
composer dump-autoload
```

### 3.3 Module autoloading via registration

Each Magento module registers itself with `ComponentRegistrar`:

```php
// registration.php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Vendor_Module',
    __DIR__
);
```

**Source**: `src/vendor/magento/module-backend/registration.php` — Magento core modules register the same way.

---

## 4. Common Composer Workflows in Magento

### 4.1 First installation

```bash
cd /path/to/magento

# Install all dependencies
composer install --no-dev

# Generate autoloader
composer dump-autoload
```

### 4.2 Adding a new library

```bash
# Example: add a PDF generation library
composer require tecnickcom/tcpdf

# The library is installed in vendor/
# Composer automatically updates autoloader
# Use it in PHP:
use Tecnickcom\Tcpdf\TCPDF;
```

### 4.3 Updating Magento

```bash
# Update Magento core
composer update magento/product-community-edition --with-dependencies

# Or update all packages
composer update

# After update:
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 4.4 Installing a module from Packagist

```bash
# Example: install a third-party module
composer require vendor/module-name

# Enable it
php bin/magento module:enable Vendor_ModuleName

# Run setup
php bin/magento setup:upgrade
```

---

## 5. composer.lock

### 5.1 What is it?

`composer.lock` records the **exact versions** installed. It ensures that
every developer on the team and every server has the **same versions**.

### 5.2 Git workflow

```bash
# ALWAYS commit composer.lock
git add composer.json composer.lock
git commit -m "chore: update dependencies"

# When pulling:
git pull
composer install  # Uses composer.lock for exact versions
```

### 5.3 When to update

```bash
# composer.lock should be updated when:
# - You add a new package (composer require)
# - You update a package (composer update vendor/package)
# - You want to check for new versions (composer update --dry-run)
```

---

## 6. Version Constraints

### 6.1 Operators

| Operator | Meaning | Example |
|----------|---------|---------|
| `1.0.0` | Exact version | `1.0.0` only |
| `^1.0.0` | Compatible with 1.0.0 | `>=1.0.0 <2.0.0` |
| `~1.0.0` | Approximately equivalent to 1.0.0 | `>=1.0.0 <1.1.0` |
| `>=1.0.0` | Greater than or equal | `1.0.0`, `1.5.0`, `2.0.0`, etc. |
| `1.0.*` | Any 1.0.x version | `1.0.0`, `1.0.1`, `1.0.99` |

### 6.2 Magento conventions

```json
{
    "magento/product-community-edition": "2.4.8",      // Exact version
    "magento/framework": "103.0.8",                    // Exact version
    "monolog/monolog": "^2.0",                          // Compatible with 2.x
    "php": "^8.1.0"                                     // PHP 8.1 or higher
}
```

**Source**: `composer.json` — Magento core uses exact versions for its own packages and `^` for third-party libraries.

---

## 7. Composer Scripts

Magento defines useful scripts in `composer.json`:

```json
{
    "scripts": {
        "post-install-cmd": [
            "Magento\\Framework\\Composer\\ComposerPlugin::postInstall"
        ],
        "post-update-cmd": [
            "Magento\\Framework\\Composer\\ComposerPlugin::postUpdate"
        ]
    }
}
```

**Source**: `src/vendor/magento/framework/Composer/ComposerPlugin.php` — Magento's Composer plugin for post-install hooks.

---

## 8. Autoloading in Magento

### 8.1 How it works

```
composer.json
    ↓
composer dump-autoload
    ↓
vendor/autoload.php (generated)
    ↓
PHP includes vendor/autoload.php
    ↓
When new Post() is called:
    - Autoloader checks namespace mapping
    - Loads app/code/Vendor/Module/Model/Post.php
    - No require/include needed
```

### 8.2 Magento's autoloading

Magento uses **multiple autoloaders**:
1. Composer autoloader (`vendor/autoload.php`) — third-party libs
2. Magento code generator — factories, proxies, interceptors
3. Module registration — `ComponentRegistrar` maps modules to paths

```php
// registration.php
ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Vendor_Module',
    __DIR__
);
```

This tells Magento: "The module `Vendor_Module` is in this directory."

**Source**: `src/vendor/magento/framework/Component/ComponentRegistrar.php` — registers modules with Magento.

---

## 9. Common Issues

### 9.1 "Class not found"

**Cause**: autoloader not updated.

**Solution**:
```bash
composer dump-autoload
php bin/magento setup:di:compile
```

### 9.2 "Package not found"

**Cause**: package doesn't exist or repository not configured.

**Solution**:
```bash
# Check if package exists
composer show vendor/package-name

# Add repository if private
composer config repositories.myrepo vcs https://github.com/vendor/repo
```

### 9.3 "Memory limit exhausted"

**Cause**: Composer needs more memory.

**Solution**:
```bash
COMPOSER_MEMORY_LIMIT=-1 composer update
```

### 9.4 "Dependency conflict"

**Cause**: two packages require incompatible versions.

**Solution**:
```bash
# See why a package was installed
composer why monolog/monolog

# See what depends on a package
composer depends magento/framework

# Resolve by updating or removing conflicting packages
```

---

## 10. Best Practices

| Practice | Why |
|----------|-----|
| **Always commit `composer.lock`** | Ensures identical versions everywhere |
| **Use `composer install` in CI/production** | Uses `composer.lock` for exact versions |
| **Use `composer update` only when needed** | Updates versions, can introduce breaking changes |
| **Don't edit `vendor/` directly** | Changes are lost on next `composer install` |
| **Use `^` for libraries, exact versions for apps** | Libraries: allow minor updates. Apps: lock exact versions |
| **Run `composer validate` before commit** | Ensures `composer.json` is valid |

---

## 11. Summary

| Command | Purpose |
|---------|---------|
| `composer install` | Install dependencies from `composer.lock` |
| `composer update` | Update dependencies, regenerate `composer.lock` |
| `composer require vendor/pkg` | Add a new package |
| `composer remove vendor/pkg` | Remove a package |
| `composer dump-autoload` | Regenerate autoloader |
| `composer validate` | Validate `composer.json` |
| `composer show` | List installed packages |

### Key takeaways

1. **Composer manages PHP dependencies** — Magento core, third-party libs, custom modules
2. **`composer.lock` ensures reproducibility** — always commit it
3. **`composer install` uses the lock file** — `composer update` changes it
4. **Autoloading is automatic** — no more `require` statements
5. **Never edit `vendor/`** — changes are overwritten

---

## 12. AlpineCommerce Reference

### 12.1 Module autoloading

AlpineCommerce modules are **not** separate Composer packages. They are
installed in `app/code/AlpineCommerce/` directly and registered via
`ComponentRegistrar`:

```php
// src/app/code/AlpineCommerce/Blog/registration.php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'AlpineCommerce_Blog', __DIR__);
```

### 12.2 Module-to-namespace mapping

| Module | Namespace | Path |
|--------|-----------|------|
| Blog | `AlpineCommerce\Blog` | `src/app/code/AlpineCommerce/Blog/` |
| Faq | `AlpineCommerce\Faq` | `src/app/code/AlpineCommerce/Faq/` |
| StorePickup | `AlpineCommerce\StorePickup` | `src/app/code/AlpineCommerce/StorePickup/` |
| CustomerCare | `AlpineCommerce\CustomerCare` | `src/app/code/AlpineCommerce/CustomerCare/` |
| LoyaltyProgram | `AlpineCommerce\LoyaltyProgram` | `src/app/code/AlpineCommerce/LoyaltyProgram/` |
| Gdpr | `AlpineCommerce\Gdpr` | `src/app/code/AlpineCommerce/Gdpr/` |

**Sources**:
- `src/app/code/AlpineCommerce/*/registration.php`
- `composer.json` (project root)

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| Composer Setup | [developer.adobe.com/commerce/php/architecture/modules/composer/](https://developer.adobe.com/commerce/php/architecture/modules/composer/) |
| Module Structure | [developer.adobe.com/commerce/php/architecture/modules/](https://developer.adobe.com/commerce/php/architecture/modules/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

AlpineCommerce-specific implementations are referenced from `src/app/code/AlpineCommerce/`.

*Last updated: 2026-09-07*
