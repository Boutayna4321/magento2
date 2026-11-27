# Magento 2 from Scratch — EAV vs Flat Data Model

> **Series**: [Learning Path](../README.md) → Phase 1, Article 5 of 5
> **Previous**: [`04-dependency-injection.md`](04-dependency-injection.md) — DI & Service Contracts
> **Next**: [`06-events-observers.md`](06-events-observers.md) — Events & Observers
> **Deep dive**: [`../magento-database.md`](../magento-database.md), [`../magento-database-queries.md`](../magento-database-queries.md)

---

Most frameworks store each entity as one row in a table. Magento stores products
and categories differently — using the **EAV model**. Understanding why is key
to writing correct Magento queries.

This article uses real SQL from a Magento 2.4.8 install.

---

## 1. The Flat Model (simple)

The **Flat model** is what you know from Laravel/Symfony:

```
catalog_product_entity
├── entity_id   (PK)
├── sku         (varchar)
├── name        (varchar)
├── price       (decimal)
└── ...
```

One table. One row per product. Columns are real database columns. Queries are
simple:

```sql
SELECT entity_id, sku, price FROM catalog_product_entity WHERE sku = 'tshirt';
```

**Used by**: orders, invoices, shipments, customers, customer addresses.

---

## 2. The EAV Model (flexible)

The **EAV model** stores attributes dynamically. Instead of columns, each
attribute is a row in a separate table.

There are **three** EAV tables for products:

### 2.1 `eav_attribute` — defines what attributes exist

```sql
SELECT attribute_id, attribute_code, backend_type, frontend_input
FROM eav_attribute
WHERE entity_type_id = 4  -- catalog_product
LIMIT 5;
```

| attribute_id | attribute_code | backend_type | frontend_input |
|--------------|----------------|--------------|----------------|
| 75 | name | varchar | text |
| 76 | sku | varchar | text |
| 79 | price | decimal | price |
| 83 | manufacturer | int | select |
| 93 | color | int | select |

- **`backend_type`** determines **which value table** stores the data.
- **`frontend_input`** determines the **admin widget** (text, select, etc.).

### 2.2 `eav_entity_attribute` — which sets use which attribute

```sql
SELECT attribute_set_id, attribute_group_id, attribute_id, sort_order
FROM eav_entity_attribute
WHERE attribute_id = 93;  -- color
```

| attribute_set_id | attribute_group_id | attribute_id | sort_order |
|-----------------|-------------------|--------------|------------|
| 4 | 8 | 93 | 20 |
| 9 | 25 | 93 | 20 |
| 10 | 35 | 93 | 20 |

`color` is available in **3 attribute sets** (Default, Top, Bottom). A product
only gets the attributes from its set.

### 2.3 Value tables — one per type

Magento has **five** pre-existing value tables (no new table per attribute):

| backend_type | Value table | Stores |
|-------------|-------------|--------|
| varchar | catalog_product_entity_varchar | Text up to 255 chars |
| int | catalog_product_entity_int | Integer or option IDs |
| text | catalog_product_entity_text | Long text (64KB) |
| decimal | catalog_product_entity_decimal | Numbers (price, weight) |
| datetime | catalog_product_entity_datetime | Dates |

```sql
-- color is an int (option ID)
SELECT e.sku, v.value AS color_option_id
FROM catalog_product_entity e
JOIN catalog_product_entity_int v ON v.entity_id = e.entity_id
    AND v.attribute_id = 93 AND v.store_id = 0
WHERE e.sku = '24-MB01';
```

---

## 3. Why EAV exists

EAV lets merchants **add custom attributes** to products without a developer
changing the database schema:

- Add "Warranty period" → stored in `_varchar`
- Add "Country of origin" (dropdown) → stored in `_int`
- Add "Care instructions" → stored in `_text`

No `ALTER TABLE`. No deployment. Just click "Save Attribute" in the admin.

### The tradeoff

| EAV | Flat |
|-----|------|
| Flexible attributes | Fast queries |
| Complex JOINs | Simple SELECTs |
| No schema changes per attribute | One column per attribute |

That is why **orders** (fixed fields) use Flat, and **products** (variable
fields) use EAV.

---

## 4. Store scoping and fallback

EAV values are **store-scoped** via `store_id`:

- `store_id = 0` → default value (applies to all stores unless overridden)
- `store_id = 1` → store view 1 override
- `store_id = 2` → store view 2 override

```sql
-- Always use COALESCE for fallback
SELECT e.sku,
       COALESCE(v_store.value, v_default.value) AS name
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_default
    ON v_default.entity_id = e.entity_id
    AND v_default.attribute_id = 75  -- name attribute
    AND v_default.store_id = 0
LEFT JOIN catalog_product_entity_varchar v_store
    ON v_store.entity_id = e.entity_id
    AND v_store.attribute_id = 75
    AND v_store.store_id = 1  -- store view 1
WHERE e.sku = '24-MB01';
```

If no store-specific value exists, the `store_id = 0` default is used.

---

## 5. Dropdown attributes — the subtlety

A dropdown attribute stores an **option ID** (integer), not the label text:

```sql
-- color attribute (backend_type = int)
SELECT v.value AS option_id
FROM catalog_product_entity_int v
WHERE v.entity_id = 1 AND v.attribute_id = 93;
-- → option_id = 52 (not "Red")
```

To get the label, you join **three** tables:

```sql
SELECT ov.value AS color_label
FROM catalog_product_entity_int ci
JOIN eav_attribute_option o ON o.option_id = ci.value AND o.attribute_id = ci.attribute_id
JOIN eav_attribute_option_value ov ON ov.option_id = o.option_id AND ov.store_id = 0
WHERE ci.entity_id = 1 AND ci.attribute_id = 93;
```

> **Always qualify the option join with `attribute_id`** — otherwise option ID 52
> from attribute A might resolve to a label from attribute B. See
> [`../magento-database-queries.md`](../magento-database-queries.md#4-dropdown-attribute)
> for the exercise.

---

## 6. Reading EAV safely — the collection API

Writing raw EAV SQL is error-prone. Magento provides the **Collection API**:

```php
$collection = $this->collectionFactory->create();
$collection->addAttributeToSelect(['name', 'color'])
    ->addAttributeToFilter('color', 'Red')
    ->setPageSize(10);

foreach ($collection as $product) {
    echo $product->getName() . ' — ' . $product->getData('color');
}
```

Behind the scenes, Magento builds the correct JOINs for the right value tables.

> **Never** use `foreach` with `->load()` — that is the **N+1 trap**. Use
> `getList()` or collections. See
> [`../magento-database-queries.md`](../magento-database-queries.md#9-the-n1-trap).

---

## 7. What you should remember

1. **Flat model** = one row, real columns. Simple SQL. Used for orders,
   invoices, customers.
2. **EAV model** = attributes as rows across multiple tables. Flexible but
   complex queries. Used for products, categories.
3. Five value tables (`_varchar`, `_int`, `_text`, `_decimal`, `_datetime`) —
   `backend_type` determines which one.
4. **Dropdowns** store option IDs, not labels — you need extra joins.
5. **Store scoping** uses `store_id = 0` for defaults, non-zero for overrides.
   Always `COALESCE(v_store, v_default)`.
6. Use the **Collection API** or **Repository** — never write EAV SQL by hand.

---

## 8. Where to go next

- **Previous**: [Dependency Injection](04-dependency-injection.md)
- **Phase 2** starts now: [HTML — Layout XML](../html/01-layout-xml.md)
- **Deep dive**: [`../magento-database.md`](../magento-database.md) — the
  complete EAV model with real table structures.
- **Exercises**: [`../magento-database-practice.md`](../magento-database-practice.md)
  — 17 hands-on exercises with runnable SQL.

---

*Sources: `src/vendor/magento/module-eav/Setup/EavSetup.php`,
`src/vendor/magento/module-catalog/Model/ResourceModel/Product/Collection.php`,
`src/vendor/magento/eav/etc/eav_attributes.xml`*
