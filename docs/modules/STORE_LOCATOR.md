# AlpineCommerce_StoreLocator Module — Store Locator

> **Status**: 🔄 Code done — Magento validation pending (finalization sprint: Sprint 3)

## 1. Responsibility

Allow visitors to **find the brand's physical stores**: admin management interface (CRUD)
and frontend listing page. Module **completely independent** of
`AlpineCommerce_StorePickup` (no cross-imports, distinct data).

## 2. Scope & features

### Included (v1.0)

| Feature | Description | Priority |
|---|---|---|
| **Admin store CRUD** | Listing + form (name, address, city, country, postcode, lat/lng, hours, status) | Critical |
| **Frontend page** | `/store-locator` route — listing of active stores | Critical |
| **Frontend filters** | Client-side search by name and city | High |
| **Google Maps link** | Link to Google Maps from each store (if lat/lng provided) | High |
| **Hierarchical ACL + Menu** | "Store Locator" menu under Content | High |

### Assumed exclusions (v1.1)

- **Detail page** per store (dedicated route)
- **Interactive map** (JS map integration)
- **Proximity search** (visitor geolocation)
- **Automatic geolocation** of visitor
- **System configuration** (hardcoded values)
- **REST API** (no Service Contract exposed in v1.0)
- **Import/export** of stores

Review verdict (archive 26): module **functionally OK for v1.0**, these gaps are
non-blocking.

## 3. Architecture

```
AlpineCommerce/StoreLocator/
├── etc/
│   ├── module.xml / db_schema.xml / di.xml / config.xml
│   ├── acl.xml                    # Created — main > store
│   └── adminhtml/menu.xml         # Created — Store Locator under content
├── Api/
│   ├── StoreInterface.php         # Created — Service Contract Data
│   └── StoreRepositoryInterface.php  # Created — Repository Pattern
├── Model/
│   ├── Store.php                  # Created
│   ├── StoreRepository.php        # Created — CollectionProcessorInterface
│   ├── ResourceModel/Store.php    # Created
│   ├── ResourceModel/Store/Collection.php  # Created
│   └── Status.php                 # Created — OptionSourceInterface
├── Controller/
│   ├── Adminhtml/Store/{Index,Edit,Save,Delete}.php  # Created — admin CRUD
│   └── Index/Index.php            # Created — frontend page
├── Block/
│   ├── Adminhtml/Store/Edit/*     # Created — buttons (GenericButton, SaveButton, BackButton)
│   └── StoreLocator.php           # Created — getStores(): array
├── Ui/
│   ├── DataProvider/
│   │   ├── StoreListingDataProvider.php  # Created
│   │   └── StoreFormDataProvider.php     # Created
│   └── Component/Listing/Column/Actions.php  # Created
└── view/
    ├── adminhtml/ui_component/alphacommerce_store_locator_store_{listing,form}.xml  # Created, XSD-valid
    ├── adminhtml/layout/..._index.xml / ..._edit.xml        # Created
    └── frontend/
        ├── layout/alphacommerce_store_locator_index_index.xml  # Created
        └── templates/store-locator.phtml                       # Created
```

**Golden rule**: complete independence from StorePickup — no
`AlpineCommerce\StorePickup\*` class imported. "Repository Pattern"
reference architecture for other modules.

## 4. Database

| Table | Role |
|---|---|
| `alphacommerce_store_locator_store` | Stores (name, address, city, country_id, postcode, lat, lng, hours, is_active) |

`db_schema.xml` created in Sprint 3 (module's main table).

## 5. REST API

**None in v1.0** — assumed exclusions (no Service Contract exposed). Deferred to v1.1.

## 6. Admin

- **ACL**: `AlpineCommerce_StoreLocator::main` (parent) > `store`
- **Menu**: Store Locator under `Magento_Backend::content`, hierarchical
- **Listing**: UI Component — **ID, Name, City, Country, Status** filters,
  Actions column (Edit/Delete)
- **Form**: UI Component XSD-valid (cf. fixed C8: `optionsclass` removed,
  orphaned `<formElements>` removed, `country_id` present, `</label>` repaired)
- **Buttons**: `ButtonProviderInterface` classes (GenericButton/SaveButton/BackButton)

## 7. Frontend

- Frontend route `/store-locator` (HTTP 200) — listing of active stores
- **Client-side filter** by name and city (JS) — no reload
- **Google Maps link** per store (lat/lng) if provided
- `StoreLocator::getStores()` block: returns **`array`** (fix CRIT-2)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Complete independence vs StorePickup | Both modules manage stores but have distinct data models and usages; zero coupling |
| Complete Repository Pattern | `StoreInterface` + `StoreRepositoryInterface` + ResourceModel/Collection (reference module) |
| `CollectionProcessorInterface` in `getList()` | Respect of Service Contract SearchCriteria (fix CRIT-1) |
| Client-side filters and search | v1.0 simplification (no server-side pagination) |
| v1.1 exclusions | Map, proximity, geoloc, config, REST, import/export (non-blocking) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| CRIT-1 | `Model/StoreRepository.php::getList()`: direct `SearchCriteriaBuilder` — the builder does not filter the collection | ✅ Fixed — `CollectionProcessorInterface` injected |
| CRIT-2 | `Block/StoreLocator.php::getStores()` returns a `Collection` instead of an `array` | ✅ Fixed — `array` return |
| CRIT-3 | Critical bug discovered in review (Sprint 3) | ✅ Fixed — cf. archive `27_SPRINT_REPORT_STORELOCATOR_FIXES.md` |
| C8 | `alphacommerce_store_locator_store_form.xml` malformed | ✅ Fixed (Phase 1) — rewritten, XSD-validated |
| C10 | `store-locator.phtml:7` — `getSize()` on an `array` (fatal) | ✅ Fixed — `count($stores)` |
| C11 | `Controller/Adminhtml/Store/{Save,Delete}.php`: `PageFactory` missing (fatal) | ✅ Fixed (Phase 1) |
| D2 | `StoreRepository.php`: `StoreInterfaceFactory` without `use` (fatal di:compile) | ✅ Fixed (Phase 1) |
| — | Non-blocking v1.1: detail page, interactive map, proximity, auto geoloc, config, REST, import/export | 📋 v1.1 |

## 11. Magento concepts taught

- **Service Contracts + Repository Pattern** (reference module)
- **CollectionProcessorInterface** for `getList(SearchCriteria)` (CRIT-1 discovery)
- UI Component listing + admin form (XSD-valid)
- Frontend Layout XML + block + template
- Hierarchical ACL + menu
- `ButtonProviderInterface` buttons

## 12. Validation & status

- **Finalization sprint**: Sprint 3 (analysis `22`-`23`, architecture `24`, review `26`,
  fixes `27`)
- **Review verdict**: functionally OK v1.0 (code review complete)
- **Magento validation**: pending — non-regression tests (frontend routes, admin
  CRUD, filters) are part of global validation (Sprint 5)

---

*Sources: docs `22_SPRINT_CAHIER_DES_CHARGES_STORELOCATOR.md`,
`23_SPRINT_ANALYSE_STORELOCATOR.md`, `24_SPRINT_ARCHITECTURE_STORELOCATOR.md`,
`26_SPRINT_REVUE_STORELOCATOR.md` (merged here), archives `25_SPRINT_REPORT_STORELOCATOR.md`
and `27_SPRINT_REPORT_STORELOCATOR_FIXES.md`.*
