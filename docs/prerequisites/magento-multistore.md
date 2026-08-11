# Magento 2 — Multi-Store Deep Dive

> **Objective**: master Magento's multi-store architecture: websites, stores,
> store views, configuration scopes, fallback system, and how AlpineCommerce
> uses multiple stores.

---

## 1. Magento's Multi-Store Architecture

### 1.1 The hierarchy

```
Website (base)
├── Store (group)
│   ├── Store View (English)        ← Language + Currency
│   ├── Store View (French)         ← Language + Currency
│   └── Store View (German)         ← Language + Currency
└── Store (group)
    └── Store View (Spanish)        ← Language + Currency
```

### 1.2 Definitions

| Level | Purpose | Shared data | Separate data |
|-------|---------|-------------|---------------|
| **Website** | Top-level entity | Orders, customers, catalogs | Configuration, payment methods, shipping |
| **Store (group)** | Groups store views | Cart, checkout, customer session | Root category, design |
| **Store View** | Language/currency view | Products, categories | Language, currency, theme |

### 1.3 Real-world example

**AlpineCommerce** uses one website with 4 store views:

```
Website: AlpineCommerce (ID: 1)
├── Store Group: Default (ID: 1)
│   ├── Store View: English (code: default, locale: en_US, currency: USD)
│   ├── Store View: French (code: french, locale: fr_FR, currency: EUR)
│   ├── Store View: German (code: german, locale: de_DE, currency: EUR)
│   └── Store View: Spanish (code: spanish, locale: es_ES, currency: EUR)
```

---

## 2. Configuration Scopes

### 2.1 Scope levels

Magento configuration can be set at **three levels**:

| Scope | Applies to | Example |
|-------|-----------|---------|
| **Default** | All websites | Base URL, timezone |
| **Website** | Specific website | Payment methods, shipping methods |
| **Store View** | Specific store view | Language, currency, translations |

### 2.2 Viewing scopes in admin

```
Stores > Settings > Configuration
    ↓
Scope selector (top-left corner):
    - Default Config
    - Website: Base (ID: 1)
    - Store View: English (ID: 1)
    - Store View: French (ID: 2)
```

### 2.3 Scope in code

```php
// Get config for current scope
$value = $this->scopeConfig->getValue('path/to/config');

// Get config for specific scope
$value = $this->scopeConfig->getValue(
    'path/to/config',
    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
    $storeId
);
```

---

## 3. Store URLs and Switching

### 3.1 Base URLs per store view

```
Stores > Configuration > General > Web > Base URLs (Secure)
    ↓
Scope: English → https://en.example.com/
Scope: French  → https://fr.example.com/
Scope: German  → https://de.example.com/
```

### 3.2 Store switching

```php
// Switch to a specific store view
$store = $this->storeManager->getStore('french');
$this->storeManager->setCurrentStore($store->getId());

// Get current store
$store = $this->storeManager->getStore();
$storeId = $store->getId();
$storeCode = $store->getCode();
$locale = $store->getLocaleCode();
$currency = $store->getBaseCurrencyCode();
```

### 3.3 Store switcher in frontend

```php
// Block/StoreSwitcher.php
class StoreSwitcher extends Template
{
    public function getStores(): array
    {
        return $this->storeManager->getStores(true);
    }
}
```

```html
<!-- template/store-switcher.phtml -->
<?php foreach ($block->getStores() as $store): ?>
    <a href="<?= $block->escapeUrl($store->getCurrentUrl()) ?>">
        <?= $block->escapeHtml($store->getName()) ?>
    </a>
<?php endforeach; ?>
```

---

## 4. Root Categories

### 4.1 What is a root category?

A **root category** is the top-level category for a store. It determines
which products are visible in that store.

```
Root Category: Default (ID: 2)
├── Men
│   ├── Shirts
│   └── Pants
├── Women
│   ├── Dresses
│   └── Shoes
└── Accessories

Root Category: Spanish (ID: 3)
├── Hombre
│   ├── Camisas
│   └── Pantalones
└── Mujer
    ├── Vestidos
    └── Zapatos
```

### 4.2 Assigning root categories

```
Stores > All Stores
    ↓
Edit Store: French
    ↓
Root Category: French Catalog (ID: 4)
```

### 4.3 Root category in code

```php
// Get root category for a store
$store = $this->storeManager->getStore('french');
$rootCategoryId = $store->getRootCategoryId();

// Load category
$category = $this->categoryRepository->get($rootCategoryId, $store->getId());
```

---

## 5. Shared vs Separate Data

### 5.1 Shared across all stores

| Data | Where stored |
|------|-------------|
| Products | `catalog_product_entity` |
| Categories | `catalog_category_entity` |
| Customers | `customer_entity` |
| Orders | `sales_order` |
| Quotes | `quote` |

### 5.2 Store-specific

| Data | Where stored |
|------|-------------|
| Product name (per store view) | `catalog_product_entity_varchar` (with `store_id`) |
| Product price (per website) | `catalog_product_index_price` (with `website_id`) |
| Stock (per website) | `cataloginventory_stock_status` (with `website_id`) |
| Category name (per store view) | `catalog_category_entity_varchar` (with `store_id`) |
| CMS pages (per store view) | `cms_page` (with `store_id`) |
| Configuration | `core_config_data` (with `scope` and `scope_id`) |

---

## 6. Fallback System

### 6.1 Config fallback

When Magento looks for a configuration value:

```
1. Store View scope (e.g., French)
   ↓ not found?
2. Website scope (e.g., Base)
   ↓ not found?
3. Default scope (global)
   ↓ not found?
4. Hardcoded default in system.xml
```

### 6.2 Template fallback

When Magento looks for a template:

```
1. Current theme: app/design/frontend/AlpineCommerce/theme/...
   ↓ not found?
2. Parent theme: app/design/frontend/Magento/luma/...
   ↓ not found?
3. Module fallback: app/code/AlpineCommerce/Blog/view/frontend/...
```

### 6.3 Translation fallback

```
1. Current locale (fr_FR.csv)
   ↓ not found?
2. Parent locale (if fr_CA inherits from fr_FR)
   ↓ not found?
3. Default locale (en_US.csv)
   ↓ not found?
4. Original string (English source)
```

---

## 7. EAV per Store View

### 7.1 How EAV uses store views

EAV attributes can be **store-specific** or **global**:

```xml
<!-- db_schema.xml for a product attribute -->
<column xsi:type="varchar" name="name" nullable="false" length="255">
    <constraint xsi:type="unique">...</constraint>
</column>
```

When you save a product name for the French store view:
```php
$product->setStoreId(2); // French store view
$product->setName('Produit génial');
$product->save();
```

Magento saves to `catalog_product_entity_varchar` with `store_id = 2`.

### 7.2 Reading store-specific values

```php
// In English store view (store_id = 1)
$product = $productRepository->getById(1, false, 1); // English
echo $product->getName(); // "Awesome Product"

// In French store view (store_id = 2)
$product = $productRepository->getById(1, false, 2); // French
echo $product->getName(); // "Produit génial"
```

### 7.3 Fallback in EAV

If a value is not set for a specific store view, Magento falls back to
the **default store view** (store_id = 0).

---

## 8. Currency per Website

### 8.1 Configuring currencies

```
Stores > Configuration > General > Currency Setup
    ↓
Scope: Website: Base
    ↓
Base Currency: USD
    ↓
Scope: Website: European
    ↓
Base Currency: EUR
```

### 8.2 Currency in code

```php
// Get current currency
$currency = $this->storeManager->getStore()->getCurrentCurrencyCode();

// Get base currency
$baseCurrency = $this->storeManager->getStore()->getBaseCurrencyCode();

// Convert price
$price = 100; // USD
$converted = $price * $this->currencyFactory->create()->getRate('USD', 'EUR');
```

---

## 9. AlpineCommerce Multi-Store Setup

### 9.1 Configuration in StoreSetup

```xml
<!-- etc/config.xml -->
<default>
    <stores>
        <website>
            <code>base</code>
            <name>Base Website</name>
        </website>
    </stores>
    <store>
        <default>
            <code>default</code>
            <name>English</name>
            <locale>en_US</locale>
            <currency>USD</currency>
        </default>
        <french>
            <code>french</code>
            <name>French</name>
            <locale>fr_FR</locale>
            <currency>EUR</currency>
        </french>
        <german>
            <code>german</code>
            <name>German</name>
            <locale>de_DE</locale>
            <currency>EUR</currency>
        </german>
        <spanish>
            <code>spanish</code>
            <name>Spanish</name>
            <locale>es_ES</locale>
            <currency>EUR</currency>
        </spanish>
    </store>
</default>
```

### 9.2 Data Patch for stores

```php
// Setup/Patch/Data/CreateStores.php
class CreateStores implements DataPatchInterface
{
    public function apply(): void
    {
        // Create store views programmatically
        $store = $this->storeFactory->create();
        $store->setCode('french')
              ->setName('French')
              ->setLocaleId($this->localeRepository->get('fr_FR')->getId())
              ->setWebsiteId(1)
              ->save();
    }
}
```

---

## 10. Working with Stores in Code

### 10.1 Get current store

```php
$store = $this->storeManager->getStore();
$storeId = $store->getId();
$storeCode = $store->getCode();
$websiteId = $store->getWebsiteId();
```

### 10.2 Get all stores

```php
/** @var StoreInterface[] $stores */
$stores = $this->storeManager->getStores(true);

foreach ($stores as $store) {
    echo $store->getCode() . ' - ' . $store->getName();
}
```

### 10.3 Load product for specific store view

```php
// Repository with store ID
$product = $this->productRepository->getById(1, false, $storeId);
$name = $product->getName(); // Store-specific name
```

### 10.4 Filter collection by store

```php
$collection = $this->productCollectionFactory->create();
$collection->addStoreFilter($storeId);
$collection->addAttributeToSelect('*');
```

---

## 11. Common Issues

### 11.1 Wrong product name in store view

**Cause**: product name not translated for that store view.

**Solution**:
```php
// Set store ID before saving
$product->setStoreId($frenchStoreId);
$product->setName('Nom français');
$product->save();
```

### 11.2 Config not applied

**Cause**: config set at wrong scope.

**Solution**: Check scope selector in admin, or check `core_config_data`:

```sql
SELECT * FROM core_config_data
WHERE path = 'path/to/config'
AND scope = 'stores'
AND scope_id = 2;
```

### 11.3 Categories not visible

**Cause**: category not assigned to store's root category.

**Solution**: Assign category to correct root category in admin.

---

## 12. Summary

| Concept | Purpose | Example |
|---------|---------|---------|
| **Website** | Separate business units | Base (US), European (EU) |
| **Store** | Group store views | Default store group |
| **Store View** | Language/currency | English, French, German |
| **Config scope** | Per-website or per-store-view settings | Currency, payment methods |
| **Root category** | Product visibility per store | English catalog, French catalog |
| **Fallback** | Use default when specific value missing | English name → French name |
| **EAV store scope** | Store-specific attribute values | Product name per language |

### AlpineCommerce Multi-Store

| Store View | Locale | Currency | Code |
|------------|--------|----------|------|
| English | en_US | USD | default |
| French | fr_FR | EUR | french |
| German | de_DE | EUR | german |
| Spanish | es_ES | EUR | spanish |

---

*Last updated: 2026-08-11.*
