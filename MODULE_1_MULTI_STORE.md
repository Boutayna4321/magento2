# Module 1: Multi-Store Setup - European Focus

## Store Structure

```
Default Website (UK - English)
├── Default Store View (English) ← store_id=1
├── French Store View ← store_id=6
├── German Store View ← store_id=7
└── Spanish Store View ← store_id=8
```

---

## 1. Why European Stores?

### Real World Scenario
A company sells across Europe:
- **UK website** - English, GBP
- **France website** - French, EUR
- **Germany website** - German, EUR
- **Spain website** - Spanish, EUR

Each market needs:
- Local language
- Local currency
- Local payment methods
- Local shipping rates
- Local taxes (VAT)

---

## 2. Store Configuration

### Locale Settings (per store view)
| Store | Language | Locale | Currency |
|-------|----------|--------|----------|
| Default | English | en_GB | GBP |
| French | French | fr_FR | EUR |
| German | German | de_DE | EUR |
| Spanish | Spanish | es_ES | EUR |

### Config Commands
```bash
# French store view (store_id=6)
bin/magento config:set general/locale/code fr_FR --scope=stores --scope-id=6
bin/magento config:set currency/options/default EUR --scope=stores --scope-id=6
bin/magento config:set general/country/default FR --scope=stores --scope-id=6

# German store view (store_id=7)
bin/magento config:set general/locale/code de_DE --scope=stores --scope-id=7
bin/magento config:set currency/options/default EUR --scope=stores --scope-id=7
bin/magento config:set general/country/default DE --scope=stores --scope-id=7

# Spanish store view (store_id=8)
bin/magento config:set general/locale/code es_ES --scope=stores --scope-id=8
bin/magento config:set currency/options/default EUR --scope=stores --scope-id=8
bin/magento config:set general/country/default ES --scope=stores --scope-id=8
```

---

## 3. Database Queries

### Store Hierarchy
```sql
SELECT 
    s.store_id,
    s.code,
    s.name,
    s.is_active,
    sg.name AS store_group,
    sw.name AS website
FROM store s
JOIN store_group sg ON s.group_id = sg.group_id
JOIN store_website sw ON s.website_id = sw.website_id
ORDER BY s.store_id;
```

### Config Values per Store
```sql
-- All locale configs
SELECT path, value, scope, scope_id
FROM core_config_data
WHERE path LIKE 'general/locale%'
ORDER BY scope, scope_id;

-- Currency settings
SELECT path, value, scope, scope_id
FROM core_config_data
WHERE path LIKE 'currency%'
ORDER BY scope, scope_id;

-- Base URLs per store view
SELECT path, value, scope, scope_id
FROM core_config_data
WHERE path LIKE 'web/unsecure/base_url%'
ORDER BY scope, scope_id;
```

---

## 4. Theme Assignment

### Per Store View
Admin > Content > Design > Configuration

| Store View | Theme |
|------------|-------|
| English | Luma |
| French | Luma (override templates) |
| German | Luma (override templates) |
| Spanish | Luma (override templates) |

### CSS/JS Overrides
Create theme with:
```
app/design/frontend/Training/european/
├── registration.php
├── theme.xml
├── web/css/
│   └── source/
│       └── _extend.less  (store-specific styles)
└── Magento_Store/layout/
    └── default.xml  (store-specific layout)
```

---

## 5. Currency Setup

### Multi-Currency Configuration
```sql
-- Default currency (Global)
SELECT * FROM core_config_data WHERE path = 'currency/options/default';

-- Allowed currencies (Global)
SELECT * FROM core_config_data WHERE path = 'currency/options/allow';

-- Currency rates (auto-updated)
SELECT * FROM currency;
SELECT * FROM directory_currency_rate;
```

### Currency Rate Updates
```bash
bin/magento currency:import  # Import rates from ECB
bin/magento currency:rate:list  # List current rates
```

### Price Scope
```sql
-- Catalog Price Scope (Global vs Website)
SELECT * FROM core_config_data WHERE path = 'catalog/price/scope';
-- '0' = Global (same price everywhere)
-- '1' = Website (different price per website)
```

---

## 6. URL Rewrites

### Per Store View
```sql
-- URL rewrites per store
SELECT * FROM core_url_rewrite 
WHERE store_id = 6  -- French store
ORDER BY created_at DESC;

-- Product URL rewrites
SELECT * FROM core_url_rewrite 
WHERE entity_type = 'product' 
AND store_id = 6;
```

### Auto-Generated URLs
Magento auto-creates URL rewrites when:
1. Product is created
2. Category is created
3. URL key is changed

---

## 7. Category Scope

### Category Visibility
```sql
-- Categories visible in French store
SELECT c.entity_id, c.name, cw.website_id
FROM catalog_category_entity c
JOIN catalog_category_website cw ON c.entity_id = cw.category_id
WHERE cw.website_id = 1;  -- Default website
```

---

## 8. Product Scope

### Product Visibility
```sql
-- Products visible in French store
SELECT p.sku, p.name, ps.store_id, ps.visibility
FROM catalog_product_entity p
JOIN catalog_product_store ps ON p.entity_id = ps.product_id
WHERE ps.store_id = 6;  -- French store

-- Product website assignment
SELECT p.sku, pw.website_id
FROM catalog_product_entity p
JOIN catalog_product_website pw ON p.entity_id = pw.product_id
ORDER BY p.sku, pw.website_id;
```

---

## 9. Store Switcher

### Frontend Display
The store switcher shows all active store views for current website.

### URL Structure
```
Default:  http://localhost:8080/
French:   http://localhost:8080/french/
German:   http://localhost:8080/german/
Spanish:  http://localhost:8080/spanish/
```

---

## 10. Common Tasks

### 1. Create Store-Specific CMS Page
```
Admin > Content > Pages > Add New Page
→ Content tab: page content
→ Page in Websites tab: assign to specific store view
```

### 2. Store-Specific Block
```
Admin > Content > Blocks > Add New Block
→ assign to specific store view
```

### 3. Store-Specific Widget
```
Admin > Content > Widgets > Add Widget
→ assign to specific store view
```

---

## Next Steps
1. Configure locale per store view
2. Set currencies
3. Assign themes
4. Test store switcher
5. Create store-specific content
