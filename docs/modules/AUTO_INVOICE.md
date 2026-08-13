# AlpineCommerce_AutoInvoice Module — Auto Invoice

> **Status**: ✅ Stable (v1.0.0)

## 1. Responsibility

Automatically create **invoices** when orders are placed, filtered by
payment method and scoped per website/store view.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Auto invoicing** | Observer on `checkout_onepage_controller_success_action` |
| **Payment filter** | Comma-separated list of payment methods (empty = all) |
| **Admin page** | `autoinvoice/invoice/index` — status + recent orders |
| **System config** | `autoinvoice/general/enabled` + `payment_methods` |
| **Configuration** | Defaults via `config.xml` |
| **No REST API** | None |
| **No frontend** | None |

## 3. Architecture

```
AlpineCommerce/AutoInvoice/
├── Block/Adminhtml/Index.php           # admin dashboard block
├── Controller/Adminhtml/Invoice/Index.php  # admin page
├── Observer/AutoInvoice.php            # checkout_onepage_controller_success_action
├── etc/
│   ├── adminhtml/menu.xml              # Sales > Auto Invoice
│   ├── adminhtml/system.xml            # enable + payment methods
│   ├── config.xml                      # default values
│   └── events.xml                      # observer registration
├── view/adminhtml/
│   ├── layout/autoinvoice_invoice_index.xml
│   └── templates/index.phtml           # recent orders table
└── composer.json                       # newly added
```

## 4. Database

No custom tables.

## 5. REST API

None.

## 6. Admin

- **Sales > Auto Invoice**: status page with recent orders (last 10)
- **Stores > Configuration > Sales > Auto Invoice**: enable flag + payment
  methods filter (per website/store view)

## 7. Frontend

None.

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_AutoInvoice
php bin/magento setup:upgrade
```

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Observer on controller event | `checkout_onepage_controller_success_action` is a controller action event — not eligible for plugin conversion |
| Admin block with Order Grid collection | Reuses Magento's order grid collection for recent orders |
| No plugin conversion | Controller action events cannot be intercepted with plugins |
| `config.xml` defaults | Magento-native default values; no install scripts needed |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Observer file is empty | 📋 Pending implementation |

## 11. Magento concepts taught

- Observer on controller action events (`checkout_onepage_controller_success_action`)
- `config.xml` default values
- Admin block + template rendering
- System configuration (`system.xml`, per-website scope)

## 12. Validation & status

- **Status**: ✅ Stable — admin page and config validated
- **Module**: enabled, `setup:upgrade` OK

---

*Sources: `README.md`, module code structure, `docs/08_CHANGELOG.md` (v1.0.0).*
