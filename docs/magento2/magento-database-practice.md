# Magento 2 — EAV Practice Lab

> **Objective**: build the EAV model from one product upwards. Each exercise
> adds exactly one new idea, and every "naive" query is a real mistake you
> would otherwise make in production without noticing.
>
> **Simple → complex.** Exercises 1-3 find the data. 4-6 read one value. 7-9
> add attributes and scale. 10-11 cover the structure. 12-16 go back to flat
> tables and errors.

Everything runs **read-only** against the live install. No `INSERT`, no
`UPDATE`, no `DELETE`.

---

## How to use it

```bash
./scripts/db-practice.sh              # everything
./scripts/db-practice.sh 6            # one exercise
./scripts/db-practice.sh B            # one part (A–G)
./scripts/db-practice.sh 6 --answers  # reveal the takeaway of each step
```

Each step prints the query, the **real output**, and — with `--answers` — one
sentence explaining what you just saw.

**Do not skip ahead.** Exercise 6 is useless if you did not run exercise 5 and
saw zero rows. The gap between the naive query and the fixed one *is* the
lesson.

---

## The map

```
PART A  Décrire           1-3   Where does a product name actually live?
PART B  Un attribut       4-6   Read one EAV value, and the store trap
PART C  Plusieurs attrs   7-9   Several attributes, options, many products
PART D  Structure        10-11  Attribute sets, static option sources
PART E  Quitter l'EAV    12-14  Back to flat tables and reports
PART F  Erreurs         15-16  ONLY_FULL_GROUP_BY, index dimensions
PART G  Bonus              17   Open questions
```

---

# PART A — Décrire

## 1. The surprise

A product has a name, a price, a colour, a description. Open the product table.

```sql
DESCRIBE catalog_product_entity;
```

```
+------------------+-------------------+------+-----+-------------------+-----------------------------------------------+
| Field            | Type              | Null | Key | Default           | Extra                                         |
+------------------+-------------------+------+-----+-------------------+-----------------------------------------------+
| entity_id        | int unsigned      | NO   | PRI | NULL              | auto_increment                                |
| attribute_set_id | smallint unsigned | NO   | MUL | 0                 |                                               |
| type_id          | varchar(32)       | NO   |     | simple            |                                               |
| sku              | varchar(64)       | NO   | MUL | NULL              |                                               |
| has_options      | smallint          | NO   |     | 0                 |                                               |
| required_options | smallint unsigned | NO   |     | 0                 |                                               |
| created_at       | timestamp         | NO   |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED                             |
| updated_at       | timestamp         | NO   |     | CURRENT_TIMESTAMP | DEFAULT_GENERATED on update CURRENT_TIMESTAMP |
+------------------+-------------------+------+-----+-------------------+-----------------------------------------------+
```

**Eight columns. No `name`, no `price`, no `color`.**

They are not missing, and they are not broken. `catalog_product_entity` holds
*identity only* — the product exists, here is its SKU, here is its type. Every
descriptive attribute lives somewhere else.

This one observation explains the entire EAV model. Everything below is a
consequence of it.

---

## 2. Find the attribute

If `name` is not a column, it must be a row in a table. Find it:

```sql
SELECT attribute_id, entity_type_id
FROM eav_attribute
WHERE attribute_code = 'name';
```

```
+--------------+----------------+
| attribute_id | entity_type_id |
+--------------+----------------+
|           45 |              3 |
|           73 |              4 |
+--------------+----------------+
```

**Two rows.** `name` is not unique. Which entity are those? Join the type:

```sql
SELECT ea.attribute_id, ea.attribute_code, et.entity_type_code
FROM eav_attribute ea
JOIN eav_entity_type et ON et.entity_type_id = ea.entity_type_id
WHERE ea.attribute_code = 'name';
```

```
+--------------+----------------+------------------+
| attribute_id | attribute_code | entity_type_code |
+--------------+----------------+------------------+
|           45 | name           | catalog_category |
|           73 | name           | catalog_product  |
+--------------+----------------+------------------+
```

> ### The rule that matters most
> **`attribute_code` is unique per entity type, never globally.**
>
> Hardcoding `attribute_id = 73` is wrong on every installation that is not
> this one. And a lookup without the `entity_type` join can return the
> **category's** `name` when you wanted the **product's** — then your query
> silently returns `NULL` with no error anywhere.

This is the single most common EAV bug. It is why every query in the rest of
this lab carries the `eav_entity_type` join.

---

## 3. Find the value table

We have the `attribute_id`. Which table holds the value?

```sql
SELECT ea.attribute_code, ea.backend_type, ea.frontend_input
FROM eav_attribute ea
JOIN eav_entity_type et ON et.entity_type_id = ea.entity_type_id
WHERE et.entity_type_code = 'catalog_product'
  AND ea.attribute_code IN ('name','description','price','color','status')
ORDER BY ea.attribute_id;
```

```
+----------------+--------------+----------------+
| attribute_code | backend_type | frontend_input |
+----------------+--------------+----------------+
| name           | varchar      | text           |
| description    | text         | textarea       |
| price          | decimal      | price          |
| color          | int          | select         |
| status         | int          | select         |
+----------------+--------------+----------------+
```

`backend_type` routes to one of five tables. The table name is always
`<entity_table>_<backend_type>`:

| `backend_type` | Value table |
|---|---|
| `varchar` | `catalog_product_entity_varchar` |
| `int` | `catalog_product_entity_int` |
| `decimal` | `catalog_product_entity_decimal` |
| `text` | `catalog_product_entity_text` |
| `datetime` | `catalog_product_entity_datetime` |

`frontend_input` is the admin widget (`text`, `textarea`, `price`, `select`) —
useful for rendering a form, irrelevant to storage.

**You now have everything.** Three facts: `attribute_id = 73`,
`backend_type = varchar`, table `catalog_product_entity_varchar`. Part B uses
them.

---

# PART B — Un attribut

## 4. The first successful read

```sql
SELECT e.entity_id, e.sku, v.value AS name
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar v
     ON v.entity_id    = e.entity_id
    AND v.attribute_id = 73
    AND v.store_id     = 0
WHERE e.sku = '24-MB01';
```

```
+------------+---------+------------------+
| entity_id | sku     | name             |
+------------+---------+------------------+
|          1 | 24-MB01 | Joust Duffle Bag |
+------------+---------+------------------+
```

One product, one name. Note the join has **three** conditions, not two:

```
v.entity_id = e.entity_id      which product
v.attribute_id = 73            which attribute
v.store_id = 0                 which store view
```

Remove `store_id` and you get one row per store view that ever overrode the
value. Keep it and you get exactly one.

---

## 5. The trap: the same query for a real store view

Change `store_id = 0` to `store_id = 1` — a real store view.

```sql
SELECT e.sku, v.value AS name
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar v
     ON v.entity_id = e.entity_id AND v.attribute_id = 73 AND v.store_id = 1
WHERE e.sku = '24-MB01';
```

```
(no rows — and no error)
```

**Zero rows. No error, no warning.** Now find out why:

```sql
SELECT store_id, COUNT(*) AS n
FROM catalog_product_entity_varchar
WHERE attribute_id = 73
GROUP BY store_id;
```

```
+----------+------+
| store_id | n    |
+----------+------+
|        0 | 2042 |
+----------+------+
```

All 2 042 product names live at `store_id = 0`. **Not one** has been overridden
for store view 1.

> ### What `store_id = 0` means
>
> It is the **admin / default scope**. It does not mean "store 0". It holds the
> value that applies to *every* store view until somebody changes it.
>
> A `store_id = N` row only exists if someone explicitly edited that value in
> store view N. On a fresh install there are almost none — which is why
> forgetting the fallback breaks nearly everything while still working in your
> head.

This is the second half of the EAV model. `store_id` is not a filter, it is a
**three-level lookup**.

---

## 6. The fix: the store fallback

Magento reads an attribute like this:

```
try (entity, attribute, requested_store)
fall back to (entity, attribute, 0)
return null if neither exists
```

In SQL: two `LEFT JOIN`s and a `COALESCE`.

```sql
SELECT e.sku, COALESCE(v_store.value, v_default.value) AS name
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_store
     ON v_store.entity_id    = e.entity_id
    AND v_store.attribute_id = 73
    AND v_store.store_id     = 1
LEFT JOIN catalog_product_entity_varchar v_default
     ON v_default.entity_id    = e.entity_id
    AND v_default.attribute_id = 73
    AND v_default.store_id     = 0
WHERE e.sku = '24-MB01';
```

```
+---------+------------------+
| sku     | name             |
+---------+------------------+
| 24-MB01 | Joust Duffle Bag |
+---------+------------------+
```

`COALESCE` is doing the whole fallback. If the store view has an override,
`v_store.value` wins; otherwise `v_default.value` fills in.

The two `store_id` columns make the mechanism visible:

```sql
SELECT e.sku,
       v_store.store_id                          AS tried,
       v_default.store_id                        AS fell_back_to,
       COALESCE(v_store.value, v_default.value)  AS name
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_store
     ON v_store.entity_id = e.entity_id AND v_store.attribute_id = 73 AND v_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar v_default
     ON v_default.entity_id = e.entity_id AND v_default.attribute_id = 73 AND v_default.store_id = 0
WHERE e.sku = '24-MB01';
```

```
+---------+-------+--------------+------------------+
| sku     | tried | fell_back_to | name             |
+---------+-------+--------------+------------------+
| 24-MB01 |  NULL |            0 | Joust Duffle Bag |
+---------+-------+--------------+------------------+
```

`tried` is `NULL` — the store view had nothing — so the value came from
`store_id = 0`.

> ### This is THE EAV pattern. Memorise it.
>
> For **any** EAV attribute, any entity:
>
> ```
> LEFT JOIN <entity_table>_<backend_type> v_store
>        ON v_store.entity_id    = e.entity_id
>       AND v_store.attribute_id = <id>
>       AND v_store.store_id     = <requested>
> LEFT JOIN <entity_table>_<backend_type> v_default
>        ON v_default.entity_id    = e.entity_id
>       AND v_default.attribute_id = <id>
>       AND v_default.store_id     = 0
> ...
> COALESCE(v_store.value, v_default.value)
> ```
>
> Change the table and the id. Nothing else.

The same fallback exists in PHP —
`Magento\Eav\Model\Entity\Attribute\AbstractAttribute` does it for you, which
is why the Product Collection never shows this bug.

---

# PART C — Plusieurs attributs

## 7. A second attribute, a different table

`name` is `varchar`. `price` is `decimal`. **Different table**, same shape:

```sql
SELECT e.sku,
       COALESCE(n_store.value, n_default.value) AS name,
       p.value                                 AS price
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar n_store
     ON n_store.entity_id = e.entity_id AND n_store.attribute_id = 73 AND n_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar n_default
     ON n_default.entity_id = e.entity_id AND n_default.attribute_id = 73 AND n_default.store_id = 0
LEFT JOIN catalog_product_entity_decimal p
     ON p.entity_id = e.entity_id AND p.attribute_id = 77 AND p.store_id = 0
WHERE e.sku = '24-MB01';
```

```
+---------+------------------+-----------+
| sku     | name             | price     |
+---------+------------------+-----------+
| 24-MB01 | Joust Duffle Bag | 34.000000 |
+---------+------------------+-----------+
```

**One `LEFT JOIN` per attribute.** The join conditions never change. Only the
table name (`_varchar` vs `_decimal`) and the `attribute_id` (73 vs 77) do.

This is the reason EAV queries look enormous: they are N identical joins.

---

## 8. A select attribute: the value is not the label

`color` is `frontend_input = select`. Read the raw value first:

```sql
SELECT e.sku, ci.value AS raw_value
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 93
WHERE e.sku = '24-WG081-gray';
```

```
+---------------+-----------+
| sku           | raw_value |
+---------------+-----------+
| 24-WG081-gray |        52 |
+---------------+-----------+
```

**52.** Not "Gray". The EAV value table stores an **option id**, because an
admin can rename "Gray" to "Graphite" and every product must follow.

The labels:

```sql
SELECT o.option_id, ov.value
FROM eav_attribute_option o
JOIN eav_attribute_option_value ov ON ov.option_id = o.option_id
WHERE o.attribute_id = 93;
```

```
+------------+----------+
| option_id | value    |
+------------+----------+
|         49 | Black    |
|         50 | Blue     |
|         51 | Brown    |
|         52 | Gray     |
...
|         60 | Yellow   |
+------------+----------+
```

Now the full chain — **three** extra joins for one word:

```sql
SELECT e.sku, ov.value AS color
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 93
JOIN eav_attribute_option o
     ON o.option_id = ci.value AND o.attribute_id = ci.attribute_id
JOIN eav_attribute_option_value ov
     ON ov.option_id = o.option_id AND ov.store_id = 0
WHERE e.sku = '24-WG081-gray';
```

```
+---------------+-------+
| sku           | color |
+---------------+-------+
| 24-WG081-gray | Gray  |
+---------------+-------+
```

```
catalog_product_entity_int   value = 52        ← the option id, for THIS attribute only
        │  o.option_id = ci.value AND o.attribute_id = ci.attribute_id
        ▼
eav_attribute_option         attribute_id, sort_order
        │  ov.option_id = o.option_id
        ▼
eav_attribute_option_value   value = 'Gray', store_id = 0
```

Note `eav_attribute_option_value` is **itself store-scoped** — option labels
can be translated per store view, so the fallback rule applies here too.

Also note: the SKU says `-gray`, the label is `Gray`. **SKU and attribute are
independent.** Never derive a value from the other.

---

## 9. Scaling up

Drop the SKU filter, read several configurables:

```sql
SELECT e.sku,
       COALESCE(n_store.value, n_default.value) AS name,
       p.value                                 AS price
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar n_store
     ON n_store.entity_id = e.entity_id AND n_store.attribute_id = 73 AND n_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar n_default
     ON n_default.entity_id = e.entity_id AND n_default.attribute_id = 73 AND n_default.store_id = 0
LEFT JOIN catalog_product_entity_decimal p
     ON p.entity_id = e.entity_id AND p.attribute_id = 77 AND p.store_id = 0
WHERE e.type_id = 'configurable'
LIMIT 5;
```

```
+------+-------------------------------+-----------+
| sku  | name                          | price     |
+------+-------------------------------+-----------+
| MH01 | Chaz Kangeroo Hoodie          | 52.000000 |
| MH02 | Teton Pullover Hoodie         | 70.000000 |
| MH03 | Bruno Compete Hoodie          | 63.000000 |
+------+-------------------------------+-----------+
```

What did it cost? `EXPLAIN` the same query:

```
| table | type | key                                                           | rows |
+-------+------+---------------------------------------------------------------+------+
| e     | ALL  | NULL                                                          | 2042 |
| n     | eq_ref | CATALOG_PRODUCT_ENTITY_VARCHAR_ENTITY_ID_ATTRIBUTE_ID_STORE_ID |    1 |
| p     | eq_ref | CATALOG_PRODUCT_ENTITY_DECIMAL_ENTITY_ID_ATTRIBUTE_ID_STORE_ID  |    1 |
```

Read it carefully:

- The **value joins** are `eq_ref` with `rows = 1`. Excellent — the composite
  unique index `(entity_id, attribute_id, store_id)` makes each an O(1) lookup.
- The **base table** is `type = ALL`: 2 042 rows scanned, because
  `type_id = 'configurable'` has no index.

Add attributes and you multiply the left-hand scan. The value lookups stay
cheap, but you are now reading N tables once per product.

For a **price list**, there is a table built exactly for this:

```sql
SELECT e.sku, p.min_price
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.type_id = 'configurable'
  AND p.website_id = 1
  AND p.customer_group_id = 0
LIMIT 5;
```

```
+------+-----------+
| sku  | min_price |
+------+-----------+
| MH01 | 52.000000 |
| MH02 | 70.000000 |
+------+-----------+
```

Pre-computed, pre-indexed, one row. **Index tables exist so you do not build
this join by hand on every request.**

---

# PART D — Structure EAV

## 10. Attribute sets

Not every product exposes the same attributes. That is decided by its
**attribute set**.

```sql
SELECT eas.attribute_set_id, eas.attribute_set_name,
       COUNT(eea.attribute_id) AS nb_attributes
FROM eav_attribute_set eas
JOIN eav_entity_attribute eea ON eea.attribute_set_id = eas.attribute_set_id
WHERE eas.entity_type_id = 4
GROUP BY eas.attribute_set_id, eas.attribute_set_name
ORDER BY nb_attributes
LIMIT 6;
```

```
+------------------+--------------------+---------------+
| attribute_set_id | attribute_set_name | nb_attributes |
+------------------+--------------------+---------------+
|                4 | Default            |            59 |
|               14 | Downloadable       |            61 |
|               12 | Sprite Stasis Ball |            65 |
|               13 | Sprite Yoga Strap  |            65 |
|               11 | Gear               |            69 |
|               15 | Bag                |            70 |
+------------------+--------------------+---------------+
```

Sets are **independent**, not nested: `Bag` has 70 attributes, but not
"Default's 59 plus 11" — the join above counts only what is *directly* attached
to the set. What does set 9 ("Top") expose?

```sql
SELECT ea.attribute_code, ea.backend_type, ea.frontend_input
FROM eav_attribute_set eas
JOIN eav_entity_attribute eea ON eea.attribute_set_id = eas.attribute_set_id
JOIN eav_attribute ea         ON ea.attribute_id = eea.attribute_id
WHERE eas.attribute_set_id = 9
  AND ea.attribute_code IN ('name','price','color','size')
ORDER BY ea.attribute_id;
```

```
+--------------+--------------+----------------+
| attribute_code | backend_type | frontend_input |
+--------------+--------------+----------------+
| name          | varchar      | text           |
| price         | decimal      | price          |
| color         | int          | select         |
| size          | int          | select         |
+--------------+--------------+----------------+
```

Configurables use **exactly two** sets:

```sql
SELECT e.attribute_set_id, s.attribute_set_name, COUNT(*) AS products
FROM catalog_product_entity e
JOIN eav_attribute_set s ON s.attribute_set_id = e.attribute_set_id
WHERE e.type_id = 'configurable'
GROUP BY e.attribute_set_id, s.attribute_set_name;
```

```
+------------------+--------------------+----------+
| attribute_set_id | attribute_set_name | products |
+------------------+--------------------+----------+
|                9 | Top                |       98 |
|               10 | Bottom             |       49 |
+------------------+--------------------+----------+
```

Why? Because those are the only two sets that carry **both** `color` and
`size` — and a configurable product *needs* them to offer variants:

```sql
SELECT eea.attribute_set_id, s.attribute_set_name, COUNT(*) AS attrs
FROM eav_entity_attribute eea
JOIN eav_attribute_set s ON s.attribute_set_id = eea.attribute_set_id
JOIN eav_attribute ea ON ea.attribute_id = eea.attribute_id
WHERE eea.entity_type_id = 4
  AND ea.attribute_code IN ('color','size')
GROUP BY eea.attribute_set_id, s.attribute_set_name
ORDER BY eea.attribute_set_id;
```

```
+------------------+--------------------+-------+
| attribute_set_id | attribute_set_name | attrs |
+------------------+--------------------+-------+
|                9 | Top                |     2 |
|               10 | Bottom             |     2 |
|               11 | Gear               |     1 |
|               12 | Sprite Stasis Ball |     2 |
|               13 | Sprite Yoga Strap  |     2 |
|               15 | Bag                |     1 |
+------------------+--------------------+-------+
```

`attrs = 2` means "has both". A configurable that is *not* in Top or Bottom
could not be created.

But `Top` and `Bottom` are also the two biggest **simple**-product sets (1 364
and 483 products). Those products simply have no rows in
`catalog_product_entity_int` for `color`/`size` — the set declares the field,
the value table stays empty.

> ### The payoff of EAV
>
> This is the whole reason Magento uses EAV. Adding a "Material" attribute does
> **not** touch `catalog_product_entity`. It inserts one row in
> `eav_attribute` and one row in `eav_entity_attribute` per *chosen* set.
> `catalog_product_entity` is never altered, and no other product gains a field
> it should not have.
>
> The price is paid in Part C: reading N attributes costs N joins.

---

## 11. Not every select attribute is in the database

`color` has 12 option rows. `status` has none. Why?

```sql
SELECT attribute_id, attribute_code, source_model
FROM eav_attribute
WHERE attribute_id IN (93, 97);
```

```
+--------------+----------------+-------------------------------------------------------+
| attribute_id | attribute_code | source_model                                          |
+--------------+----------------+-------------------------------------------------------+
|           93 | color          | NULL                                                  |
|           97 | status         | Magento\Catalog\Model\Product\Attribute\Source\Status |
+--------------+----------------+-------------------------------------------------------+
```

```sql
SELECT attribute_id, COUNT(*) AS options_in_db
FROM eav_attribute_option
WHERE attribute_id IN (93, 97)
GROUP BY attribute_id;
```

```
+--------------+---------------+
| attribute_id | options_in_db |
+--------------+---------------+
|           93 |            12 |
+--------------+---------------+
```

`status` is missing from the second result — it has **zero** option rows.

`source_model` explains it. For `color` the options are administrator-managed
rows in `eav_attribute_option`. For `status` the labels are **hardcoded in a
PHP class** (`Magento\Catalog\Model\Product\Attribute\Source\Status`), because
a product is only ever Enabled or Disabled.

### The trap: an unqualified option join

Every product stores `status = 1`. Option id 1 exists — but it belongs to
`gender`. So the "obvious" join returns a **wrong answer, not a NULL**:

```sql
SELECT ov.value AS label_for_status
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 97
JOIN eav_attribute_option o
     ON o.option_id = ci.value            -- ← no attribute_id
JOIN eav_attribute_option_value ov
     ON ov.option_id = o.option_id AND ov.store_id = 0
LIMIT 3;
```

```
+------------------+
| label_for_status |
+------------------+
| Male             |
| Male             |
| Male             |
+------------------+
```

**"Male", 2 054 times.** Option id 1 really is:

```
+--------------+----------------+-------+
| attribute_id | attribute_code | value |
+--------------+----------------+-------+
|           20 | gender         | Male  |
+--------------+----------------+-------+
```

The raw value `1` is meaningless on its own — it is only an option id *within
one attribute*. Two rules follow:

1. **Always pair the option join with the attribute:**
   `ON o.option_id = ci.value AND o.attribute_id = ci.attribute_id`
2. **Check `source_model` first.** A `select` attribute with a `source_model`
   has no option rows to join to, and the collision above will silently
   hand you another attribute's label.

---

# PART E — Quitter l'EAV

EAV is flexible and slow. Everything commercially critical is a **flat table**.
Now use them correctly.

## 12. Revenue: `base_` columns and status filters

`sales_order` is flat. No joins needed. Two traps remain.

```sql
-- Naive
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)                   AS n,
       ROUND(SUM(grand_total), 2) AS ca
FROM sales_order
GROUP BY month;
```

```
+---------+----+---------+
| month   | n  | ca      |
+---------+----+---------+
| 2026-07 |  8 | 1689.13 |
| 2026-08 | 21 | 4484.96 |
| 2026-09 |  7 |  261.00 |
+---------+----+---------+
```

```sql
-- Correct
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)                        AS orders,
       ROUND(SUM(base_grand_total), 2) AS ca
FROM sales_order
WHERE status NOT IN ('canceled')
GROUP BY month
ORDER BY month;
```

```
+---------+--------+---------+
| month   | orders | ca      |
+---------+--------+---------+
| 2026-07 |      7 | 1119.13 |
| 2026-08 |     20 | 4430.96 |
| 2026-09 |      7 |  261.00 |
+---------+--------+---------+
```

July: **8 → 7 orders**, **1689.13 → 1119.13**.

| Trap | Effect |
|---|---|
| `grand_total` | In the *visitor's* currency. It changes with the store view. |
| no `status` filter | Canceled orders counted as revenue. |

`base_grand_total` is the accounting truth. Two bugs, one symptom — fixing
only the column still over-counts.

## 13. `LEFT JOIN`: filters belong in `ON`

```sql
-- Conditions in WHERE
SELECT c.email, ROUND(SUM(o.base_grand_total),2) AS spent
FROM customer_entity c
JOIN sales_order o ON o.customer_id = c.entity_id
WHERE o.status NOT IN ('canceled')
GROUP BY c.entity_id, c.email
HAVING spent > 0
ORDER BY spent DESC
LIMIT 5;
```

Five rows, looks perfect. **Six customers have no order at all and have
silently vanished.**

```sql
-- Conditions in ON
SELECT c.entity_id, c.email,
       ROUND(COALESCE(SUM(o.base_grand_total), 0), 2) AS spent,
       COUNT(o.entity_id)                          AS orders
FROM customer_entity c
LEFT JOIN sales_order o
       ON o.customer_id = c.entity_id
      AND o.status NOT IN ('canceled')
GROUP BY c.entity_id, c.email
ORDER BY spent DESC
LIMIT 8;
```

```
+-----------+------------------------------+---------+--------+
| entity_id | email                        | spent   | orders |
+-----------+------------------------------+---------+--------+
|         1 | roni_cost@example.com        | 3139.09 |     21 |
|         7 | aicha.lamrani@example.com    | 1448.00 |      3 |
|         6 | yassine.ouazzani@example.com |  764.00 |      4 |
|         5 | salma.benjelloun@example.com |  324.00 |      2 |
|         4 | karim.elalami@example.com    |   34.00 |      1 |
|        13 | boutayna4321.bm@gmail.com    |    0.00 |      0 |
|        12 | checkout_test@example.com    |    0.00 |      0 |
|         2 | deleted-2@anonymized.local   |    0.00 |      0 |
+-----------+------------------------------+---------+--------+
```

There are 11 customers; **6 have no order at all** and only appeared because
`LIMIT 8` was generous. The `INNER JOIN` version showed 5 rows and never
mentioned them.

A filter on the right table of a `LEFT JOIN` goes in `ON`. In `WHERE` it
discards the NULL-extended rows — quietly turning your `LEFT JOIN` into an
`INNER JOIN`.

Notice `deleted-2@anonymized.local`: kept, at 0.00, not deleted. That is the
`SET NULL` on `sales_order.customer_id` — GDPR erasure removes the account, not
the accounting history.

## 14. The snapshot: never join the catalog for a sales report

```sql
-- Naive: joins the catalog to get the name
SELECT e.sku, n.value AS name, SUM(oi.qty_ordered) AS qty
FROM sales_order_item oi
JOIN sales_order o            ON o.entity_id = oi.order_id
JOIN catalog_product_entity e ON e.entity_id = oi.product_id
JOIN catalog_product_entity_varchar n
     ON n.entity_id = e.entity_id AND n.attribute_id = 73 AND n.store_id = 0
WHERE o.status NOT IN ('canceled')
GROUP BY e.sku, n.value
ORDER BY qty DESC
LIMIT 3;
```

```
+------------------+--------------------------------+---------+
| sku              | name                           | qty     |
+------------------+--------------------------------+---------+
| MT07             | Argus All-Weather Tank         | 61.0000 |
| MT07-XS-Gray     | Argus All-Weather Tank-XS-Gray | 61.0000 |
| WS12             | Radiant Tee                    | 11.0000 |
+------------------+--------------------------------+---------+
```

**One product cannot sell 61 units twice.** The catalog join reads a
configurable order's *parent* line and its *child* line as two different
products, because the EAV name differs between them.

```sql
-- Correct: read the snapshot columns
SELECT oi.sku, MAX(oi.name) AS name, SUM(oi.qty_ordered) AS qty
FROM sales_order_item oi
JOIN sales_order o ON o.entity_id = oi.order_id
WHERE o.status NOT IN ('canceled')
GROUP BY oi.sku
ORDER BY qty DESC
LIMIT 3;
```

```
+------------------+-------------------------------+----------+
| sku              | name                          | qty      |
+------------------+-------------------------------+----------+
| MT07-XS-Gray     | Argus All-Weather Tank-XS-Gray | 122.0000 |
| WS12-XL-Purple   | Radiant Tee-XL-Purple          |  12.0000 |
| TEST-LOYALTY-250 | Test Loyalty Product 250 MAD   |  10.0000 |
+------------------+-------------------------------+----------+
```

122 units, correct.

`sales_order_item` **duplicates** `sku`, `name`, `price` and `qty_ordered`
precisely so this join is unnecessary. Those columns were frozen at purchase
time; joining the catalog gives you *today's* name, which is wrong the moment
anyone renames a product.

Three rules:

1. **Never join the catalog for a sales report.**
2. **Group by the business key** (SKU), not by a display column.
3. `MAX(price)` on a configurable parent is the *parent* price. For per-variant
   revenue, group by `parent_item_id` or by `product_options`.

---

# PART F — Erreurs

## 15. `ONLY_FULL_GROUP_BY`

```sql
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, created_at, COUNT(*)
FROM sales_order
GROUP BY month;
```

```
ERROR 1055 (42000): Expression #2 of SELECT list is not in GROUP BY clause
and contains nonaggregated column 'magento2.sales_order.created_at'
which is not functionally dependent on columns in GROUP BY clause;
this is incompatible with sql_mode=only_full_group_by
```

The message names the position and the column. Usually enough. Two repairs.

**A — aggregate it**, when you want the boundaries of the group:

```sql
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)        AS orders,
       MIN(created_at) AS first_order,
       MAX(created_at) AS last_order
FROM sales_order
GROUP BY month
ORDER BY month;
```

```
+---------+--------+---------------------+---------------------+
| month   | orders | first_order         | last_order          |
+---------+--------+---------------------+---------------------+
| 2026-07 |      8 | 2026-07-14 11:55:10 | 2026-07-31 16:44:39 |
| 2026-08 |     21 | 2026-08-05 14:22:55 | 2026-08-28 17:08:00 |
| 2026-09 |      7 | 2026-09-04 09:42:28 | 2026-09-09 15:44:46 |
+---------+--------+---------------------+---------------------+
```

**B — group the source too**, when you want the daily detail:

```sql
SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       DATE(created_at) AS day,
       COUNT(*)         AS orders
FROM sales_order
GROUP BY month, day
ORDER BY month, day
LIMIT 5;
```

**Never** "fix" this by removing `ONLY_FULL_GROUP_BY` from `sql_mode`. You get
an arbitrary row from the group, and a bug that only appears on data you did
not test.

## 16. Index tables need their dimensions

```sql
-- Naive
SELECT e.sku, p.min_price
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.sku = '24-MB01';
```

```
+---------+----------+
| sku     | min_price |
+---------+----------+
| 24-MB01 | 34.000000 |
| 24-MB01 | 34.000000 |
| 24-MB01 | 34.000000 |
| 24-MB01 | 34.000000 |
+---------+----------+
```

**Four identical rows.** The primary key is
`(entity_id, customer_group_id, website_id)`: one row per customer group.

```sql
-- Correct
SELECT e.sku, p.min_price, p.website_id, p.customer_group_id
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.sku = '24-MB01'
  AND p.website_id = 1
  AND p.customer_group_id = 0;
```

```
+---------+----------+-------------+-------------------+
| sku     | min_price | website_id | customer_group_id |
+---------+----------+-------------+-------------------+
| 24-MB01 | 34.000000 |           1 |                 0 |
+---------+----------+-------------+-------------------+
```

Always filter both dimensions. That is also how tiered pricing works: the
`website_id` comes from the current store, the `customer_group_id` from the
logged-in customer.

---

# PART G — Bonus

Five open questions. No answers — read the question, write the query, then
compare with `./scripts/db-practice.sh 17`.

**17a.** How many products have a `color`, and how many do not? *(Hint: a
`LEFT JOIN` plus `SUM(col IS NULL)`.)*

**17b.** Print the category subtree under `1/2` using only the materialized
`path`. *(Hint: one `LIKE`, one `ORDER BY`.)*

**17c.** Which products are enabled on website 1 but belong to **no** category?
*(These are invisible on the storefront. This is the most common "my product
does not show up".)*

**17d.** Revenue per store view, with the store *name* rather than the id.
*(Hint: `COUNT(DISTINCT ...)`, not `COUNT(*)`.)*

**17e.** Which SKUs are out of stock? *(Hint: `inventory_source_item` joins on
`source_code` — there is no `source_id` column.)*

---

## Self-check

Before moving on, answer these without running anything:

1. Where does a product's `name` physically live?
2. Why can `attribute_code = 'name'` return two rows?
3. What does `store_id = 0` mean, and why must you fall back to it?
4. A `select` attribute stores 52 instead of "Gray" — why, and which three
   tables turn it back into a word?
5. Why is `status` absent from `eav_attribute_option`, and what does an
   unqualified `o.option_id = ci.value` join return for it?
6. What are the two tables you use to read many product prices quickly, and
   which two columns must you always filter?
7. `sales_order_item` duplicates `name` and `price`. Why?
8. Where does a filter on the right table of a `LEFT JOIN` belong?

Answers and the full reference:
[magento-database.md §3-§4](magento-database.md#3-the-eav-model-in-detail) and
[magento-database-queries.md §3](magento-database-queries.md#3-eav-queries).

---

## Related

- [magento-database.md](magento-database.md) — the tables, EAV, relations
- [magento-database-queries.md](magento-database-queries.md) — the full query guide
- [magento-cron-indexers.md](magento-cron-indexers.md) — why index tables exist
- [magento-multistore.md](magento-multistore.md) — what `store_id` means

*Last updated: 2026-09-28*
