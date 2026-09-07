# Magento CLI

The Magento CLI (`bin/magento`) is your primary tool for development, deployment, and maintenance.

## Essential commands

### Module management

```bash
php bin/magento module:enable AlpineCommerce_AutoInvoice
php bin/magento module:disable AlpineCommerce_Test
php bin/magento setup:upgrade
```

### Cache

```bash
php bin/magento cache:flush
php bin/magento cache:clean
php bin/magento cache:status
```

### Indexers

```bash
php bin/magento indexer:reindex
php bin/magento indexer:status
php bin/magento indexer:set-mode schedule
```

### Static content

```bash
php bin/magento setup:static-content:deploy -f
```

### Developer mode

```bash
php bin/magento deploy:mode:set developer
```

## Where to go next

- **Full reference**: [`../magento2/magento-cli.md`](../magento2/magento-cli.md)
- **Docker setup**: [`docker.md`](docker.md)

---

*Prerequisite for: daily development workflow*
