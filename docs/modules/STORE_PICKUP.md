# AlpineCommerce_StorePickup Module — Store Pickup

> **Status**: 🔄 Code done — Magento validation pending (finalization sprint: Sprint 2)

## 1. Responsibility

Allow the customer to choose **store pickup** for their order. Magento has
no native store pickup in Open Source. The module adds a custom Magento
carrier, a store selection in the checkout, and admin management of pickup
points.

## 2. Scope & features

### Included (v1.0)

| Feature | Description | Priority |
|---|---|---|
| **Pickup point CRUD** | Admin: listing + form (source_code, name, address, coordinates, hours, status) | Critical |
| **Custom Magento carrier** | `Model/Carrier/StorePickup` | — |
| **Checkout selection** | Checkout integration (frontend config provider) | — |
| **ACL + Menu** | `main` > `store`, `config` — menu under Content | High |
| **REST API** | GET/POST `/V1/carts/mine/store-pickup` (self) | — |

### Assumed exclusions (v1.1)

- **Availability/time slots** per store
- **Advanced configuration** (pickup fees, delay before pickup): the base carrier
  config (`system.xml`: active, title, name, price, sort_order, sallowspecific) is sufficient

## 3. Architecture

```
AlpineCommerce/StorePickup/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / config.xml / webapi.xml   # EXISTING — unchanged
│   ├── acl.xml                    # Created (Sprint 2) — main, store, config
│   └── adminhtml/
│       ├── system.xml             # EXISTING — carrier config
│       └── menu.xml               # Created (Sprint 2)
├── Api/                           # EXISTING — StoreInfoRepositoryInterface, StoreAvailabilityInterface, StorePickupCartManagementInterface
├── Controller/Adminhtml/Store/
│   ├── Index.php                  # Created — listing
│   ├── Edit.php                   # Created — form
│   ├── Save.php                   # Created — save (delegates to Repository)
│   └── Delete.php                 # Created — delete
├── Ui/
│   ├── DataProvider/
│   │   ├── StoreInfoListingDataProvider.php   # Created — AbstractDataProvider
│   │   └── StoreInfoFormDataProvider.php      # Created — ModifierPoolDataProvider
│   └── Component/Listing/Column/Actions.php   # Created — Edit/Delete
└── view/adminhtml/
    ├── layout/alphacommerce_pickup_store_index.xml / _edit.xml
    └── ui_component/alphacommerce_pickup_store_info_listing.xml / _form.xml
```

**Golden rule**: do not touch the existing business core (carrier, checkout, REST API,
`StoreInfoRepositoryInterface`). The admin relies on it.

## 4. Database

| Table | Role |
|---|---|
| `alphacommerce_pickup_store_info` | Pickup points (source_code, name, address, lat/lng, hours, is_active) |
| `quote` / `sales_order` | Added columns (pickup point selection per order) |

No schema modification in Sprint 2 (pre-existing tables).

## 5. REST API

| Route | Method | Auth | Role |
|---|---|---|---|
| `/V1/carts/mine/store-pickup` | GET | self | Retrieve available stores |
| `/V1/carts/mine/store-pickup` | POST | self | Associate a store with the cart |

3 Service Contracts: `StoreInfoRepositoryInterface`, `StoreAvailabilityInterface`,
`StorePickupCartManagementInterface`.

## 6. Admin

- **ACL**: `AlpineCommerce_StorePickup::main` (parent) > `store`, `config`
- **Menu**: Store Pickup under `Magento_Backend::content`, `sortOrder=100`
- **Listing**: 2.4.8 UI Component (columns source_code, name, city, country_id, phone,
  is_active, Edit/Delete actions)
- **Form**: UI Component (all `StoreInfoInterface` fields, `country_id` in
  select, `source_code`/`name` required validation, numeric lat/lng)

## 7. Frontend

- Checkout: pickup point selection + config provider (key `storePickup`)
- Custom carrier in the shipping process

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Do not modify checkout core | Plugin/carrier integration already works |
| Add only the missing admin interface | Store CRUD + ACL + menu |
| Reuse `alphacommerce_pickup_store_info` + its Repository | No new entity |
| Listing via `CollectionFactory` (not `getList()`) | `StoreInfoRepositoryInterface` does not expose `getList(SearchCriteria)` — avoids modifying the Service Contract |
| v1.1 deferral | Availability/slots + advanced config |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| C9 | `alphacommerce_pickup_store_info_form.xml`: malformed XML | ✅ Fixed (Phase 1) |
| C11 | `Controller/Adminhtml/Store/{Save,Delete}.php`: `PageFactory` missing | ✅ Fixed (Phase 1) |
| P2 | `etc/adminhtml/routes.xml` missing → admin URLs `alphacommerce_pickup/*` unresolved | 📋 BACKLOG B-06 P2 |
| P3 | `etc/adminhtml/menu.xml`: item without `action` attribute (non-clickable menu) | 📋 BACKLOG B-06 P3 |

## 11. Magento concepts taught

- Custom Magento carrier (`Magento\Shipping\Model\Carrier\AbstractCarrier`)
- Checkout integration (config provider)
- UI Component listing + form (`AbstractDataProvider`, `ModifierPoolDataProvider`)
- Admin CRUD controllers (Faq pattern)
- ACL + admin menu

## 12. Validation & status

- **Finalization sprint**: Sprint 2 (analysis `18`-`19`, architecture `20`)
- **Magento validation**: pending — non-regression tests (checkout, REST)
- Residual P2/P3 issues to address in Phase 2 (see `BACKLOG.md`)

---

*Sources: docs `18_SPRINT_CAHIER_DES_CHARGES_STOREPICKUP.md`,
`19_SPRINT_ANALYSE_STOREPICKUP.md`, `20_SPRINT_ARCHITECTURE_STOREPICKUP.md`
(merged here), archive `21_SPRINT_REPORT_STOREPICKUP.md`.*
