# AlpineCommerce_StoreSetup Module — Store Setup

> **Status**: ✅ Stable (v1.1.0)

## 1. Responsibility

Provide the project's **base store configuration** (payment methods, shipping
methods, currencies, locales, default store views) and shared Magento observers
(product save, order placed, checkout, customer login). This module replaces
the former `AlpineCommerce_Training` demo module and is now a **production
infrastructure module**.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Default configuration** | `config.xml` sets: payment (checkmo, banktransfer), shipping (flatrate 5.00, freeshipping >= 50.00, tablerate disabled), currencies, locales |
| **Store view configuration** | Default website (UK, GBP, EUR) + store views: french (fr_FR/EUR), german (de_DE/EUR), spanish (es_ES/EUR), default_fr (fr_FR/EUR) |
| **Observers** | `product_save_after`, `order_place_after`, `checkout_onepage_controller_success_action`, `customer_login` |
| **Frontend block** | `StoreInfo` block displays current store name/id/url/currency |
| **Helper** | `Data` helper wraps store/config access + logging |

### Assumed exclusions

- No admin CRUD interface.
- No REST API.
- No database tables of its own (configuration-only module).

## 3. Architecture

```
AlpineCommerce/StoreSetup/
├── etc/
│   ├── module.xml                        # sequence: Magento_Store, Magento_Backend, Magento_Catalog, Magento_Sales, Magento_Inventory
│   ├── config.xml                        # default config values (payment, shipping, currency, store views)
│   ├── di.xml                            # observers + block/helper preferences
│   └── system.xml                        # admin system config (demo)
├── Block/StoreInfo.php                   # frontend block
├── Helper/Data.php                       # store/config helper
├── Observer/                             # product, order, checkout, customer_login
├── Setup/Patch/Data/CreateStores.php     # store view creation (demo data)
└── view/frontend/                        # templates + i18n
```

## 4. Database

No dedicated tables. `CreateStores.php` Data Patch inserts store views/groups
into `core_store` / `core_store_group`.

## 5. REST API

None.

## 6. Admin

- System configuration under `storesetup` node (demo).

## 7. Frontend

- `StoreInfo` block available in layouts/templates.

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Rename from `Training` to `StoreSetup` | The module graduated from demo/training to production store configuration |
| Configuration-only via `config.xml` | Magento-native way to ship default values; no install scripts needed |
| Observers for cross-cutting concerns | Pedagogical examples + functional hooks for other modules |
| `CreateStores.php` kept as Data Patch | Reproducible demo store views; see BACKLOG B-08 for production concern |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| B-08 | `CreateStores.php` Data Patch creates store views permanently — irreversible/undesirable in production | 📋 BACKLOG B-08 — remove/transform |

## 11. Magento concepts taught

- `config.xml` default values (payment, shipping, currency)
- Multi-store configuration (store views, locales, currencies)
- Observers (`product_save_after`, `order_place_after`, `checkout_onepage_controller_success_action`, `customer_login`)
- Data Patches for store creation
- Frontend block + helper pattern

## 12. Validation & status

- **Status**: ✅ Stable — renamed from Training (v1.1.0), global validation OK (Sprint 6)
- Used as project-wide configuration baseline (shipping methods, store views)

---

*Sources: commit `a895e96` (rename Training → StoreSetup), `docs/08_CHANGELOG.md` (v1.3.0),
`SPRINT_VALIDATION_REPORT.md`, `SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
