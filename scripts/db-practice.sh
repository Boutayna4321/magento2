#!/usr/bin/env bash
#
# db-practice.sh
#
# Practice lab for the Magento 2 database guides. Runs every query read-only
# against the live database and prints the real output, so you can compare your
# own results.
#
# The lab is ordered simple → complex, and builds the EAV model step by step:
#
#   Part A  Découvrir    1-3   where the data actually lives
#   Part B  Un attribut  4-6   read one EAV value, and the store fallback
#   Part C  Plusieurs    7-9   several attributes, options, many products
#   Part D  Structure   10-11  attribute sets, static sources
#   Part E  Quitter EAV 12-14  back to flat tables and reports
#   Part F  Erreurs     15-16  ONLY_FULL_GROUP_BY, index dimensions
#
# USAGE
#   ./scripts/db-practice.sh              # everything
#   ./scripts/db-practice.sh 5            # exercise 5
#   ./scripts/db-practice.sh B            # part B (4, 5, 6)
#   ./scripts/db-practice.sh --answers    # reveal the point of each step
#
# REQUIRES
#   The MySQL container running:  docker compose up -d mysql
#
# SAFETY
#   Every query below is SELECT-only. Nothing is written, updated or deleted.

set -uo pipefail

CONTAINER="${MYSQL_CONTAINER:-magento2-mysql}"
DB_USER="${MYSQL_USER:-magento}"
DB_PASS="${MYSQL_PASSWORD:-magento123}"
DB_NAME="${MYSQL_DATABASE:-magento2}"

ONLY="${1:-}"
PART=""
SHOW_ANSWERS=0
[ "${2:-}" = "--answers" ] && SHOW_ANSWERS=1
[ "$ONLY" = "--answers" ] && { SHOW_ANSWERS=1; ONLY=""; }

blue()  { printf '\033[1;34m%s\033[0m\n' "$*"; }
green() { printf '\033[1;32m%s\033[0m\n' "$*"; }
red()   { printf '\033[1;31m%s\033[0m\n' "$*"; }
dim()   { printf '\033[2m%s\033[0m\n' "$*"; }

# q <label> <sql>
q() {
    blue "  ▸ $1"
    dim "$2" | fold -s -w 96 | sed 's/^/     /'
    echo
    local out
    out=$(docker exec -i "$CONTAINER" mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" --table -e "$2" 2>&1 \
        | grep -v -e 'insecure' -e 'Using a password')
    if [ -z "$out" ]; then
        red "     (no rows — and no error. This is the dangerous case.)"
    else
        printf '%s\n' "$out"
    fi
    echo
}

# a <text>  — the takeaway, hidden unless --answers
a() {
    [ "$SHOW_ANSWERS" -eq 1 ] || return 0
    green "  ✓ $1"
    echo
}

part() {
    PART="$1"
    if [ -z "$ONLY" ] || [ "$ONLY" = "$1" ]; then
        echo
        red "════════ PART $1 — $2"
        dim "$3"
        echo
    fi
}

# want <number>  — run when no filter, when the filter is this number,
#                   or when the filter is the current part letter.
want() {
    case "$ONLY" in
        "")           return 0 ;;
        [1-9]|[1-9][0-9]) [ "$ONLY" = "$1" ] && return 0 || return 1 ;;
        [A-G])        [ "$ONLY" = "$PART" ] && return 0 || return 1 ;;
        *)             return 1 ;;
    esac
}

if ! docker ps --format '{{.Names}}' 2>/dev/null | grep -qx "$CONTAINER"; then
    red "Container '$CONTAINER' is not running."
    echo "Start it with:  docker compose up -d mysql"
    exit 1
fi

green "Magento 2 database practice"
dim "Database: $DB_NAME   Container: $CONTAINER   Every query is read-only."
[ "$SHOW_ANSWERS" -eq 1 ] && green "Answers: shown" || dim "Add --answers to reveal the takeaway of each step."

# ===========================================================================
part A "DÉCOUVRIR" "Where does a product name actually live?  Exercises 1-3"

if want 1; then
    red "1. The surprise"
    dim "A product has a name, a price, a colour. Open the product table."
    q "the product table" "DESCRIBE catalog_product_entity;"
    a "8 columns. No name, no price, no colour. Those are not missing — they live in the EAV value tables. This single fact explains the whole model."
fi

if want 2; then
    red "2. Find the attribute"
    dim "'name' is not unique in Magento. Two different things are called name."
    q "without entity type" "SELECT attribute_id, entity_type_id
FROM eav_attribute
WHERE attribute_code = 'name';"
    q "with entity type" "SELECT ea.attribute_id, ea.attribute_code, et.entity_type_code
FROM eav_attribute ea
JOIN eav_entity_type et ON et.entity_type_id = ea.entity_type_id
WHERE ea.attribute_code = 'name';"
    a "45 is the CATEGORY name, 73 is the PRODUCT name. attribute_code is unique per entity type, never globally. Always join eav_entity_type."
fi

if want 3; then
    red "3. Find the value table"
    dim "backend_type tells you which of the five value tables holds the value."
    q "backend types" "SELECT ea.attribute_code, ea.backend_type, ea.frontend_input
FROM eav_attribute ea
JOIN eav_entity_type et ON et.entity_type_id = ea.entity_type_id
WHERE et.entity_type_code = 'catalog_product'
  AND ea.attribute_code IN ('name','description','price','color','status')
ORDER BY ea.attribute_id;"
    a "varchar → catalog_product_entity_varchar. decimal → _decimal. int → _int. text → _text. You now have everything you need to build the query."
fi

# ===========================================================================
part B "UN ATTRIBUT" "Read one EAV value, and fix the store trap.  Exercises 4-6"

if want 4; then
    red "4. The first successful read"
    dim "Product 24-MB01, name attribute, admin scope."
    q "name of 24-MB01" "SELECT e.entity_id, e.sku, v.value AS name
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar v
     ON v.entity_id    = e.entity_id
    AND v.attribute_id = 73
    AND v.store_id     = 0
WHERE e.sku = '24-MB01';"
    a "Three conditions, not two: entity_id, attribute_id AND store_id. Drop store_id and you get one row per store view — or none."
fi

if want 5; then
    red "5. The trap: real store view"
    dim "Same query, store_id = 1 instead of 0."
    q "store_id = 1" "SELECT e.sku, v.value AS name
FROM catalog_product_entity e
JOIN catalog_product_entity_varchar v
     ON v.entity_id = e.entity_id AND v.attribute_id = 73 AND v.store_id = 1
WHERE e.sku = '24-MB01';"
    q "why? where the values are" "SELECT store_id, COUNT(*) AS n
FROM catalog_product_entity_varchar
WHERE attribute_id = 73
GROUP BY store_id;"
    a "ZERO rows, no error. An override row only exists if somebody changed the value in that store view. Nobody ever did, so all 2 042 names sit at store_id = 0. This is the most common EAV bug in production code."
fi

if want 6; then
    red "6. The fix: the store fallback"
    dim "Try the requested store, fall back to the admin scope."
    q "COALESCE fallback" "SELECT e.sku, COALESCE(v_store.value, v_default.value) AS name
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_store
     ON v_store.entity_id = e.entity_id AND v_store.attribute_id = 73 AND v_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar v_default
     ON v_default.entity_id = e.entity_id AND v_default.attribute_id = 73 AND v_default.store_id = 0
WHERE e.sku = '24-MB01';"
    q "you now have the recipe" "SELECT e.sku,
       v_store.store_id                          AS tried,
       v_default.store_id                        AS fell_back_to,
       COALESCE(v_store.value, v_default.value)  AS name
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar v_store
     ON v_store.entity_id = e.entity_id AND v_store.attribute_id = 73 AND v_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar v_default
     ON v_default.entity_id = e.entity_id AND v_default.attribute_id = 73 AND v_default.store_id = 0
WHERE e.sku = '24-MB01';"
    a "Two LEFT JOINs and a COALESCE reproduce exactly what Magento does internally. This is THE EAV pattern — memorise it."
fi

# ===========================================================================
part C "PLUSIEURS ATTRIBUTS" "Add attributes, resolve options, scale up.  Exercises 7-9"

if want 7; then
    red "7. A second attribute, a different table"
    dim "name is varchar. price is decimal. Different table, same shape."
    q "name + price" "SELECT e.sku,
       COALESCE(n_store.value, n_default.value) AS name,
       p.value                                 AS price
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar n_store
     ON n_store.entity_id = e.entity_id AND n_store.attribute_id = 73 AND n_store.store_id = 1
LEFT JOIN catalog_product_entity_varchar n_default
     ON n_default.entity_id = e.entity_id AND n_default.attribute_id = 73 AND n_default.store_id = 0
LEFT JOIN catalog_product_entity_decimal p
     ON p.entity_id = e.entity_id AND p.attribute_id = 77 AND p.store_id = 0
WHERE e.sku = '24-MB01';"
    a "One join per attribute, always. The only thing that changes between them is the table name and the attribute_id."
fi

if want 8; then
    red "8. A select attribute: the value is not the label"
    dim "colour. Read the raw value first — it is a number, not a word."
    q "raw value" "SELECT e.sku, ci.value AS raw_value
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 93
WHERE e.sku = '24-WG081-gray';"
    q "the 12 colours" "SELECT o.option_id, ov.value
FROM eav_attribute_option o
JOIN eav_attribute_option_value ov ON ov.option_id = o.option_id
WHERE o.attribute_id = 93;"
    q "resolve the label" "SELECT e.sku, ov.value AS color
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 93
JOIN eav_attribute_option o
     ON o.option_id = ci.value AND o.attribute_id = ci.attribute_id
JOIN eav_attribute_option_value ov
     ON ov.option_id = o.option_id AND ov.store_id = 0
WHERE e.sku = '24-WG081-gray';"
    a "A select attribute stores 52, not 'Gray'. Three extra joins to get the word. Note the SKU says -gray but the label is Gray: SKU and attribute are independent."
fi

if want 9; then
    red "9. Scaling up: many products"
    dim "Now drop the SKU filter and read 5 configurables."
    q "5 products x 2 attributes" "SELECT e.sku,
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
LIMIT 5;"
    q "what did it cost?" "EXPLAIN SELECT e.sku, n.value, p.value
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_varchar n
     ON n.entity_id = e.entity_id AND n.attribute_id = 73 AND n.store_id = 0
LEFT JOIN catalog_product_entity_decimal p
     ON p.entity_id = e.entity_id AND p.attribute_id = 77 AND p.store_id = 0
WHERE e.type_id = 'configurable';"
    q "the index table does it in one" "SELECT e.sku, p.min_price
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.type_id = 'configurable'
  AND p.website_id = 1
  AND p.customer_group_id = 0
LIMIT 5;"
    a "The EAV joins use eq_ref (1 row each, fast) but the base table is scanned (type=ALL, 2 042 rows). Add attributes and you multiply that cost. For a price list, read catalog_product_index_price — pre-computed, pre-indexed, one row."
fi

# ===========================================================================
part D "STRUCTURE EAV" "Which attributes exist, and where do their labels live?  Exercises 10-11"

if want 10; then
    red "10. Attribute sets"
    dim "Not every product exposes the same attributes. The set decides."
    q "sets and their size" "SELECT eas.attribute_set_id, eas.attribute_set_name,
       COUNT(eea.attribute_id) AS nb_attributes
FROM eav_attribute_set eas
JOIN eav_entity_attribute eea ON eea.attribute_set_id = eas.attribute_set_id
WHERE eas.entity_type_id = 4
GROUP BY eas.attribute_set_id, eas.attribute_set_name
ORDER BY nb_attributes
LIMIT 6;"
    q "what set 9 (Top) exposes" "SELECT ea.attribute_code, ea.backend_type, ea.frontend_input
FROM eav_attribute_set eas
JOIN eav_entity_attribute eea ON eea.attribute_set_id = eas.attribute_set_id
JOIN eav_attribute ea         ON ea.attribute_id = eea.attribute_id
WHERE eas.attribute_set_id = 9
  AND ea.attribute_code IN ('name','price','color','size')
ORDER BY ea.attribute_id;"
    q "which sets do configurables use?" "SELECT e.attribute_set_id, s.attribute_set_name, COUNT(*) AS products
FROM catalog_product_entity e
JOIN eav_attribute_set s ON s.attribute_set_id = e.attribute_set_id
WHERE e.type_id = 'configurable'
GROUP BY e.attribute_set_id, s.attribute_set_name;"
    q "where do color and size live?" "SELECT eea.attribute_set_id, s.attribute_set_name, COUNT(*) AS attrs
FROM eav_entity_attribute eea
JOIN eav_attribute_set s ON s.attribute_set_id = eea.attribute_set_id
JOIN eav_attribute ea ON ea.attribute_id = eea.attribute_id
WHERE eea.entity_type_id = 4
  AND ea.attribute_code IN ('color','size')
GROUP BY eea.attribute_set_id, s.attribute_set_name
ORDER BY eea.attribute_set_id;"
    a "Configurables use exactly two sets: Top and Bottom, the only two that carry BOTH color and size — a configurable needs them to offer variants. But those same two sets also hold 1 847 simple products, which simply leave color/size empty. The set is the product's declared list of columns, chosen once at creation. Adding an attribute later inserts a row in eav_entity_attribute for the right sets only; catalog_product_entity is never touched."
fi

if want 11; then
    red "11. Not all select attributes are in the database"
    dim "colour has 12 option rows. status has none. Why?"
    q "where the labels come from" "SELECT attribute_id, attribute_code, source_model
FROM eav_attribute
WHERE attribute_id IN (93, 97);"
    q "option rows" "SELECT attribute_id, COUNT(*) AS options_in_db
FROM eav_attribute_option
WHERE attribute_id IN (93, 97)
GROUP BY attribute_id;"
    q "the trap: an unqualified option join" "SELECT ov.value AS label_for_status
FROM catalog_product_entity e
JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 97
JOIN eav_attribute_option o
     ON o.option_id = ci.value
JOIN eav_attribute_option_value ov
     ON ov.option_id = o.option_id AND ov.store_id = 0
LIMIT 3;"
    q "what option 1 really is" "SELECT o.attribute_id, ea.attribute_code, ov.value
FROM eav_attribute_option o
JOIN eav_attribute ea ON ea.attribute_id = o.attribute_id
JOIN eav_attribute_option_value ov ON ov.option_id = o.option_id AND ov.store_id = 0
WHERE o.option_id = 1;"
    a "status stores 1 for every product — and option_id 1 exists, but it belongs to gender. An unqualified o.option_id = ci.value join returns 'Male' as the status of 2 054 products: a wrong answer, not a NULL. Always pair the option join with o.attribute_id = ci.attribute_id, and check source_model first — a select attribute with a source_model has no option rows to join to."
fi

# ===========================================================================
part E "QUITTER L'EAV" "Back to flat tables and reports.  Exercises 12-14"

if want 12; then
    red "12. Revenue: base_ columns and status filters"
    dim "sales_order is a flat table. No joins needed. Two traps remain."
    q "naive" "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)                   AS n,
       ROUND(SUM(grand_total), 2) AS ca
FROM sales_order
GROUP BY month;"
    q "correct" "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)                           AS orders,
       ROUND(SUM(base_grand_total), 2)    AS ca
FROM sales_order
WHERE status NOT IN ('canceled')
GROUP BY month
ORDER BY month;"
    a "July: 8 orders / 1689.13 becomes 7 / 1119.13. grand_total is in the visitor's currency; base_grand_total is the accounting truth. And canceled orders are not revenue."
fi

if want 13; then
    red "13. LEFT JOIN: filters belong in ON"
    dim "Six customers have no order at all."
    q "conditions in WHERE" "SELECT c.email, ROUND(SUM(o.base_grand_total),2) AS spent
FROM customer_entity c
JOIN sales_order o ON o.customer_id = c.entity_id
WHERE o.status NOT IN ('canceled')
GROUP BY c.entity_id, c.email
HAVING spent > 0
ORDER BY spent DESC
LIMIT 5;"
    q "conditions in ON" "SELECT c.entity_id, c.email,
       ROUND(COALESCE(SUM(o.base_grand_total), 0), 2) AS spent,
       COUNT(o.entity_id)                          AS orders
FROM customer_entity c
LEFT JOIN sales_order o
       ON o.customer_id = c.entity_id
      AND o.status NOT IN ('canceled')
GROUP BY c.entity_id, c.email
ORDER BY spent DESC
LIMIT 8;"
    a "A filter on the right table of a LEFT JOIN belongs in ON, never in WHERE. In WHERE it discards the NULL rows and quietly turns your LEFT JOIN into an INNER JOIN."
fi

if want 14; then
    red "14. The snapshot: never join the catalog for a sales report"
    dim "A configurable order stores a parent line plus one child line per variant."
    q "naive: joins the catalog" "SELECT e.sku, n.value AS name, SUM(oi.qty_ordered) AS qty
FROM sales_order_item oi
JOIN sales_order o            ON o.entity_id = oi.order_id
JOIN catalog_product_entity e ON e.entity_id = oi.product_id
JOIN catalog_product_entity_varchar n
     ON n.entity_id = e.entity_id AND n.attribute_id = 73 AND n.store_id = 0
WHERE o.status NOT IN ('canceled')
GROUP BY e.sku, n.value
ORDER BY qty DESC
LIMIT 3;"
    q "correct: read the snapshot" "SELECT oi.sku, MAX(oi.name) AS name, SUM(oi.qty_ordered) AS qty
FROM sales_order_item oi
JOIN sales_order o ON o.entity_id = oi.order_id
WHERE o.status NOT IN ('canceled')
GROUP BY oi.sku
ORDER BY qty DESC
LIMIT 3;"
    a "One product cannot sell 122 units twice. sales_order_item already holds sku, name and price as frozen at purchase time — that is why it duplicates them. Group by the business key (SKU), never by a display column."
fi

# ===========================================================================
part F "ERREURS" "Fix what breaks.  Exercises 15-16"

if want 15; then
    red "15. ONLY_FULL_GROUP_BY"
    q "rejected" "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, created_at, COUNT(*)
FROM sales_order
GROUP BY month;"
    q "fix A: aggregate it" "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       COUNT(*)        AS orders,
       MIN(created_at) AS first_order,
       MAX(created_at) AS last_order
FROM sales_order
GROUP BY month
ORDER BY month;"
    q "fix B: group the source too" "SELECT DATE_FORMAT(created_at,'%Y-%m') AS month,
       DATE(created_at) AS day,
       COUNT(*)         AS orders
FROM sales_order
GROUP BY month, day
ORDER BY month, day
LIMIT 5;"
    a "The error names the column. Aggregate it or group on it. NEVER drop ONLY_FULL_GROUP_BY from sql_mode — you just get an arbitrary row and a bug that only shows on untested data."
fi

if want 16; then
    red "16. Index tables need their dimensions"
    q "naive" "SELECT e.sku, p.min_price
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.sku = '24-MB01';"
    q "correct" "SELECT e.sku, p.min_price, p.website_id, p.customer_group_id
FROM catalog_product_index_price p
JOIN catalog_product_entity e ON e.entity_id = p.entity_id
WHERE e.sku = '24-MB01'
  AND p.website_id = 1
  AND p.customer_group_id = 0;"
    a "The PK is (entity_id, customer_group_id, website_id): one row per customer group. Four identical rows is the symptom. Always filter both dimensions — that is also how tiered pricing works."
fi

# ===========================================================================
part G "BONUS" "Open questions, no answers.  Exercise 17"

if want 17; then
    red "17. Five open questions"
    dim "Read the questions, write the queries, then compare."
    echo

    green "  17a. How many products have a colour, and how many do not?"
    q "count" "SELECT COUNT(*) AS products,
       SUM(ci.entity_id IS NOT NULL) AS with_color,
       SUM(ci.entity_id IS NULL)     AS without_color
FROM catalog_product_entity e
LEFT JOIN catalog_product_entity_int ci
     ON ci.entity_id = e.entity_id AND ci.attribute_id = 93;"

    green "  17b. The category tree, straight from the materialized path"
    q "tree" "SELECT entity_id, parent_id, path, level, children_count
FROM catalog_category_entity
WHERE path LIKE '1/2%'
ORDER BY path
LIMIT 6;"

    green "  17c. Products enabled on the website but in no category"
    dim "  (they are invisible on the storefront — the classic 'my product does not show up')"
    q "orphans" "SELECT e.entity_id, e.sku
FROM catalog_product_entity e
JOIN catalog_product_website pw ON pw.product_id = e.entity_id
LEFT JOIN catalog_category_product cp ON cp.product_id = e.entity_id
WHERE pw.website_id = 1 AND cp.product_id IS NULL;"

    green "  17d. Revenue per store view, with its name"
    q "stores" "SELECT s.name AS store_view,
       COUNT(DISTINCT o.entity_id)        AS orders,
       ROUND(SUM(o.base_grand_total), 2)  AS ca
FROM sales_order o
JOIN store s ON s.store_id = o.store_id
WHERE o.status NOT IN ('canceled')
GROUP BY s.name;"

    green "  17e. Which SKUs are out of stock?"
    dim "  (hint: the join key is source_code, there is no source_id)"
    q "stock" "SELECT sku, quantity, status
FROM inventory_source_item
WHERE status = 0;"
fi

if [ -z "$ONLY" ]; then
    echo
    green "Done. One exercise: ./scripts/db-practice.sh 6     One part: ./scripts/db-practice.sh C"
fi
