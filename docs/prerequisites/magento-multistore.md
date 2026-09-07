# Magento Multi-Store

Magento supports multiple stores from a single installation.

## Scope hierarchy

```
Website
    └── Store (view)
            └── Store View
```

| Level | Purpose |
|---|---|
| **Website** | Separate domain, separate customers, separate orders |
| **Store** | Groups store views, shares customers/orders |
| **Store View** | Different language/design, same catalog |

## Configuration scopes

Settings can be configured at different scopes:

| Scope | Where set |
|---|---|
| **Default** | Global (all websites) |
| **Website** | Per website |
| **Store View** | Per store view |

```xml
<showInDefault>1</showInDefault>
<showInWebsite>1</showInWebsite>
<showInStore>0</showInStore>
```

## Key concepts

### Base URLs

Each website can have its own base URL (domain/subdomain).

### Currency

Each website/store view can have different currencies.

### Locale

Each store view can have a different locale/language.

### Config fallback

If a setting is not defined at website/store view level, it falls back to default.

## Where to go next

- **Full reference**: [`../magento2/magento-multistore.md`](../magento2/magento-multistore.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: multi-language stores, multi-brand deployments*
