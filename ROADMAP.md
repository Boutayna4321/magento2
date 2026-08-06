# Magento 2.4.8 - Training Roadmap

> Had roadmap khassk tebqa 3andek Bach t3awnk f kolchi kat9der t3mel f had l-projet.
> Kol section fih: **Wach howa**, **Kifach kaykhdm**, w **Commands dyalo**.

---

## Table of Contents

1. [Infrastructure & Docker](#1-infrastructure--docker)
2. [Magento Basics](#2-magento-basics)
3. [Module Development](#3-module-development)
4. [Multi-Store Setup](#4-multi-store-setup)
5. [Theme & Frontend](#5-theme--frontend)
6. [Observers & Events](#6-observers--events)
7. [Database & Config](#7-database--config)
8. [CLI Commands](#8-cli-commands)
9. [Debugging & Troubleshooting](#9-debugging--troubleshooting)
10. [Common Workflows](#10-common-workflows)
11. [File Structure Reference](#11-file-structure-reference)
12. [Quick Cheat Sheet](#12-quick-cheat-sheet)

---

## 1. Infrastructure & Docker

### Wach howa
L-projet dyalek kaykhdm 3la Docker. Kol service kayjry f wa7ed l-container mo9tane3.

### Architecture

```
┌─────────────────────────────────────────────────┐
│                  HOST (your PC)                  │
│                                                  │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐  │
│  │  Nginx   │───▶│   PHP    │───▶│  MySQL   │  │
│  │  :8080   │    │  :9000   │    │  :3306   │  │
│  └──────────┘    └────┬─────┘    └──────────┘  │
│                       │                          │
│                  ┌────┴─────┐    ┌──────────┐  │
│                  │ Elastic  │    │  Redis   │  │
│                  │  :9200   │    │  :6379   │  │
│                  └──────────┘    └──────────┘  │
└─────────────────────────────────────────────────┘
```

### Services

| Service       | Container           | Port   | Wach howa                                    |
|---------------|---------------------|--------|----------------------------------------------|
| Nginx         | magento2-nginx      | 8080   | Web server - kat-serve les requetes HTTP      |
| PHP-FPM       | magento2-php        | 9000   | PHP interpreter - kat-executer l-code PHP     |
| MySQL         | magento2-mysql      | 3306   | Database - kat-khzen les données             |
| Elasticsearch | magento2-elasticsearch | 9200 | Search engine - kat-9emel full-text search   |
| Redis         | magento2-redis      | 6379   | Cache - kat-s3el l-page loading               |
| Composer      | magento2-composer   | --     | Dependency manager                            |

### Credentials

```
MySQL Root Password:  root123
MySQL Database:       magento2
MySQL User:           magento / magento123
Magento URL:          http://localhost:8080
Deployment Mode:      developer
```

### Commands dyal Docker

```bash
# Start chi services
docker compose up -d

# Stop ga3 les services
docker compose down

# 3reft l9adech les services khedamin
docker ps

# 7talwa chi service
docker compose restart nginx

# 3reft les logs dyal chi service
docker logs magento2-php --tail 50

# Dkhel f chi container
docker exec -it magento2-php bash

# Executer command f chi container
docker exec magento2-php php -v
```

### Files dyal Infrastructure

```
magento/
├── docker-compose.yml          # Definition dyal ga3 les services
├── php/
│   ├── Dockerfile              # Image dyal PHP 8.2-fpm + extensions
│   ├── php.ini                 # Config dyal PHP (memory, timeout, etc.)
│   └── opcache.ini             # OPcache + JIT config
├── nginx/
│   ├── default.conf            # Server block (upstream, MAGE_ROOT)
│   └── nginx.conf.sample       # Magento nginx rules (dyal static files, PHP, etc.)
├── mysql/
│   └── init.sql                # DB creation + user privileges
├── elasticsearch/
│   └── elasticsearch.yml       # ES config (single-node, no security)
├── redis/
│   └── redis.conf              # 256MB max, LRU eviction
├── scripts/
│   ├── install.sh              # Full Magento install automation
│   ├── start.sh                # docker compose up -d
│   ├── stop.sh                 # docker compose down
│   └── magento-cli.sh          # Wrapper: docker compose exec php bin/magento "$@"
└── .env                        # Docker environment variables
```

---

## 2. Magento Basics

### Wach howa Magento
Magento 2 huwa **e-commerce platform** built f PHP using **Symfony** components w **Zend Framework**. Kaykhdm b architecture **modular** - kol feature huwa wa7ed module.

### Architecture dyal Magento

```
Request → Nginx → PHP-FPM → Magento Bootstrap
                                    │
                                    ▼
                              ┌─────────────┐
                              │   Router     │
                              └──────┬──────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │  Controller  │
                              └──────┬──────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │  Block/Model  │
                              └──────┬──────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │  Template    │
                              └──────┬──────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │  Layout XML  │
                              └──────┬──────┘
                                     │
                                     ▼
                              ┌─────────────┐
                              │   HTML/CSS   │
                              └─────────────┘
```

### Magento File Structure

```
src/
├── app/
│   ├── code/                    # Custom modules (hna kan7eto modules dyalna)
│   │   └── Cartware/
│   │       └── Training/        # Module dyalna
│   ├── design/
│   │   └── frontend/
│   │       └── Cartware/
│   │           └── Training/    # Theme dyalna
│   └── etc/
│       ├── config.php           # List dyal ga3 les modules (enabled/disabled)
│       └── env.php              # Environment config (DB, cache, etc.)
├── bin/
│   └── magento                  # Magento CLI
├── generated/                   # Auto-generated code (DI, Interceptors, etc.)
├── lib/                         # Magento framework libraries
├── pub/                         # Web-accessible directory
│   ├── static/                  # CSS, JS, images (compiled/deployed)
│   ├── media/                   # Uploaded files
│   └── index.php                # Entry point
├── setup/                       # Magento installer
├── var/                         # Cache, logs, sessions
└── vendor/                      # Composer dependencies
```

### Key Concepts

| Concept | Wach howa |
|---------|-----------|
| **Module** | Wa7ed package fih: classes, config, templates, etc. Kaytsyana b `module.xml` |
| **Theme** | Design/layout. Kaytsyana b `theme.xml`. Kaykhdm b parent theme (inheritance) |
| **Store View** | Wa7ed locale/language (e.g., fr_FR, de_DE). Kaytsyana f `core_config_data` |
| **Store Group** | Group dyal store views (e.g., "France" website) |
| **Website** | Top level - kayjme3 chi store groups |
| **Block** | PHP class kat-prepare data dyal template |
| **Template** | PHTML file - kat-render l-HTML |
| **Layout XML** | XML file kat-definir wach kayn f l-page (blocks, containers) |
| **Observer** | Event listener - kat-running code when wa7ed event kaytsra |
| **Plugin/Interceptor** | Way to extend method dyal class bla ma tbedl l-asli |

---

## 3. Module Development

### Module Structure

```
Cartware/Training/
├── registration.php              # Kan-register 3nd ComponentRegistrar
├── etc/
│   ├── module.xml                # Module definition (name, version, dependencies)
│   ├── config.xml                # Default config values
│   ├── di.xml                    # Dependency Injection config
│   ├── system.xml                # Admin config fields
│   └── events.xml                # Event observers
├── Helper/
│   └── Data.php                  # Utility methods (shared across module)
├── Block/
│   └── StoreInfo.php             # Frontend block
├── Observer/
│   ├── ProductSaveAfter.php      # Event observer
│   └── ...
├── Console/
│   └── Command/
│       └── CreateStoresCommand.php  # CLI command
├── Setup/
│   └── Patch/
│       └── Data/
│           └── CreateStores.php     # Data patch (runs on setup:upgrade)
└── view/
    ├── frontend/
    │   ├── layout/
    │   │   └── default.xml      # Layout XML for all pages
    │   ├── templates/
    │   │   └── store-info.phtml  # Template file
    │   └── i18n/                 # Translation files (CSV)
    └── adminhtml/                # Admin panel templates (if needed)
```

### registration.php

```php
<?php
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,    // Type: MODULE or THEME
    'Cartware_Training',           // Module name (Vendor_Module)
    __DIR__                        // Path
);
```

### module.xml

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Module/etc/module.xsd">
    <module name="Cartware_Training" setup_version="1.1.0">
        <sequence>
            <!-- Les modules li khasshom ytsalo qbel had module -->
            <module name="Magento_Store"/>
            <module name="Magento_Catalog"/>
        </sequence>
    </module>
</config>
```

### di.xml (Dependency Injection)

```xml
<!-- Kan-injecta object f constructor -->
<type name="Cartware\Training\Helper\Data">
    <arguments>
        <argument name="storeManager" xsi:type="object">
            Magento\Store\Model\StoreManagerInterface
        </argument>
   argument>
</type>

<!-- Kan-register CLI command -->
<type name="Magento\Framework\Console\CommandListInterface">
    <arguments>
        <argument name="commands" xsi:type="array">
            <item name="training_store_create" xsi:type="object">
                Cartware\Training\Console\Command\CreateStoresCommand
            </item>
        </argument>
    </arguments>
</type>
```

### Helper Class Pattern

```php
<?php
namespace Cartware\Training\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

class Data extends AbstractHelper
{
    // Kay-extend AbstractHelper - fih $this->scopeConfig (config reader)
    
    public function __construct(
        Context $context,
        // Inject dependencies here
    ) {
        parent::__construct($context);  // REQUIRED - khassk d3iha
    }
    
    // L3tab3 dial store config:
    public function isEnabled()
    {
        // $this->scopeConfig kayqra mn etc/config.xml + core_config_data
        return $this->scopeConfig->isSetFlag('training/general/enabled');
    }
    
    public function getValue()
    {
        return $this->scopeConfig->getValue('some/config/path');
    }
}
```

### Block Class Pattern

```php
<?php
namespace Cartware\Training\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class StoreInfo extends Template
{
    private $helper;

    public function __construct(
        Context $context,
        Data $helper,         // Kan-injecta l-helper
        array $data = []
    ) {
        parent::__construct($context, $data);  // REQUIRED
        $this->helper = $helper;
    }

    // Methods dyalk ghadi ytshtalo mn template
    public function getStoreData()
    {
        return [
            'name' => $this->helper->getStoreName(),
            // ...
        ];
    }
}
```

### Template Pattern (PHTML)

```php
<?php
// $this huwa l-block object
// $escaper huwa XSS protection object (Magento 2.4+)
$storeData = $this->getStoreData();
?>
<div class="my-block">
    <!-- Kan-escape l-output bach n7miw mn XSS -->
    <p><?= $escaper->escapeHtml($storeData['name']) ?></p>
    
    <!-- For booleans, kan-l3abo direct -->
    <p><?= $storeData['is_active'] ? 'Yes' : 'No' ?></p>
</div>
```

### Layout XML Pattern

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <!-- Kan-zido block f wa7ed container -->
        <referenceContainer name="content">
            <block class="Cartware\Training\Block\StoreInfo"
                   name="training.store.info"
                   template="Cartware_Training::store-info.phtml"
                   after="-"/>
        </referenceContainer>
    </body>
</page>
```

### Module Commands

```bash
# Compile DI (generate proxies, factories, etc.)
docker exec magento2-php bin/magento setup:di:compile

# Run setup:upgrade (apply data patches)
docker exec magento2-php bin/magento setup:upgrade

# Check module status
docker exec magento2-php bin/magento module:status Cartware_Training

# Enable module
docker exec magento2-php bin/magento module:enable Cartware_Training

# Disable module
docker exec magento2-php bin/magento module:disable Cartware_Training

# Check PHP syntax
docker exec magento2-php php -l /var/www/html/app/code/Cartware/Training/Helper/Data.php
```

---

## 4. Multi-Store Setup

### Wach howa
Magento kaykhdm b **3 levels** dyal hierarchy:

```
Website (e.g., "Default Website")
  └── Store Group (e.g., "Main Website Store")
        └── Store View (e.g., "French", "German")
```

**Store View** hiya li kat-definir l-locale, currency, w language.

### Stores dyalna

| Store ID | Code          | Name           | Locale | Currency | Country |
|----------|---------------|----------------|--------|----------|---------|
| 1        | default       | Default Store  | en_GB  | GBP      | GB      |
| 6        | french        | French         | fr_FR  | EUR      | FR      |
| 7        | german        | German         | de_DE  | EUR      | DE      |
| 8        | spanish       | Spanish        | es_ES  | EUR      | ES      |
| 22       | default_fr    | Default French | fr_FR  | EUR      | GB      |

### Kifach kan9loq stores

#### Tari9a 1: CLI Command (recommended)

```bash
docker exec magento2-php bin/magento training:store:create
```

Hadi kan9loq 4 stores (french, german, spanish, default_fr) w kan7tt l-config values (locale, currency, country) mn `etc/config.xml` w kan-assign theme.

#### Tari9a 2: Data Patch (runs automatically)

```bash
docker exec magento2-php bin/magento setup:upgrade
```

Hadi kan9loq nafs les stores ga3, but kan-running automatically f setup.

#### Tari9a 3: Admin Panel

1. Dkhel f Admin → Stores → All Stores
2. Click "Create Store View"
3. 7ta l-locale, currency, etc.

### Store Config (etc/config.xml)

```xml
<config>
    <!-- Default values (for all stores) -->
    <default>
        <training>
            <general>
                <enabled>1</enabled>
            </general>
        </training>
    </default>

    <!-- Store-specific values -->
    <stores>
        <french>  <!-- Code dyal store -->
            <general>
                <locale>
                    <code>fr_FR</code>
                </locale>
            </general>
        </french>
    </stores>
</config>
```

### Theme Assignment (critical!)

**Important:** Kan9loq store, khassk kan-assign theme! Ila ma 3titihach theme, kayakhdo fallback w CSS maykhdemch.

```sql
-- Kan-verifiw theme assignment
SELECT s.code, cv.value as theme_id
FROM store s
LEFT JOIN core_config_data cv 
  ON cv.scope = 'stores' AND cv.scope_id = s.store_id 
  AND cv.path = 'design/theme/theme_id'
WHERE s.code != 'admin';

-- Kan7tto theme l store
INSERT INTO core_config_data (scope, scope_id, path, value)
VALUES ('stores', 22, 'design/theme/theme_id', 4);
```

### Static Files Deployment

```bash
# Deploy CSS/JS/images ga3 les locales
docker exec magento2-php bin/magento setup:static-content:deploy -f

# Deploy ghir locale wa7ed
docker exec magento2-php bin/magento setup:static-content:deploy fr_FR de_DE es_ES

# Check les locales deployed
ls pub/static/frontend/Cartware/Training/
```

---

## 5. Theme & Frontend

### Theme Structure

```
Cartware/Training/
├── registration.php          # Kan-register l-theme
├── theme.xml                 # Title + parent theme
├── web/
│   └── css/
│       ├── _styles.less      # Main LESS file (imports ga3)
│       ├── styles-m.less     # Mobile styles
│       ├── styles-l.less     # Desktop styles
│       ├── print.less        # Print styles
│       ├── email.less        # Email styles
│       └── source/
│           └── _.less        # Source directory (imports from parent)
├── i18n/                     # Translations
│   ├── fr_FR.csv
│   ├── de_DE.csv
│   └── es_ES.csv
```

### theme.xml

```xml
<theme xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
       xsi:noNamespaceSchemaLocation="urn:magento:framework:Config/etc/theme.xsd">
    <title>Cartware Training</title>
    <parent>Magento/Luma</parent>  <!-- Parent theme - kan-heritawiw mnou -->
</theme>
```

### LESS Architecture

```
styles-m.less (Mobile)
├── source/_reset.less        # CSS reset (from parent)
├── _styles.less              # Main styles
│   ├── source/lib/_lib.less  # Global lib (from parent)
│   ├── source/_sources.less  # Theme styles (from parent)
│   └── source/_components.less  # Components (from parent)
├── source/_theme.less        # Theme overrides
└── source/lib/_responsive.less  # Responsive mixins

styles-l.less (Desktop)
├── _styles.less              # Same main styles
├── source/_theme.less
└── source/lib/_responsive.less
```

**Important:** Les `@import` statements kanqraw relative l l-file. Ila l-file ma kaynch f l-child theme, kan-heritawiwh mn parent.

### Layout XML

```
view/frontend/layout/
├── default.xml               # Applies to ga3 les pages
├── catalog_product_view.xml  # Product page ghir
├── catalog_category_view.xml # Category page ghir
└── cms_index_index.xml       # Homepage ghir
```

### Template Locations

```
view/frontend/templates/
├── store-info.phtml          # Template dyalna
├── html/
│   ├── header.phtml
│   └── footer.phtml
└── ...
```

### Theme Commands

```bash
# Clean theme cache
docker exec magento2-php bin/magento cache:clean

# Deploy static files
docker exec magento2-php bin/magento setup:static-content:deploy -f

# Compile LESS (developer mode - automatic)
# CSS files generated on-the-fly f pub/static/frontend/
```

---

## 6. Observers & Events

### Wach howa
Magento kay-emmit **events** f chi mqa3d (e.g., product saved, order placed). **Observers** kan9dro nkhdmo chi code when events hadou kaytsra.

### Events dyalna

| Event Name | Trigger | Observer Class | Wach kay-dir |
|------------|---------|----------------|--------------|
| `catalog_product_save_after` | Product saved | `ProductSaveAfter` | Kay-log SKU, name, price. Kay-set short description ila kan khawi |
| `sales_order_place_after` | Order placed | `OrderPlacedAfter` | Kay-log order ID, status, total, customer email |
| `checkout_controller_before_action` | Before checkout | `CheckoutBefore` | Kay-log "Checkout accessed" |
| `customer_customer_login` | Customer login | `CustomerLogin` | Kay-log customer email, ID, name |

### Observer Pattern

```php
<?php
namespace Cartware\Training\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;

class ProductSaveAfter implements ObserverInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        // Kan-get data mn l-event
        $product = $observer->getEvent()->getProduct();
        
        // Kan-3mlo chi 7aja (e.g., log)
        $this->logger->info('Product saved: ' . $product->getSku());
        
        // Kan-modify l-product (note: already saved f DB)
        if (empty($product->getShortDescription())) {
            $product->setShortDescription('Auto-generated...');
            // NOTE: hadi may-khdmch bach tsave f DB - khassk t-save o5er mara
        }
        
        return $this;  // REQUIRED
    }
}
```

### events.xml

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:Event/etc/events.xsd">
    <event name="catalog_product_save_after">
        <observer name="training_product_save"
                  instance="Cartware\Training\Observer\ProductSaveAfter"/>
    </event>
</config>
```

### Common Magento Events

| Event | When | Data Available |
|-------|------|----------------|
| `catalog_product_save_after` | Product saved | `getProduct()` |
| `catalog_product_save_before` | Before product save | `getProduct()` |
| `sales_order_place_after` | Order placed | `getOrder()` |
| `sales_order_save_after` | Order saved | `getOrder()` |
| `checkout_controller_before_action` | Before checkout | -- |
| `customer_customer_login` | Customer login | `getCustomer()` |
| `customer_register_success` | Customer registered | `getCustomer()` |
| `controller_action_predispatch` | Before any controller | `getActionName()` |

---

## 7. Database & Config

### Key Tables

| Table | Wach howa |
|-------|-----------|
| `store` | Les store views (id, code, name, is_active) |
| `store_group` | Store groups |
| `website` | Websites |
| `core_config_data` | Ga3 les config values (scope: default/website/store) |
| `theme` | Registered themes |
| `eav_attribute` | Product/customer attributes |
| `catalog_product_entity` | Products |
| `sales_order` | Orders |
| `customer_entity` | Customers |

### Config Scopes

```
default (ga3 les stores)
  └── website (e.g., "base")
        └── store (e.g., "french")
```

```sql
-- Kan-qraw config value
SELECT * FROM core_config_data 
WHERE path = 'training/general/enabled';

-- Kan-qraw config value l store-specific
SELECT * FROM core_config_data 
WHERE path = 'general/locale/code' 
AND scope = 'stores' 
AND scope_id = 6;  -- Store ID

-- Kan-hatto config value
INSERT INTO core_config_data (scope, scope_id, path, value)
VALUES ('stores', 6, 'general/locale/code', 'fr_FR');
```

### Store Info Query

```sql
SELECT 
    s.store_id,
    s.code,
    s.name,
    s.is_active,
    cv_locale.value as locale,
    cv_currency.value as currency,
    cv_theme.value as theme_id
FROM store s
LEFT JOIN core_config_data cv_locale 
    ON cv_locale.scope = 'stores' AND cv_locale.scope_id = s.store_id 
    AND cv_locale.path = 'general/locale/code'
LEFT JOIN core_config_data cv_currency 
    ON cv_currency.scope = 'stores' AND cv_currency.scope_id = s.store_id 
    AND cv_currency.path = 'currency/options/default'
LEFT JOIN core_config_data cv_theme 
    ON cv_theme.scope = 'stores' AND cv_theme.scope_id = s.store_id 
    AND cv_theme.path = 'design/theme/theme_id'
WHERE s.code != 'admin'
ORDER BY s.store_id;
```

---

## 8. CLI Commands

### Magento Commands

```bash
# ── Setup & Install ──
bin/magento setup:install                    # Install Magento (first time)
bin/magento setup:upgrade                    # Apply DB changes, data patches
bin/magento setup:di:compile                 # Compile DI (generate code)
bin/magento setup:static-content:deploy -f   # Deploy CSS/JS/images
bin/magento setup:db:generate-declaration    # Generate DB schema

# ── Cache ──
bin/magento cache:clean                      # Clean all caches
bin/magento cache:flush                      # Flush Magento cache storage
bin/magento cache:status                     # Check cache status
bin/magento cache:disable                    # Disable all caches
bin/magento cache:enable                     # Enable all caches

# ── Modules ──
bin/magento module:status                    # List all modules + status
bin/magento module:enable Module_Name        # Enable module
bin/magento module:disable Module_Name       # Disable module
bin/magento module:status Cartware_Training  # Check specific module

# ── Indexers ──
bin/magento indexer:reindex                   # Reindex all
bin/magento indexer:status                   # Check indexer status
bin/magento indexer:reindex catalog_product_attribute_eav  # Reindex one

# ── Mode ──
bin/magento deploy:mode:show                 # Show current mode
bin/magento deploy:mode:set developer        # Set developer mode
bin/magento deploy:mode:set production       # Set production mode

# ── Admin ──
bin/magento admin:user:create                # Create admin user

# ── Custom Commands ──
bin/magento training:store:create            # Our custom CLI command
```

### Using the Wrapper Script

```bash
# Instead of typing docker exec magento2-php bin/magento ...
./scripts/magento-cli.sh cache:clean
./scripts/magento-cli.sh setup:upgrade
./scripts/magento-cli.sh module:status
```

### PHP Syntax Check

```bash
docker exec magento2-php php -l /var/www/html/app/code/Cartware/Training/Helper/Data.php
```

---

## 9. Debugging & Troubleshooting

### CSS/JS Broken

```bash
# 1. Check mode (developer = on-the-fly, production = deployed)
bin/magento deploy:mode:show

# 2. Clean cache
bin/magento cache:clean

# 3. Deploy static files
bin/magento setup:static-content:deploy -f

# 4. Check theme assignment
SELECT * FROM core_config_data WHERE path = 'design/theme/theme_id';

# 5. Check if CSS files exist
ls pub/static/frontend/Cartware/Training/en_GB/css/
```

### White Screen / 500 Error

```bash
# 1. Check PHP error log
docker exec magento2-php tail -50 /var/log/php_errors.log

# 2. Check Magento log
tail -50 src/var/log/system.log
tail -50 src/var/log/exception.log

# 3. Enable display errors (dev only!)
# php.ini: display_errors = On

# 4. Check permissions
ls -la src/var/ src/pub/static/ src/generated/
```

### Module Not Working

```bash
# 1. Check if enabled
bin/magento module:status Cartware_Training

# 2. Check if registered in config.php
grep "Cartware_Training" src/app/etc/config.php

# 3. Compile DI
bin/magento setup:di:compile

# 4. Run setup:upgrade
bin/magento setup:upgrade

# 5. Clean cache
bin/magento cache:clean

# 6. Check PHP syntax of all files
find src/app/code/Cartware/Training -name "*.php" -exec docker exec magento2-php php -l /var/www/html/{} \;
```

### Store Not Found

```bash
# 1. Check stores in DB
docker exec magento2-mysql mysql -u root -proot123 magento2 -e "SELECT * FROM store;"

# 2. Check store config
docker exec magento2-mysql mysql -u root -proot123 magento2 -e "
SELECT s.code, cv.path, cv.value 
FROM store s 
JOIN core_config_data cv ON cv.scope_id = s.store_id 
WHERE cv.path LIKE 'general/%' 
ORDER BY s.store_id, cv.path;"

# 3. Check theme assignment
docker exec magento2-mysql mysql -u root -proot123 magento2 -e "
SELECT s.code, cv.value 
FROM store s 
LEFT JOIN core_config_data cv ON cv.scope = 'stores' AND cv.scope_id = s.store_id AND cv.path = 'design/theme/theme_id';"
```

### DI Compilation Errors

```bash
# 1. Delete generated code
docker exec magento2-php rm -rf /var/www/html/generated/code/*
docker exec magento2-php rm -rf /var/www/html/generated/metadata/*

# 2. Recompile
bin/magento setup:di:compile
```

### Container Issues

```bash
# 1. Check container status
docker ps

# 2. Check container logs
docker logs magento2-php --tail 100
docker logs magento2-nginx --tail 100
docker logs magento2-mysql --tail 100

# 3. Rebuild containers
docker compose down
docker compose build --no-cache
docker compose up -d

# 4. Enter container for debugging
docker exec -it magento2-php bash
docker exec -it magento2-mysql mysql -u root -proot123
```

---

## 10. Common Workflows

### Workflow: Kanbni wa7ed module jdid

```bash
# 1. Kan-creer l-structure
mkdir -p src/app/code/Cartware/MyModule/etc
mkdir -p src/app/code/Cartware/MyModule/Helper
mkdir -p src/app/code/Cartware/MyModule/Block
mkdir -p src/app/code/Cartware/MyModule/view/frontend/layout
mkdir -p src/app/code/Cartware/MyModule/view/frontend/templates

# 2. Kan-creer registration.php
# 3. Kan-creer etc/module.xml
# 4. Kan-creer etc/di.xml (ila khassni DI)
# 5. Kan-creer Helper, Block, etc.
# 6. Kan-creer layout XML + template
# 7. Kan-compile + kan-test
docker exec magento2-php bin/magento setup:di:compile
docker exec magento2-php bin/magento cache:clean
```

### Workflow: Kan-modify template

```bash
# 1. Kan-edit l-template PHTML file
# 2. Kan-clean cache
docker exec magento2-php bin/magento cache:clean layout block_html
# 3. Kan-refresh l-page f browser (Ctrl+Shift+R)
```

### Workflow: Kan-add wa7ed config field

```bash
# 1. Kan-add field f etc/system.xml
# 2. Kan-add default value f etc/config.xml
# 3. Kan-clean cache
docker exec magento2-php bin/magento cache:clean config
```

### Workflow: Kan-add wa7ed observer

```bash
# 1. Kan-creer Observer class
# 2. Kan-register f etc/events.xml
# 3. Kan-clean cache
docker exec magento2-php bin/magento cache:clean
```

### Workflow: Kan-deploy l-changes

```bash
# Developer mode (on-the-fly):
docker exec magento2-php bin/magento cache:clean

# Production mode (static deployment):
docker exec magento2-php bin/magento setup:di:compile
docker exec magento2-php bin/magento setup:static-content:deploy -f
docker exec magento2-php bin/magento cache:clean
```

---

## 11. File Structure Reference

### Module Files (wach kanqraw wa7ed module)

```
registration.php          → Module registration (ComponentRegistrar)
etc/module.xml            → Module definition (name, version, sequence)
etc/config.xml            → Default config values
etc/di.xml                → Dependency Injection
etc/system.xml            → Admin config fields
etc/events.xml            → Event observers
Helper/Data.php           → Utility methods
Block/*.php               → Frontend blocks
Observer/*.php            → Event observers
Console/Command/*.php     → CLI commands
Setup/Patch/Data/*.php    → Data patches (runs on setup:upgrade)
view/frontend/layout/*.xml     → Layout XML
view/frontend/templates/*.phtml → Templates
view/frontend/i18n/*.csv       → Translations
```

### Theme Files

```
registration.php          → Theme registration
theme.xml                 → Theme definition (title, parent)
web/css/*.less            → LESS stylesheets
web/css/source/*.less     → Source files
web/js/*.js               → JavaScript files
web/images/*              → Theme images
i18n/*.csv                → Translations
```

### Config Files

```
app/etc/config.php        → Module list (enabled/disabled)
app/etc/env.php           → Environment (DB, cache, etc.)
docker-compose.yml        → Docker services
.php/php.ini              → PHP config
nginx/default.conf        → Nginx config
redis/redis.conf          → Redis config
```

---

## 12. Quick Cheat Sheet

### Docker

```bash
docker compose up -d                    # Start
docker compose down                     # Stop
docker compose restart nginx            # Restart service
docker ps                               # List running
docker exec -it magento2-php bash       # Enter container
docker logs magento2-php --tail 50      # Check logs
```

### Magento

```bash
bin/magento cache:clean                  # Clean cache
bin/magento setup:upgrade                # Apply changes
bin/magento setup:di:compile             # Compile DI
bin/magento setup:static-content:deploy -f  # Deploy CSS/JS
bin/magento module:status                # Module status
bin/magento deploy:mode:show             # Show mode
bin/magento indexer:reindex              # Reindex
```

### MySQL

```bash
docker exec magento2-mysql mysql -u root -proot123 magento2 -e "QUERY;"
```

### PHP Check

```bash
docker exec magento2-php php -l /var/www/html/PATH/TO/FILE.php
```

### File Locations

```
Module code:       src/app/code/Cartware/
Theme:             src/app/design/frontend/Cartware/
Static files:      src/pub/static/frontend/
Logs:              src/var/log/
Cache:             src/var/cache/
Generated code:    src/generated/
Config (DB):       core_config_data table
```

---

## Environment Info

```
Magento Version:  2.4.8
PHP Version:      8.2
MySQL Version:    8.0
Elasticsearch:    8.11.4
Redis:            7.2
Nginx:            1.25
Mode:             developer
Theme:            Cartware/Training (parent: Magento/Luma)
Module:           Cartware_Training v1.1.0
```

---

*Last updated: 2026-07-15*
