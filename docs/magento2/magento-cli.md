# Magento 2 — CLI (`bin/magento`): Practical Guide

> **Objective**: master the Magento command line. These commands are used
> **every day** by developers: module installation,
> compilation, cache, static content deployment.

---

## Table of Contents

1. [What is `bin/magento`?](#1-what-is-binmagento)
2. [How to execute commands](#2-how-to-execute-commands)
3. [Essential commands](#3-essential-commands)
4. [Useful development commands](#4-useful-development-commands)
5. [Typical development workflow](#5-typical-development-workflow)
6. [Commands by scenario](#6-commands-by-scenario)
7. [Common errors](#7-common-errors)
8. [Quick reference table](#8-quick-reference-table)
9. [Summary](#9-summary)
10. [AlpineCommerce Reference](#10-alpinecommerce-reference)

---

## 1. What is `bin/magento`?

`bin/magento` is the Magento **CLI (Command Line Interface)**. It is a
PHP executable that gives access to all maintenance and development
operations.

**Why it is important**:
- Enable/disable modules: `module:enable`, `module:disable`
- Update the database: `setup:upgrade`
- Compile code (production): `setup:di:compile`
- Flush caches: `cache:flush`
- Create an admin: `admin:user:create`

**Source**: `src/vendor/magento/framework/Console/CommandList.php` — the command list registry that all CLI commands are registered to.

---

## 2. How to execute commands

### 2.1 Direct execution (Magento standard)

```bash
cd /path/to/magento

php bin/magento module:status
php bin/magento cache:flush
```

### 2.2 Command structure

```
php bin/magento <command> [arguments] [options]
```

**Examples**:
```bash
php bin/magento module:enable Vendor_Module
php bin/magento setup:upgrade --keep-generated
php bin/magento cache:flush --group=config
```

---

## 3. Essential commands

### 3.1 Module management

```bash
# List all modules and their status
php bin/magento module:status

# Enable a module
php bin/magento module:enable Vendor_Module

# Disable a module
php bin/magento module:disable Vendor_Module

# Enable multiple modules
php bin/magento module:enable Vendor_Module1 Vendor_Module2

# Check dependencies (who depends on what)
php bin/magento module:dependency:show Vendor_Module

# View a module's configuration
php bin/magento module:config:show Vendor_Module
```

**Source**: `src/vendor/magento/module-developer/Console/Command/ModuleStatusCommand.php` — implements the `module:status` command.

### 3.2 Database update

```bash
# Apply db_schema.xml and data patch changes
php bin/magento setup:upgrade

# With sample data
php bin/magento setup:upgrade --sample-data=yes

# Check database status
php bin/magento setup:db:status
```

**When to use it?**
- After creating/modifying a `db_schema.xml`
- After creating/modifying a Data Patch
- After enabling a new module

**Source**: `src/vendor/magento/module-setup/Console/Command/UpgradeCommand.php` — implements the `setup:upgrade` command.

### 3.3 DI compilation (Dependency Injection)

```bash
# Compile code for production
# Generates interceptors, factories, proxies
php bin/magento setup:di:compile

# Verify compilation
php bin/magento setup:di:compile --dry-run
```

**When to use it?**
- In production (mandatory)
- In development: only if you modify `di.xml` or have
  "Class not found" errors
- After adding plugins, preferences, virtual types

**Source**: `src/vendor/magento/framework/Console/Command/DiCompileCommand.php` — generates factories, interceptors, and proxies.

### 3.4 Cache

```bash
# Flush all caches
php bin/magento cache:flush

# Flush a specific cache
php bin/magento cache:clean layout
php bin/magento cache:clean block_html
php bin/magento cache:clean config

# Enable/disable a cache type
php bin/magento cache:enable layout
php bin/magento cache:disable layout

# View cache status
php bin/magento cache:status

# Flush configuration cache
php bin/magento app:config:dump
```

**When to use `cache:flush` vs `cache:clean`?**
- `cache:clean`: empties the cache but keeps the configuration
- `cache:flush`: empties EVERYTHING (more drastic, OK in dev)

**Source**: `src/vendor/magento/module-backend/Console/Command/CacheFlushCommand.php` — implements cache flush operations.

### 3.5 Static content

```bash
# Deploy static content (CSS, JS, fonts)
php bin/magento setup:static-content:deploy -f

# For a specific locale
php bin/magento setup:static-content:deploy -f fr_FR de_DE

# For a specific theme
php bin/magento setup:static-content:deploy -f --theme="Vendor/theme"

# In development mode: no need for this command
# Files are generated on the fly
```

**Source**: `src/vendor/magento/module-deploy/Console/Command/DeployStaticContentCommand.php` — handles static content deployment.

### 3.6 Indexing

```bash
# Reindex all indexers
php bin/magento indexer:reindex

# Reindex a specific indexer
php bin/magento indexer:reindex catalogsearch_fulltext

# View indexer status
php bin/magento indexer:status

# Set "update on schedule" mode (cron)
php bin/magento indexer:set-mode schedule

# Set "update on save" mode (immediate)
php bin/magento indexer:set-mode realtime
```

**Source**: `src/vendor/magento/module-indexer/Console/Command/IndexerReindexCommand.php` — implements reindex operations.

### 3.7 Deployment management

```bash
# Put the site in maintenance mode
php bin/magento maintenance:enable

# Take the site out of maintenance mode
php bin/magento maintenance:disable

# See who is in maintenance mode
php bin/magento maintenance:status

# Allow an IP to access during maintenance
php bin/magento maintenance:enable --ip=192.168.1.100
```

**Source**: `src/vendor/magento/module-deploy/Console/Command/MaintenanceEnableCommand.php` — implements maintenance mode commands.

### 3.8 Admin

```bash
# Create an admin user
php bin/magento admin:user:create \
    --admin-name="Admin" \
    --admin-email="admin@example.com" \
    --admin-firstname="Admin" \
    --admin-lastname="User" \
    --admin-user="admin" \
    --admin-password="Admin123!"

# List admins
php bin/magento admin:user:list

# Change password
php bin/magento admin:user:change-password --admin-user=admin

# Delete an admin
php bin/magento admin:user:delete --admin-user=admin
```

---

## 4. Useful development commands

### 4.1 Magento mode

```bash
# View current mode
php bin/magento deploy:mode:show

# Switch to developer mode
php bin/magento deploy:mode:set developer

# Switch to production mode
php bin/magento deploy:mode:set production

# Switch to default mode
php bin/magento deploy:mode:set default
```

| Mode | Usage | Characteristics |
|------|-------|-----------------|
| **developer** | Local development | No compilation, errors displayed, simplified cache |
| **production** | Live server | Compiled code, hidden errors, aggressive cache |
| **default** | In between | Optional compilation, errors displayed |

**Source**: `src/vendor/magento/module-deploy/Console/Command/DeployModeSetCommand.php` — implements mode switching.

### 4.2 System information

```bash
# View Magento version
php bin/magento --version

# View all available commands
php bin/magento list

# View environment info
php bin/magento info:backup:info
```

### 4.3 Theme management

```bash
# View installed themes
php bin/magento theme:list

# Install a theme
php bin/magento theme:install Vendor_theme
```

**Source**: `src/vendor/magento/module-theme/Console/Command/ThemeListCommand.php` — lists installed themes.

### 4.4 Translation management

```bash
# Generate translation files
php bin/magento i18n:collect-phrases -f -o app/code/Vendor/Module/i18n/fr_FR.csv app/code/Vendor/Module

# Check missing translations
php bin/magento i18n:check app/code/Vendor/Module/i18n/fr_FR.csv
```

**Source**: `src/vendor/magento/module-translation/Console/Command/CollectPhrasesCommand.php` — collects translatable phrases.

---

## 5. Typical development workflow

### 5.1 After modifying a module

```bash
# 1. Enable the module (if new)
php bin/magento module:enable Vendor_Module

# 2. Update the DB (if db_schema.xml or data patch modified)
php bin/magento setup:upgrade

# 3. Compile (if "class not found" error or di.xml modification)
php bin/magento setup:di:compile

# 4. Flush caches
php bin/magento cache:flush

# 5. If you modify JS/CSS:
php bin/magento setup:static-content:deploy -f
# Or in developer mode: just flush cache
```

### 5.2 After modifying a layout or template

```bash
# In DEVELOPER mode: just flush cache
php bin/magento cache:flush

# Changes take effect immediately
```

### 5.3 After modifying PHP files (outside di.xml)

```bash
# In DEVELOPER mode: nothing to do!
# Magento regenerates code automatically

# In PRODUCTION mode:
php bin/magento setup:di:compile
```

### 5.4 Complete workflow after a git pull

```bash
# 1. Update Composer dependencies
composer install --no-dev

# 2. Enable modules (if new)
php bin/magento module:enable Vendor_Module1 Vendor_Module2

# 3. Update the DB
php bin/magento setup:upgrade

# 4. Compile
php bin/magento setup:di:compile

# 5. Deploy static content
php bin/magento setup:static-content:deploy -f

# 6. Reindex
php bin/magento indexer:reindex

# 7. Flush caches
php bin/magento cache:flush

# 8. Check mode
php bin/magento deploy:mode:set developer
```

---

## 6. Commands by scenario

### 6.1 "I created a new module"

```bash
# 1. Create files (registration.php, module.xml, etc.)
# 2. Enable the module
php bin/magento module:enable Vendor_Module

# 3. Update the DB (if db_schema.xml)
php bin/magento setup:upgrade

# 4. Compile
php bin/magento setup:di:compile

# 5. Flush caches
php bin/magento cache:flush
```

### 6.2 "I modified a PHTML template"

```bash
# In developer mode: just flush cache
php bin/magento cache:flush
```

### 6.3 "I modified a layout XML"

```bash
# In developer mode: just flush cache
php bin/magento cache:flush
```

### 6.4 "I added a plugin in di.xml"

```bash
# Recompile
php bin/magento setup:di:compile

# Flush caches
php bin/magento cache:flush
```

### 6.5 "I added a column in db_schema.xml"

```bash
# Update the DB
php bin/magento setup:upgrade

# Recompile
php bin/magento setup:di:compile

# Flush caches
php bin/magento cache:flush
```

### 6.6 "The site is slow / CSS not loading"

```bash
# Redeploy static content
php bin/magento setup:static-content:deploy -f

# Flush caches
php bin/magento cache:flush

# Reindex
php bin/magento indexer:reindex
```

### 6.7 "I want to test in production mode"

```bash
# Switch to production mode
php bin/magento deploy:mode:set production

# Compile
php bin/magento setup:di:compile

# Deploy static content
php bin/magento setup:static-content:deploy -f
```

---

## 7. Common errors

### 7.1 "Area code is not set"

**Cause**: you are running a command that requires an area, but Magento
does not know in which context to run.

**Solution**:
```bash
# Add the --area option
php bin/magento setup:upgrade --area=frontend
```

### 7.2 "Class not found"

**Cause**: the code is not compiled (production mode) or interceptors
are outdated.

**Solution**:
```bash
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### 7.3 "Permission denied" on var/, pub/, generated/

**Cause**: file permissions are incorrect.

**Solution**:
```bash
# Linux
sudo chown -R 1000:1000 var/ pub/ generated/
sudo chmod -R 755 var/ pub/ generated/
```

### 7.4 "The command did not stop after 10 seconds"

**Cause**: `setup:upgrade` is blocked (slow data patch, DB inaccessible).

**Solution**:
```bash
# Increase the timeout
php -d max_execution_time=600 bin/magento setup:upgrade
```

### 7.5 "Cache storage is not writable"

**Cause**: permissions on `var/cache/` or `var/page_cache/`.

**Solution**:
```bash
sudo chmod -R 777 var/cache/ var/page_cache/
```

---

## 8. Quick reference table

| Task | Command |
|------|---------|
| Enable a module | `module:enable Vendor_Module` |
| Update the DB | `setup:upgrade` |
| Compile code | `setup:di:compile` |
| Flush caches | `cache:flush` |
| Deploy static content | `setup:static-content:deploy -f` |
| Reindex | `indexer:reindex` |
| Create an admin | `admin:user:create` |
| View mode | `deploy:mode:show` |
| Switch to dev | `deploy:mode:set developer` |
| Switch to prod | `deploy:mode:set production` |
| Maintenance ON | `maintenance:enable` |
| Maintenance OFF | `maintenance:disable` |
| View all commands | `list` |

---

## 9. Summary

| Concept | Explanation |
|---------|------------|
| `bin/magento` | Magento CLI, entry point for all commands |
| `module:enable/disable` | Enables/disables a module |
| `setup:upgrade` | Applies schema/DB changes |
| `setup:di:compile` | Generates DI code (interceptors, factories, proxies) |
| `cache:flush` | Flushes all caches |
| `setup:static-content:deploy` | Generates CSS, JS, fonts (production) |
| `indexer:reindex` | Reindexes data (search, categories...) |
| `deploy:mode` | Switches between developer / production |
| `maintenance:enable` | Enables maintenance mode |

### Daily work order (development)

```bash
# Morning: start Docker
docker compose up -d

# After coding:
php bin/magento cache:flush

# If "class not found" error:
php bin/magento setup:di:compile
php bin/magento cache:flush

# If CSS/JS modification:
php bin/magento setup:static-content:deploy -f

# If DB modification (db_schema.xml / data patch):
php bin/magento setup:upgrade
php bin/magento cache:flush
```

---

## 10. AlpineCommerce Reference

### 10.1 Project-specific commands

The AlpineCommerce project provides convenience wrappers for common
Magento CLI operations:

```bash
# Helper script
./scripts/magento-cli.sh module:status
./scripts/magento-cli.sh cache:flush

# Docker convenience
docker compose exec php bash
php bin/magento cache:flush
```

### 10.2 AlpineCommerce modules

| Module | Common commands |
|--------|----------------|
| Blog | `module:enable AlpineCommerce_Blog`, `setup:upgrade` |
| Faq | `module:enable AlpineCommerce_Faq`, `setup:upgrade` |
| StorePickup | `module:enable AlpineCommerce_StorePickup`, `cache:flush` |
| CustomerCare | `module:enable AlpineCommerce_CustomerCare`, `cron:run` |
| LoyaltyProgram | `module:enable AlpineCommerce_LoyaltyProgram`, `setup:upgrade` |
| Gdpr | `module:enable AlpineCommerce_Gdpr`, `setup:upgrade` |

### 10.3 Translation generation

```bash
# Generate translation files for AlpineCommerce modules
php bin/magento i18n:collect-phrases -f -o src/app/code/AlpineCommerce/Blog/i18n/fr_FR.csv src/app/code/AlpineCommerce/Blog
```

**Source**: `src/app/code/AlpineCommerce/Blog/i18n/` — AlpineCommerce translation files.

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| CLI Commands | [developer.adobe.com/commerce/php/architecture/cli/](https://developer.adobe.com/commerce/php/architecture/cli/) |
| Module Management | [developer.adobe.com/commerce/php/architecture/modules/](https://developer.adobe.com/commerce/php/architecture/modules/) |
| Setup Upgrade | [developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/](https://developer.adobe.com/commerce/php/architecture/modules/declarative-configuration/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under `src/vendor/magento/`.

AlpineCommerce-specific implementations are referenced from `src/app/code/AlpineCommerce/`.

*Last updated: 2026-09-07*
