# Magento 2 — Cron & Indexers

> **Objective**: understand how Magento automates recurring tasks (cron) and
> how it keeps search, categories, and prices fast (indexers). These two
> systems are essential to the daily operation of any Magento store.

---

## 1. What is Cron?

### 1.1 Definition

**Cron** is a time-based job scheduler. It runs tasks **automatically** at
configured intervals.

**In Magento**, cron is used for:
- Sending transactional emails
- Reindexing data
- Updating currency rates
- Generating sitemaps
- Running scheduled updates (catalog price rules, cart price rules)
- Cleaning expired cache
- Customer segmentation updates
- Custom module tasks (e.g., CustomerCare VIP levels)

### 1.2 How Magento Cron works

Magento uses a **3-level cron system**:

```
Level 1: System cron (every minute)
    ↓
Level 2: Magento cron runner (every minute)
    ↓
Level 3: Scheduled jobs (var/cron_schedule)
```

**Level 1 — System cron** (Linux):
```bash
# /etc/cron.d/magento
* * * * * /usr/bin/php /var/www/html/bin/magento cron:run
```

**Level 2 — Magento cron runner**:
```bash
php bin/magento cron:run
```

This generates `var/cron_schedule/*.yml` files with scheduled jobs.

**Level 3 — Scheduled jobs**:
Each job has:
- `job_code` (e.g., `indexer_reindex_all_invalid`)
- `status` (pending, running, success, missed, error)
- `scheduled_at` (when it should run)
- `executed_at` (when it actually ran)
- `finished_at` (when it finished)

### 1.3 Running cron manually

```bash
# Run all cron jobs
php bin/magento cron:run

# Run a specific job group
php bin/magento cron:run --group="default"

# Run a specific job by code
php bin/magento cron:run --job="indexer_reindex_all_invalid"

# View cron status
php bin/magento cron:check

# View cron schedule
ls -la var/cron_schedule/
```

### 1.4 Cron groups

Magento organizes cron jobs into **groups**:

| Group | Purpose | Example jobs |
|-------|---------|--------------|
| `default` | General Magento tasks | `catalog_product_price_reindex`, `sales_send_order_email` |
| `index` | Indexing | `indexer_reindex_all_invalid` |
| `staging` | Staging/scheduled changes | `staging_update_entities` |

### 1.5 AlpineCommerce example: CustomerCare cron

```xml
<!-- etc/crontab.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <group id="default">
        <job name="customercare_update_vip_levels" instance="AlpineCommerce\CustomerCare\Cron\UpdateVipLevels" method="execute">
            <schedule>0 2 * * *</schedule>
        </job>
    </group>
</config>
```

```php
// Cron/UpdateVipLevels.php
class UpdateVipLevels
{
    private CustomerCareInterface $customerCare;
    
    public function execute(): void
    {
        $this->customerCare->recalculateAll();
    }
}
```

**Schedule**: every day at 2:00 AM (`0 2 * * *`)

---

## 2. What is an Indexer?

### 2.1 Definition

An **indexer** transforms complex EAV data into **flat tables** for fast
reading.

**The problem Magento solves**:
- Products use EAV (attributes spread across 10+ tables)
- A product listing page needs to filter/sort by name, price, status, category
- Joining 10 tables for every product is **slow**
- Solution: pre-compute the data into flat index tables

### 2.2 How indexers work

```
EAV Data (tables: catalog_product_entity, catalog_product_entity_varchar, ...)
    ↓ [Indexer processes this]
Flat Index Table (catalog_product_index_price, catalogsearch_fulltext, ...)
    ↓ [Fast SELECT queries]
Frontend / Admin
```

### 2.3 Types of indexers

| Type | Description | Example |
|------|-------------|---------|
| **Update on Save** | Reindex immediately when data changes | `catalog_product_price` |
| **Update on Schedule** | Reindex via cron, not on save | `catalog_search` |
| **Reindex Required** | Manual reindex needed | `catalog_product_attribute` |

### 2.4 Indexer modes

```bash
# View all indexers and their modes
php bin/magento indexer:status

# Set an indexer to "Update on Save"
php bin/magento indexer:set-mode realtime catalog_product_price

# Set an indexer to "Update on Schedule"
php bin/magento indexer:set-mode schedule catalog_search

# Set all indexers to "Update on Schedule"
php bin/magento indexer:set-mode schedule
```

**Modes explained**:

| Mode | Behavior | Use case |
|------|----------|----------|
| **Realtime** (`realtime`) | Reindex immediately on save | Critical data (price, stock) |
| **Schedule** (`schedule`) | Reindex via cron | Heavy data (search, catalog) |

### 2.5 Indexer commands

```bash
# List all indexers
php bin/magento indexer:list

# Reindex all invalid indexers
php bin/magento indexer:reindex

# Reindex a specific indexer
php bin/magento indexer:reindex catalog_product_price

# Reset an indexer (mark as invalid)
php bin/magento indexer:reset catalog_search

# Show indexer info
php bin/magento indexer:show-status
```

---

## 3. Core Magento Indexers

### 3.1 Product indexers

| Indexer | Table | Purpose |
|---------|-------|---------|
| `catalog_product_price` | `catalog_product_index_price` | Product prices (including tier, group) |
| `catalog_product_attribute` | `catalog_product_index_entity` | Product attributes for listings |
| `cataloginventory_stock` | `cataloginventory_stock_status` | Stock status per product/store |
| `catalog_category_product` | `catalog_category_product_index` | Product-to-category assignments |
| `catalog_product_fulltext` | `catalogsearch_fulltext` | Full-text search for products |
| `catalogrule_product` | `catalogrule_*` | Catalog price rules |
| `salesrule_rule` | `salesrule_*` | Cart price rules |

### 3.2 Customer indexers

| Indexer | Table | Purpose |
|---------|-------|---------|
| `customer_grid` | `customer_grid_flat` | Customer listing data (admin grid) |

### 3.3 Category indexers

| Indexer | Table | Purpose |
|---------|-------|---------|
| `catalog_category_flat` | `catalog_category_flat_store_*` | Flat category data per store |
| `catalog_category_fulltext` | `catalogsearch_fulltext` | Category search |

---

## 4. How Indexers and Cron Work Together

### 4.1 The "Update on Schedule" flow

```
1. Admin updates a product price
   ↓
2. Magento marks the indexer as "invalid"
   ↓
3. Cron runs (every minute)
   ↓
4. Cron sees "invalid" indexer → triggers reindex
   ↓
5. Indexer rebuilds the flat table
   ↓
6. Frontend shows the new price
```

### 4.2 Cron jobs for indexers

Magento automatically schedules these cron jobs:

| Job Code | Indexer | Frequency |
|----------|---------|-----------|
| `indexer_reindex_all_invalid` | All invalid indexers | Every minute |
| `catalog_product_price_reindex` | `catalog_product_price` | Every minute |
| `catalogsearch_fulltext_reindex` | `catalog_search` | Every minute |

### 4.3 Checking cron health

```bash
# Check if cron is running
php bin/magento cron:check

# View cron schedule
cat var/cron_schedule/*.yml | grep -i "indexer"

# View cron logs
grep -i "cron" var/log/system.log
grep -i "cron" var/log/exception.log
```

---

## 5. Creating a Custom Indexer

### 5.1 Example: CustomerCare VIP indexer

```xml
<!-- etc/indexer.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <indexer id="customercare_vip_status" view_id="customercare_vip_status">
        <title>Customer Care VIP Status</title>
        <description>Customer VIP level and lifetime spent</description>
        <class>AlpineCommerce\CustomerCare\Model\Indexer\VipStatus</class>
    </indexer>
</config>
```

```php
// Model/Indexer/VipStatus.php
class VipStatus implements IndexerInterface
{
    private CustomerCareInterface $customerCare;
    
    public function executeFull(): void
    {
        $this->customerCare->recalculateAll();
    }
    
    public function executeRow($id): void
    {
        // Reindex for a single customer ID
        $this->customerCare->recalculateVipStatus((int) $id);
    }
    
    public function executeList(array $ids): void
    {
        foreach ($ids as $id) {
            $this->executeRow($id);
        }
    }
}
```

### 5.2 Triggering the indexer

```xml
<!-- etc/events.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <event name="checkout_onepage_controller_success_action">
        <observer name="autoinvoice_create_invoice" instance="AlpineCommerce\AutoInvoice\Observer\AutoInvoice"/>
    </event>
</config>
```

```php
// Observer/AutoInvoice.php
class AutoInvoice
{
    private ScopeConfigInterface $scopeConfig;
    private OrderServiceInterface $orderService;
    
    public function execute(Event $event): void
    {
        $order = $event->getEvent()->getOrder();
        // Auto-create invoice based on config
    }
}
```

---

## 6. Indexer Best Practices

### 6.1 When to use "Update on Save" vs "Update on Schedule"

| Data type | Recommended mode | Why |
|-----------|------------------|-----|
| Product price | Realtime | Price changes must be visible immediately |
| Stock status | Realtime | Out-of-stock must be visible immediately |
| Search index | Schedule | Reindexing is heavy, can wait for cron |
| Category assignment | Schedule | Multiple products can change at once |
| Customer data | Realtime | Customer info must be current |

### 6.2 Avoiding indexer performance issues

```bash
# 1. Use "Update on Schedule" for heavy indexers
php bin/magento indexer:set-mode schedule catalog_search

# 2. Reindex during low traffic (via cron)
# Configure cron to run heavy reindexes at night

# 3. Monitor indexer status
php bin/magento indexer:show-status

# 4. Reset stuck indexers
php bin/magento indexer:reset catalog_search
```

### 6.3 AlpineCommerce indexer usage

AlpineCommerce modules use indexers indirectly:
- **CustomerGrid**: uses `customer_grid` indexer for admin customer listing
- **StorePickup**: no custom indexer (uses Repository directly)
- **Blog**: no custom indexer (small dataset, Repository is fine)

---

## 7. Cron Best Practices

### 7.1 Cron configuration

```bash
# Verify cron is installed
crontab -l

# Should show something like:
* * * * * /usr/bin/php /var/www/html/bin/magento cron:run
```

### 7.2 Cron in Docker

```yaml
# docker-compose.yml (cron service)
cron:
  image: alpine:latest
  volumes:
    - ./src:/var/www/html
  entrypoint: /bin/sh -c "echo '* * * * * php /var/www/html/bin/magento cron:run' >> /etc/crontabs/root && crond -f -l 2"
```

### 7.3 Monitoring cron

```bash
# Check cron schedule
ls -la var/cron_schedule/

# View recent cron jobs
cat var/cron_schedule/*.yml | grep -A 10 "indexer"

# Check for missed jobs
php bin/magento cron:check
```

### 7.4 AlpineCommerce cron jobs

| Job Code | Module | Schedule | Purpose |
|----------|--------|----------|---------|
| `customercare_update_vip_levels` | CustomerCare | Daily 02:00 | Recalculate all VIP levels |
| `indexer_reindex_all_invalid` | Core | Every minute | Reindex all invalid indexers |

---

## 8. Common Issues

### 8.1 Cron not running

**Symptoms**: scheduled tasks not executing, emails not sending, indexers not updating.

**Solutions**:
```bash
# 1. Check if cron is installed
crontab -l

# 2. Test cron manually
php bin/magento cron:run

# 3. Check cron logs
grep -i "cron" var/log/system.log

# 4. Check cron schedule
ls -la var/cron_schedule/
```

### 8.2 Indexer stuck

**Symptoms**: `indexer:status` shows "invalid" forever, reindex hangs.

**Solutions**:
```bash
# 1. Reset the indexer
php bin/magento indexer:reset catalog_search

# 2. Reindex
php bin/magento indexer:reindex catalog_search

# 3. If still stuck, check DB
mysql -u root -p magento2 -e "SELECT * FROM indexer_state WHERE indexer_id = 'catalog_search';"

# 4. Update status manually
mysql -u root -p magento2 -e "UPDATE indexer_state SET status = 'valid' WHERE indexer_id = 'catalog_search';"
```

### 8.3 Cron jobs missing

**Symptoms**: expected cron jobs not in `var/cron_schedule/`.

**Solutions**:
```bash
# 1. Run cron manually to generate schedule
php bin/magento cron:run

# 2. Check crontab.xml files
# Verify the job is defined in etc/crontab.xml

# 3. Check module is enabled
php bin/magento module:status AlpineCommerce_CustomerCare
```

### 8.4 Indexer too slow

**Symptoms**: reindex takes hours, site slows down during reindex.

**Solutions**:
```bash
# 1. Use "Update on Schedule" instead of "Realtime"
php bin/magento indexer:set-mode schedule catalog_search

# 2. Run reindex during low traffic
php bin/magento cron:run --group="index" &

# 3. Increase PHP memory limit
php -d memory_limit=4G bin/magento indexer:reindex
```

---

## 9. Debugging Cron and Indexers

### 9.1 Debug cron

```bash
# View cron schedule in detail
cat var/cron_schedule/*.yml | grep -B 5 -A 15 "customercare"

# Check cron execution logs
grep -i "cron:run" var/log/system.log

# Test a specific cron job
php bin/magento cron:run --job="customercare_update_vip_levels"
```

### 9.2 Debug indexers

```bash
# View indexer status
php bin/magento indexer:show-status

# View indexer details
php bin/magento indexer:info catalog_search

# Reindex with verbose output
php bin/magento indexer:reindex catalog_search -v

# Check indexer tables
mysql -u root -p magento2 -e "SHOW TABLES LIKE '%index%';"
```

### 9.3 Log cron activity

```php
// In a cron class
class UpdateVipLevels
{
    private LoggerInterface $logger;
    
    public function execute(): void
    {
        $this->logger->info('Cron UpdateVipLevels started');
        
        try {
            $this->customerCare->recalculateAll();
            $this->logger->info('Cron UpdateVipLevels completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Cron UpdateVipLevels failed: ' . $e->getMessage());
        }
    }
}
```

---

## 10. Summary

| Concept | Purpose | Command |
|---------|---------|---------|
| **Cron** | Automated recurring tasks | `php bin/magento cron:run` |
| **Cron groups** | Organize jobs by type | `default`, `index`, `staging` |
| **Cron schedule** | When jobs run | `var/cron_schedule/*.yml` |
| **Indexer** | Pre-compute data for fast reading | `php bin/magento indexer:reindex` |
| **Indexer modes** | Realtime vs Schedule | `indexer:set-mode realtime/schedule` |
| **Indexer status** | Valid, invalid, working | `php bin/magento indexer:status` |

### Key takeaways

1. **Cron runs every minute** — it triggers scheduled jobs like email sending, indexer updates, and custom module tasks
2. **Indexers pre-compute complex queries** — they transform EAV data into flat tables for fast reading
3. **"Update on Schedule" is for heavy operations** — search, category assignments
4. **"Update on Save" is for critical data** — prices, stock status
5. **Cron and indexers work together** — cron triggers the indexer jobs that rebuild flat tables

### When to use what

| Scenario | Action |
|----------|--------|
| Product price changed | Indexer `catalog_product_price` (realtime) |
| Customer search not finding new products | Reindex `catalog_search` (schedule via cron) |
| Emails not sending | Check cron: `cron:check`, `cron:run` |
| Admin grid slow | Check `customer_grid` indexer status |
| Custom module needs daily task | Add job to `etc/crontab.xml` |

---

*Last updated: 2026-08-11.*
