# Composer for Magento

**Composer** is PHP's dependency manager. Magento 2 uses Composer extensively.

## Key concepts

| Concept | Description |
|---|---|
| **Package** | A library (e.g., `magento/product-community-edition`) |
| **Vendor** | Package author (e.g., `magento`) |
| **Version** | Semantic versioning (e.g., `2.4.8`) |
| **Constraint** | Version requirement (e.g., `^2.4.8`) |
| **Lock file** | Exact versions installed (`composer.lock`) |

## Essential commands

```bash
composer install          # Install dependencies from lock file
composer update           # Update dependencies
composer require vendor/package  # Add a new package
composer remove vendor/package  # Remove a package
composer dump-autoload     # Regenerate autoloader
```

## Magento-specific

```bash
# Install Magento with all dependencies
composer install

# Add a module from Packagist
composer require vendor/module-name

# Update Magento core
composer update magento/product-community-edition --with-all-dependencies
```

## Autoloading

Magento uses PSR-4 autoloading defined in `composer.json`:

```json
{
  "autoload": {
    "psr-4": {
      "AlpineCommerce\\": "src/app/code/AlpineCommerce/"
    }
  }
}
```

## Where to go next

- **Full reference**: [`../magento2/magento-composer.md`](../magento2/magento-composer.md)
- **Engineering standards**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: installing dependencies, creating modules*
