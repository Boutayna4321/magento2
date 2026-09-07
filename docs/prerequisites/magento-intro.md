# Magento Introduction

**What is Magento?** Magento 2 is an open-source e-commerce platform written in
PHP, published by Adobe. It comes in two editions:

| Edition | What it is |
|---|---|
| **Adobe Commerce** | Paid: B2B features, advanced MSI, Page Builder, Adobe support |
| **Magento Open Source** | Free: complete e-commerce core, extensible via modules. **This is what AlpineCommerce uses.** |

**Why Magento exists**: online stores quickly become complex (multi-store,
millions of product files, validation workflows, ERP integrations, large-scale
customization). Magento structures this complexity with a modular architecture.

## Key concepts

1. **Modular**: everything is a module. You add a feature by creating a module, not by modifying the core.
2. **Extensible without modification**: Plugins, Observers, Layout XML, and DI allow you to change behavior without touching the original code.
3. **Service Contracts**: every business capability is exposed via an interface.
4. **EAV + tables**: the data model combines flexible attributes (EAV) and optimized flat tables.

## Architecture overview

```
HTTP Request
    ↓
Router (URL router)
    ↓
Controller (orchestrates, does not handle business logic)
    ↓
Service Contract (business interface)
    ↓
Repository (implementation, encapsulates data access)
    ↓
ResourceModel (talks to the database)
    ↓
Database (MySQL)
    ↓
Response (HTML, JSON...)
```

## Where to go next

- **Full reference**: [`../magento2/magento-intro.md`](../magento2/magento-intro.md)
- **Hands-on**: start with the [`AUTO_INVOICE.md`](../modules/AUTO_INVOICE.md) module documentation
- **Engineering standards**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: all Magento 2 development*
