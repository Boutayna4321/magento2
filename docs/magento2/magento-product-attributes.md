# Magento 2 — Creating a Product Attribute

> **The third piece.** [magento-database.md](magento-database.md) explains what
> EAV *is*. [magento-database-queries.md](magento-database-queries.md) explains
> how to *read* it. This document covers the third operation: **creating an
> attribute and making it appear in the product form** — by hand in the admin,
> and in code.

Everything below is verified against a Magento 2.4.8 install with the Luma
sample data (2 042 products, 8 product attribute sets). Tables, columns,
counts, menu paths and class names are real. Outputs shown for a **new**
attribute you create are marked *illustrative*, because they depend on the
`attribute_id` your install allocates.

---

## In a nutshell

**Two routes, and they are not the same** — see
[section 3](#3-which-route--admin-or-code) for the comparison and the decision
rule.

**Via the admin** (exploration, sandbox):

1. **Create it** — `Stores` → `Attributes` → `Product` → Add New.
2. **Put it in a set** — `Stores` → `Attributes` → `Attribute Sets`. Without
   this it is invisible.
3. **Fill it in** — open a product, type a value, save.

**Via code** (anything real): a data patch in your own module, applied with
`bin/magento setup:upgrade` — see
[section 8](#8-doing-it-in-code--the-data-patch). There is no
`bin/magento attribute:create`.

**Either way**, check the database: `eav_attribute` (1 row),
`catalog_eav_attribute` (1 row), `eav_entity_attribute` (1 per set), and the
matching `catalog_product_entity_*` value table.

---

## Table of Contents

1. [What you can add](#1-what-you-can-add)
2. [The five decisions](#2-the-five-decisions)
3. [Which route — admin or code?](#3-which-route--admin-or-code)
4. [Manual steps — the admin](#4-manual-steps--the-admin)
5. [What the database looks like afterwards](#5-what-the-database-looks-like-afterwards)
6. [Reading it back](#6-reading-it-back)
7. [Making it visible elsewhere](#7-making-it-visible-elsewhere)
8. [Doing it in code — the data patch](#8-doing-it-in-code--the-data-patch)
9. [Gotchas](#9-gotchas)
10. [Undo](#10-undo)
11. [Self-check](#11-self-check)

---

## 1. What you can add

Anything a store owner types into a product form. The limit is not the data
model — it is your imagination plus the input type you pick.

### Realistic examples

| Idea | Input type | Stores in | Notes |
|---|---|---|---|
| Warranty period in months | `text` | `_varchar` | Keep it numeric-looking, or use `text` anyway |
| Country of origin | `select` | `_int` | An **option id**, not a string |
| Care instructions | `textarea` | `_text` | Long free text; `varchar` would truncate at 255 |
| Refurbished | `boolean` | `_int` | `0` / `1` |
| Release date | `date` | `_datetime` | |
| Weight (shipped) | `weight` | `_decimal` | |
| Supplier reference | `text` | `_varchar` | Pair with `is_unique = Yes` to reject duplicates |
| Certification (CE, ISO…) | `multiselect` | `_text` | Comma-separated option ids |
| Fabric composition | `textarea` | `_text` | |
| Internal cost | `price` | `_decimal` | `Use in Layered Navigation` must stay off — see below |
| Fit / cut | `select` | `_int` | |
| Recycled packaging | `boolean` | `_int` | |
| Style code | `text` | `_varchar` | |
| Voltage | `select` | `_varchar` | Note: `varchar` + `select` is a valid combo |
| Assembly time (min) | `text` | `_varchar` | Numeric validation needs a custom model |
| Eco score | `select` | `_int` | |

For this install the pairs already in use are:

```
+-------------+----------------+----+
| backend_type | frontend_input | n  |
+-------------+----------------+----+
| text         | multiselect    | 13 |
| int          | select         | 10 |
| varchar      | select         |  8 |
| int          | boolean        |  8 |
| varchar      | text           |  7 |
| datetime     | date           |  6 |
| decimal      | price          |  5 |
+-------------+----------------+----+
```

### The input type is your most important choice

`frontend_input` decides the admin widget. It also **usually** decides
`backend_type`, and `backend_type` decides the table. Get it wrong and you
cannot change it later without a data migration.

| Input type you want | Pick `backend_type` | Value table | Stores |
|---|---|---|---|
| Text box | `varchar` | `catalog_product_entity_varchar` | Up to 255 chars |
| Text area | `text` | `catalog_product_entity_text` | 64 KB |
| Dropdown (admin-managed) | `int` | `catalog_product_entity_int` | An **option id** |
| Dropdown (static, from a class) | `varchar` | `catalog_product_entity_varchar` | The **literal value** |
| Multi-select | `text` | `catalog_product_entity_text` | Comma-separated option ids |
| Yes / No | `int` | `catalog_product_entity_int` | `0` / `1` |
| Price box | `decimal` | `catalog_product_entity_decimal` | |
| Date picker | `datetime` | `catalog_product_entity_datetime` | |
| Weight | `decimal` | `catalog_product_entity_decimal` | |

**The dropdown row is the trap.** Two different things look identical in the
admin:

- `Manufacturer` — the options live in `eav_attribute_option`, you manage them
  in the attribute editor. `backend_type = int`, the value is an option id.
- `status` — the options are hardcoded in
  `Magento\Catalog\Model\Product\Attribute\Source\Status`. There is no
  `source_model` field to fill in the UI, so Magento picks `varchar` and the
  value is `'1'` or `'2'`.

Same widget, different storage. Check `source_model` before you write a query:
see [the exercises in the lab](magento-database-practice.md#11-not-every-select-attribute-is-in-the-database).

---

## 2. The five decisions

Answer these before opening the form. Getting them wrong is the expensive kind
of wrong.

### 1. `attribute_code`

Lowercase, no spaces, no accents: `warranty_months`, not `Warranty Months`.
It becomes a PHP `getData('warranty_months')` key, a REST field name, a
GraphQL field name, and a column name in the value table. **It is permanent.**

### 2. `frontend_input` and `backend_type`

See the table above. Permanent too, in practice.

### 3. Scope

- **All Store Views** → values live at `store_id = 0` and apply everywhere.
- **Store View** → each store view gets its own value; the `store_id = 0`
  fallback is what makes non-overridden stores still work.

Start with **All Store Views**. It is one row per product, not N.

### 4. Required

`Required = Yes` means the product **cannot be saved without a value**.

On an install that already has 2 042 products, flipping an optional attribute
to required does not retro-fill anything — the products that were already
saved stay invalid. Test it on one product first.

### 5. Unique

`Unique = Yes` enforces a uniqueness check at save time. Use it for a
supplier reference or an EAN. It is application-level validation, **not** a
database constraint — a race between two concurrent saves can still slip a
duplicate through.

---

## 3. Which route — admin or code?

**Both work, and they are not equivalent.** The admin writes directly to the
database. Code writes to the database *and* leaves the recipe in git.

| | Admin panel | Data patch in a module |
|---|---|---|
| Works immediately | Yes | No — needs `setup:upgrade` |
| Reproducible on another environment | **No** | Yes |
| Reviewable in a pull request | **No** | Yes |
| Survives a reinstall | **No** | Yes |
| Choose which attribute sets get it | Yes, per set | Only with extra code — see below |
| Fine for | exploring, one-off sandbox | anything real |

**There is no CLI command.** Magento 2.4.8 ships exactly one attribute command:

```bash
docker exec magento2-php php bin/magento list | grep -i attribute
```

```
catalog:product:attributes:cleanup   Removes unused product attributes.
```

That one **deletes** unused attributes. There is no `attribute:create`. So
"do it from the command line" means writing a data patch — see
[section 8](#8-doing-it-in-code--the-data-patch).

### The decision

- **You are exploring, or this is a throwaway sandbox** → admin. Fastest, zero
  files, and you can see the field in the form immediately.
- **It is a real requirement** → data patch. Otherwise the next developer,
  the staging environment, or the fresh install simply will not have the
  attribute, and nobody will know why.

### Do not mix them

If you create `warranty_months` in the admin *and* ship a patch that creates
`warranty_months`, the patch is idempotent by `attribute_code`: on the next
`setup:upgrade` it will **update** the row your admin click created, keeping
the admin-assigned `attribute_id` but overwriting `label`, `input` and the
flags. Usually harmless, occasionally confusing.

Worse is the reverse. A patch with `'group' => 'Product Details'` adds the
attribute to **all 8 sets** — including the ones you never wanted. If you only
meant `Top`, see the next point.

### `addAttribute()` cannot target a single set

`EavSetup::addAttribute()` loops over **every** `eav_attribute_set` of the
entity type. Read it in the source:

```php
// src/vendor/magento/module-eav/Setup/EavSetup.php
if (!empty($attr['group']) || empty($attr['user_defined'])) {
    $sets = /* ALL sets of the entity type */;
    foreach ($sets as $set) {
        $this->addAttributeToSet($entityTypeId, $set['attribute_set_id'], ...);
    }
}
```

To pick sets explicitly, declare the attribute **without** `group`, then call
`addAttributeToSet()` yourself:

```php
$eavSetup->addAttribute(Product::ENTITY, 'warranty_months', [
    'type'         => 'varchar',
    'label'        => 'Warranty (months)',
    'input'        => 'text',
    'required'     => false,
    'sort_order'   => 100,
    'visible'      => true,
    'user_defined' => true,
    'system'       => false,
]);

foreach (['Top', 'Bottom', 'Bag'] as $setName) {
    $eavSetup->addAttributeToSet(
        Product::ENTITY,       // entity type
        $setName,              // set name OR set id
        'Product Details',     // group name OR group id — auto-created if absent
        'warranty_months',     // attribute code OR attribute id
        100                    // sort_order
    );
}
```

`addAttributeToSet()` is idempotent too — it checks for the
`(attribute_set_id, attribute_id)` pair first. The group is created in the set
if it does not exist, which is why the source calls `addAttributeGroup()`
before it.

### One-off values without a form

If you only need to *set* a value in bulk and never expose the field, skip both
routes. Load the Product Repository, set the attribute, save. The attribute
itself still has to exist, though.

---

## 4. Manual steps — the admin

Admin URL for this install: **`http://localhost:8080/admin`**

### Step 1 — create the attribute

`Stores` → `Attributes` → `Product`

Click **Add New Product Attribute**.

| Field | What to type for a `warranty_months` example |
|---|---|
| Attribute Code | `warranty_months` |
| Scope | All Store Views |
| Input Type | Text |
| Input Validator | *(leave empty)* |
| Frontend Label | `Warranty (months)` |
| Default Value | *(leave empty)* |
| Values Required | No |
| Unique | No |
| Scope | All Store Views |

Then the **Advanced Attribute Properties** section:

| Field | Value |
|---|---|
| Store View Options | *leave empty* |
| Unique | No |
| Use in Layered Navigation | No |
| Use in Product Search | No |
| Use in Catalog Rules Product Listing | No |
| Use in Product Attributes Sorting | No |
| Visible on Catalog Pages | Yes |
| Visible on Product Pages | Yes |
| Visible in Catalog Search | Yes |
| Comparable on Storefront | No |

The exact rows in this install for comparison:

```
+--------------+----------------+--------------+----------------+--------------+---------------+---------------+------------------+------------------+---------------+
| attribute_id | entity_type_id | attribute_code | backend_type | frontend_input | frontend_label | is_required | is_unique | is_user_defined | default_value |
+--------------+----------------+--------------+--------------+----------------+---------------+---------------+------------------+------------------+---------------+
|           81 |              4 | cost          | decimal      | price          | Cost           |           0 |         0 |               1 | NULL          |
|           83 |              4 | manufacturer  | int          | select         | Manufacturer   |           0 |         0 |               1 | NULL          |
|           93 |              4 | color         | int          | select         | Color          |           0 |         0 |               1 | 49            |
|          145 |              4 | eco_collection | int         | boolean        | Eco Collection |           0 |         0 |               1 |               |
+--------------+----------------+--------------+--------------+----------------+---------------+---------------+------------------+------------------+---------------+
```

Click **Save Attribute**.

### Step 2 — the attribute is not visible yet

Saving the attribute only defines it. It exists in the database, and it is in
**zero** attribute sets, so it appears on no product form.

Verify:

```sql
SELECT ea.attribute_id, ea.attribute_code, ea.backend_type, ea.frontend_input,
       ea.is_user_defined
FROM eav_attribute ea
JOIN eav_entity_type et ON et.entity_type_id = ea.entity_type_id
WHERE et.entity_type_code = 'catalog_product'
  AND ea.attribute_code = 'warranty_months';

SELECT COUNT(*) AS rows_in_eav_entity_attribute
FROM eav_entity_attribute eea
JOIN eav_attribute ea ON ea.attribute_id = eea.attribute_id
WHERE ea.attribute_code = 'warranty_months';
```

*Illustrative output — the `attribute_id` is whatever your install allocates
(the next free id, 157 in this one):*

```
+--------------+-----------------+--------------+----------------+-----------------+
| attribute_id | attribute_code  | backend_type | frontend_input | is_user_defined |
+--------------+-----------------+--------------+----------------+-----------------+
|          157 | warranty_months | varchar      | text           |               1 |
+--------------+-----------------+--------------+----------------+-----------------+

+------------------------------+
| rows_in_eav_entity_attribute |
+------------------------------+
|                            0 |
+------------------------------+
```

Zero rows. That is the normal state after step 1.

### Step 3 — put it in the attribute sets

`Stores` → `Attributes` → `Attribute Sets`

Choose a set on the left, e.g. **Top**. Drag your attribute from
`Product Details` to the group you want it in — or use *Add New Group* to
create one first. Click **Save**.

Which sets should get it? Look at how many products each one holds:

```sql
SELECT s.attribute_set_id, s.attribute_set_name, COUNT(e.entity_id) AS products
FROM eav_attribute_set s
LEFT JOIN catalog_product_entity e ON e.attribute_set_id = s.attribute_set_id
WHERE s.entity_type_id = 4
GROUP BY s.attribute_set_id, s.attribute_set_name
ORDER BY s.attribute_set_id;
```

```
+------------------+--------------------+----------+
| attribute_set_id | attribute_set_name | products |
+------------------+--------------------+----------+
|                4 | Default            |        2 |
|                9 | Top                |     1462 |
|               10 | Bottom             |      532 |
|               11 | Gear               |       20 |
|               12 | Sprite Stasis Ball |        9 |
|               13 | Sprite Yoga Strap  |        3 |
|               14 | Downloadable       |        0 |
|               15 | Bag                |       14 |
+------------------+--------------------+----------+
```

Adding the attribute to **Top** exposes it on 1 462 products. Adding it to
**Downloadable** exposes it on none — the set exists, no product uses it.

Compare with how a shipped attribute is spread. `cost` is in all 8 sets;
`color` is in the 6 that have something colourful in them:

```sql
SELECT eea.attribute_set_id, s.attribute_set_name, eea.attribute_group_id,
       g.attribute_group_name, eea.sort_order
FROM eav_entity_attribute eea
JOIN eav_attribute_set s ON s.attribute_set_id = eea.attribute_set_id
JOIN eav_attribute_group g ON g.attribute_group_id = eea.attribute_group_id
JOIN eav_attribute ea ON ea.attribute_id = eea.attribute_id
WHERE ea.attribute_code = 'cost'
ORDER BY eea.attribute_set_id;
```

```
+------------------+--------------------+---------------------+----------------------+------------+
| attribute_set_id | attribute_set_name | attribute_group_id | attribute_group_name | sort_order |
+------------------+--------------------+---------------------+----------------------+------------+
|                4 | Default            |                   8 | Product Details      |          6 |
|                9 | Top                |                  25 | Product Details      |          6 |
|               10 | Bottom             |                  35 | Product Details      |          6 |
|               11 | Gear               |                  45 | Product Details      |          6 |
|               12 | Sprite Stasis Ball |                  55 | Product Details      |          6 |
|               13 | Sprite Yoga Strap  |                  65 | Product Details      |          6 |
|               14 | Downloadable       |                  75 | Product Details      |          6 |
|               15 | Bag                |                  85 | Product Details      |          6 |
+------------------+--------------------+---------------------+----------------------+------------+
```

`sort_order` is where the field sits inside its group. Equal values across
sets is normal — it is a position, not a global index.

### Step 4 — enter a value

`Catalog` → `Products`, open any product in that set. The new field is at the
bottom of the group you put it in. Save.

```sql
SELECT e.sku, v.value
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar v
     ON v.entity_id = e.entity_id
    AND v.attribute_id = 157
    AND v.store_id = 0
WHERE e.sku = '24-MB01';
```

```
+---------+-------+
| sku     | value |
+---------+-------+
| 24-MB01 | 24    |
+---------+-------+
```

*Illustrative output.* One row, `store_id = 0`. That is the whole storage
model.

To make the id portable across environments, never hardcode it — resolve it
first, the way
[magento-database-queries.md §3.1](magento-database-queries.md#31-resolving-an-attribute_id)
does:

```sql
SELECT attribute_id FROM eav_attribute
WHERE entity_type_id = 4 AND attribute_code = 'warranty_months';
```

### Step 5 — flush the caches

If the form still does not show the field:

```bash
docker exec magento2-php php bin/magento cache:clean
```

In production use `setup:upgrade` or `setup:static-content:deploy -j 8`, not a
full compile, just for an attribute.

---

## 5. What the database looks like afterwards

Four tables. This is the complete footprint of "I added a product attribute".

### `eav_attribute` — one row

Verified with `SHOW CREATE TABLE eav_attribute`:

```
+--------------+------------------+------+-----+---------+-------+
| attribute_id | smallint unsigned | NO   | PRI | NULL    | auto_increment
| entity_type_id | smallint unsigned | NO | MUL | 0       |
| attribute_code | varchar(255)  | NO   |     | NULL    |
| backend_type   | varchar(8)   | NO   |     | static  |
| frontend_input | varchar(50)  | YES  | MUL | NULL    |
| frontend_label | varchar(255) | YES  |     | NULL    |
| source_model   | varchar(255) | YES  |     | NULL    |
| is_required  | smallint unsigned | NO |     | 0       |
| is_user_defined | smallint unsigned | NO |   | 0       |
| is_unique    | smallint unsigned | NO |     | 0       |
| default_value | text         | YES  |     | NULL    |
+--------------+------------------+------+-----+---------+-------+
```

`entity_type_id = 4` is `catalog_product`. The full type list:

```sql
SELECT entity_type_id, entity_type_code FROM eav_entity_type;
```

### `eav_entity_attribute` — one row per set

```
+---------------------+----------------+------------------+---------------------+--------------+------------+
| entity_attribute_id | entity_type_id | attribute_set_id | attribute_group_id | attribute_id | sort_order |
+---------------------+----------------+------------------+---------------------+--------------+------------+
|                 583 |              4 |               11 |                  50 |           93 |       1017 |
+---------------------+----------------+------------------+---------------------+--------------+------------+
```

(`color` in set `Gear`.) The **join between this table and the product is
`attribute_id`** — that is the field that makes "this set has this attribute"
queryable without touching the product table at all.

### `catalog_eav_attribute` — one row

Created together with the attribute, holding the storefront, grid, search and
sorting flags. Covered in [section 7](#7-making-it-visible-elsewhere).

### The value table — one row per product that has a value

`catalog_product_entity_varchar` for a `varchar` attribute, `_int`, `_decimal`,
`_text` or `_datetime` otherwise. No table is created: the five value tables
already exist and are shared by all attributes of that type.

This is the point of the whole model. Compare the schema change with adding a
column to `sales_order`: **zero ALTER, zero table, zero code deployment.**

### Optional tables, for dropdowns only

| Table | Rows created when |
|---|---|
| `eav_attribute_option` | You add options in the *Storefront Options* tab |
| `eav_attribute_option_value` | Same, one row per option per store view |

---

## 6. Reading it back

The pattern from the lab, unchanged. Substitute your `attribute_id` and table.

### Plain text / decimal / datetime attribute

```sql
SELECT e.sku, COALESCE(v_store.value, v_default.value) AS warranty_months
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_store
     ON v_store.entity_id = e.entity_id
    AND v_store.attribute_id = 157
    AND v_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar v_default
     ON v_default.entity_id = e.entity_id
    AND v_default.attribute_id = 157
    AND v_default.store_id = 0
WHERE e.sku = '24-MB01';
```

### Dropdown attribute

Add two joins, and **qualify the option join with `attribute_id`** — otherwise
the option id can silently resolve to another attribute's label:

```sql
SELECT e.sku, ov.value AS origin
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id
    AND ci.attribute_id = 158
JOIN eav_attribute_option o
     ON o.option_id = ci.value
    AND o.attribute_id = ci.attribute_id
JOIN eav_attribute_option_value ov
     ON ov.option_id = o.option_id
    AND ov.store_id = 0
WHERE e.sku = '24-MB01';
```

### Filterable in the Collection API

The moment the attribute is in an attribute set, the Product Collection can
filter on it. This is the reason EAV is worth it:

```php
/** @var \Magento\Catalog\Api\ProductRepositoryInterface $repository */
$collection = $this->collectionFactory->create();
$collection->addAttributeToSelect(['name', 'warranty_months', 'origin'])
    ->addAttributeToFilter('origin', 'MA')
    ->setPageSize(10);

foreach ($collection as $product) {
    echo $product->getName() . ' — ' . $product->getData('origin');
}
```

Do **not** loop with `foreach` calling `load()`. That is the N+1 trap described
in [magento-database-queries.md §9.4](magento-database-queries.md#94-the-n1-trap).

---

## 7. Making it visible elsewhere

Creating the attribute is only the beginning. The switches that decide where it
shows up live in a **fourth** table you have not met yet:
**`catalog_eav_attribute`**, one row per product attribute.

```sql
DESCRIBE catalog_eav_attribute;
```

```
+-------------------------------+------------------+------+-----+---------+-------+
| Field                         | Type             | Null | Key | Default | Extra |
+-------------------------------+------------------+------+-----+---------+-------+
| attribute_id                  | smallint unsigned | NO   | PRI | NULL    |       |
| frontend_input_renderer       | varchar(255)     | YES  |     | NULL    |       |
| is_global                     | smallint unsigned | NO   |     | 1       |       |
| is_visible                    | smallint unsigned | NO   |     | 1       |       |
| is_searchable                 | smallint unsigned | NO   |     | 0       |       |
| is_filterable                 | smallint unsigned | NO   |     | 0       |       |
| is_comparable                 | smallint unsigned | NO   |     | 0       |       |
| is_visible_on_front           | smallint unsigned | NO   |     | 0       |       |
| used_in_product_listing       | smallint unsigned | NO   | MUL | 0       |       |
| used_for_sort_by              | smallint unsigned | NO   | MUL | 0       |       |
| is_used_in_grid               | smallint unsigned | NO   |     | 0       |       |
| is_visible_in_grid            | smallint unsigned | NO   |     | 0       |       |
| is_filterable_in_grid         | smallint unsigned | NO   |     | 0       |       |
| position                      | int              | NO   |     | 0       |       |
| search_weight                 | float            | NO   |     | 1       |       |
+-------------------------------+------------------+------+-----+---------+-------+
```

Compare the shipped attributes:

```sql
SELECT ca.attribute_id, ea.attribute_code, ca.is_filterable, ca.is_searchable,
       ca.used_in_product_listing, ca.used_for_sort_by, ca.is_used_in_grid
FROM catalog_eav_attribute ca
JOIN eav_attribute ea ON ea.attribute_id = ca.attribute_id
WHERE ea.attribute_code IN ('name','color','cost','manufacturer')
  AND ea.entity_type_id = 4;
```

```
+--------------+---------------+--------------+--------------+-------------------------+------------------+-----------------+
| attribute_id | attribute_code | is_filterable | is_searchable | used_in_product_listing | used_for_sort_by | is_used_in_grid |
+--------------+---------------+--------------+--------------+-------------------------+------------------+-----------------+
|           73 | name           |            0 |            1 |                       1 |                1 |               0 |
|           81 | cost          |            0 |            0 |                       0 |                0 |               1 |
|           83 | manufacturer  |            1 |            1 |                       0 |                0 |               1 |
|           93 | color         |            1 |            0 |                       1 |                0 |               1 |
+--------------+---------------+--------------+--------------+-------------------------+------------------+-----------------+
```

`color` is filterable and appears in the product listing. `cost` is neither —
but it is a grid column. Note `cost` is a **price** attribute; it is
deliberately not filterable, because filtering on a value the customer cannot
see is a data leak.

The mapping between the admin switches and these columns:

| Surface | Admin switch | Column |
|---|---|---|
| **Product grid** column | *Storefront Properties* → **Show in Product List** | `is_used_in_grid`, `is_visible_in_grid`, `position` |
| **Product form** | Being in an attribute set | *(not here — that is `eav_entity_attribute`)* |
| **Layered navigation** | **Use in Layered Navigation** | `is_filterable` |
| **Layered navigation** sub-filter | **Use in Layered Navigation** + `is_filterable_in_search` | `is_filterable`, `is_filterable_in_search` |
| **Search results** | **Use in Product Search** | `is_searchable`, `search_weight` |
| **Compare** | **Comparable on Storefront** | `is_comparable` |
| **Sort by** | **Use for Product Sorting** | `used_for_sort_by` |
| **Catalog rules** | **Use in Catalog Rules Product Listing** | `used_in_product_listing` |
| **REST / GraphQL** | Automatic, once the attribute exists | *(nothing to set)* |

Anything with `is_filterable` or `is_searchable` at `1` now has an entry in
`catalog_product_index_attribute` — which is why turning one on requires a
reindex, and turning one off does not.

---

## 8. Doing it in code — the data patch

The admin is fine for exploration. For anything that must be reproducible —
another environment, a second developer, a deploy — use a **data patch**.

Magento 2.4.8 discovers patches by **file path**. There is no `PatchList` to
register.

### Files

```
src/app/code/Acme/Catalog/
├── registration.php
└── Setup/
    └── Patch/
        └── Data/
            └── AddWarrantyProductAttribute.php
```

### The patch

```php
<?php
declare(strict_types=1);

namespace Acme\Catalog\Setup\Patch\Data;

use Magento\Catalog\Model\Product;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Store\Model\Store;

class AddWarrantyProductAttribute implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly EavSetupFactory $eavSetupFactory
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup->addAttribute(Product::ENTITY, 'warranty_months', [
            'type'         => 'varchar',
            'label'        => 'Warranty (months)',
            'input'        => 'text',
            'required'     => false,
            'sort_order'   => 100,
            'visible'      => true,
            'user_defined' => true,
            'system'       => false,
            'group'        => 'Product Details',
        ]);
    }
}
```

`addAttribute()` is **idempotent**: if `attribute_code` already exists it
updates the row, and the `group` key adds the attribute to **every** product
attribute set. Do not hand-write the `eav_entity_attribute` inserts.

> **The `'group'` key is not "put it in the Product Details group".** It is
> "put it in the Product Details group **of every set**". If you only want
> three sets, drop `group` and call `addAttributeToSet()` in a loop — see
> [section 3](#3-which-route--admin-or-code).

### Run it

```bash
docker exec magento2-php php bin/magento setup:upgrade
```

Magento records it in `patch_list` so it never runs twice:

```sql
SELECT patch_name FROM patch_list
WHERE patch_name LIKE '%AddWarrantyProductAttribute%';
```

**Never** edit an applied patch. Change the `getAliases()` return value
instead — that is exactly what it is for.

### `setup:upgrade` is not optional here

Without it, the cache still serves the old attribute list, and on a
production-mode install you get a `Static content deployment error`. The
warning about running it in development mode is expected; use
`--keep-generated` if you are mid-way through other work:

```bash
docker exec magento2-php php bin/magento setup:upgrade --keep-generated
```

---

## 9. Gotchas

### The attribute is saved but invisible

99 % of the time it is not in the attribute set. Check
`eav_entity_attribute` (section 4, step 2). Saving the attribute does **not**
add it to any set.

### `catalog_product_entity` still has no column

This is correct. If you were expecting a `warranty_months` column there, you
misread the model — see
[magento-database.md §2](magento-database.md#2-two-data-models-flat-tables-and-eav).

### A dropdown resolves to another attribute's label

`o.option_id = ci.value` without `o.attribute_id` gives a **wrong string, not
a NULL**. Demonstrated in the lab:
[exercise 11](magento-database-practice.md#11-not-every-select-attribute-is-in-the-database).

### Forcing required on existing products

Flipping `is_required` to `1` does not backfill. The 2 042 products that
already exist now fail validation. Fill the column first, or leave it optional
and validate in code.

### Unique is not a database constraint

`is_unique` is checked by the save controller. Two concurrent saves can both
pass. If you truly need it, add a real unique index in `db_schema.xml` — and
then you must handle the duplicates that already exist.

### Forgetting the store fallback

`WHERE store_id = 1` returns nothing on a fresh install. Always the
`COALESCE(v_store, v_default)` pair.

### `is_used_in_grid` on a slow attribute

Every grid column is a joined EAV attribute. Ten grid columns on a 50 000-row
catalog is a slow grid. Choose three or four.

### Reindexing is not needed for a plain attribute

Adding an attribute does not require `indexer:reindex`. You need it if the
attribute is `is_filterable` or `is_searchable`, because those back
`catalog_product_index_*`.

---

## 10. Undo

### From the admin

`Stores` → `Attributes` → `Product` → row menu → **Delete**. Then remove it
from each attribute set, or the set keeps a dangling reference.

### From the CLI

```bash
docker exec magento2-php php bin/magento catalog:product:attributes:cleanup
```

```
Removes unused product attributes.
Usage:
  catalog:product:attributes:cleanup
```

It drops product attributes with no values and no set membership — the exact
leftovers of a half-finished experiment.

### What is left behind

Deleting the attribute leaves rows in the value table. Check and clean:

```sql
SELECT COUNT(*) FROM catalog_product_entity_varchar  WHERE attribute_id = 157;
SELECT COUNT(*) FROM eav_entity_attribute           WHERE attribute_id = 157;
SELECT COUNT(*) FROM catalog_eav_attribute           WHERE attribute_id = 157;
SELECT COUNT(*) FROM eav_attribute                  WHERE attribute_id = 157;
```

On a lab install, deleting all four is safe. On production, take a dump first:

```bash
docker exec magento2-mysql mysqldump -umagento -pmagento123 magento2 \
  eav_attribute eav_entity_attribute catalog_eav_attribute \
  eav_attribute_option eav_attribute_option_value \
  > /tmp/$(date +%F)-eav-backup.sql
```

---

## 11. Self-check

1. You save a new attribute and it does not appear on any product form. What
   table is missing a row, and which admin screen fills it?
2. Name the four tables an attribute creation writes to.
3. `frontend_input = select` with no `source_model`. What does
   `backend_type` become, and what is stored in the value table?
4. Your dropdown renders "Male" for a product's `origin`. What is missing from
   the join?
5. Why does adding a dropdown attribute not create a new value table?
6. You add an attribute to set `Downloadable`. How many products see it? How do
   you find that out without opening the admin?
7. What is the only step that cannot be repeated safely, and what should you do
   instead?
8. Your layered navigation filter never appears. Which table row is missing?
9. Why is `cost` a grid column but not filterable?
10. You created an attribute in the admin, and a colleague's install does not
    have it. What is missing from the repository, and what is the fix?
11. A data patch with `'group' => 'Product Details'` ends up in all 8 attribute
    sets. Is that a bug or the intended behaviour? How do you restrict it?

---

## Related

- [magento-database.md §3](magento-database.md#3-the-eav-model-in-detail) — the three EAV layers
- [magento-database.md §3.5](magento-database.md#35-attribute-sets) — attribute sets
- [magento-database-queries.md §3](magento-database-queries.md#3-eav-queries) — the EAV query patterns
- [magento-database-queries.md §7](magento-database-queries.md#7-creating-a-table) — declarative schema and data patches
- [magento-database-practice.md](magento-database-practice.md) — the 17-exercise lab
- [magento-cli.md](magento-cli.md) — `setup:upgrade`, indexers, cache
- [magento-multistore.md](magento-multistore.md) — what Scope really changes

*Last updated: 2026-09-28*
