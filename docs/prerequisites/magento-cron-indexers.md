# Magento Cron & Indexers

Magento uses **cron jobs** for scheduled tasks and **indexers** to maintain
searchable/displayable data.

## Cron

Cron jobs run background tasks:
- Email sending
- Sitemap generation
- Currency rate updates
- Indexer processing (in scheduled mode)

### Essential cron entries

```bash
# In crontab -e
* * * * * php /path/to/magento/bin/magento cron:run
* * * * * php /path/to/magento/update/cron.php
* * * * * php /path/to/magento/bin/magento setup:cron:run
```

## Indexers

Indexers pre-compute data for faster queries.

| Indexer | Purpose |
|---|---|
| `catalogsearch_fulltext` | Search index |
| `catalog_product_price` | Product prices |
| `inventory` | Stock/quantity (MSI) |
| `catalog_category_product` | Category-product relationships |

### Indexer modes

| Mode | Behavior |
|---|---|
| **Realtime** | Updated on save (default, slower writes) |
| **Schedule** | Updated by cron (faster writes, eventual consistency) |

```bash
php bin/magento indexer:set-mode schedule
php bin/magento indexer:reindex
```

## Where to go next

- **Full reference**: [`../magento2/magento-cron-indexers.md`](../magento2/magento-cron-indexers.md)
- **CLI commands**: [`magento-cli.md`](magento-cli.md)

---

*Prerequisite for: performance optimization, deployment*
