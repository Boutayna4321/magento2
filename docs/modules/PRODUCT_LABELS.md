# AlpineCommerce_ProductLabels Module — Product Labels

> **Status**: ✅ Stable (v1.5.0)

## 1. Responsibility

**Manageable product labels** (e.g. "New", "Sale", "Limited stock"):
visual rendering on the product page and category listings.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Admin grid** | Listing with Delete / Change status massactions + "Add New Label" button |
| **Edit form** | Name, code, colors, priority, position, validity dates, status, product selection |
| **REST API** | Label CRUD + product linking + application |
| **Frontend** | Rendering on product page and category listings (plugin `CatalogBlock`) |
| **i18n** | French translation |

## 3. Architecture

```
AlpineCommerce/ProductLabels/
├── Api/                        # Service Contracts + SearchResults
├── Block/
│   ├── Adminhtml/Label/Grid.php   # fix C7 — rewritten (native massaction)
│   └── Frontend/                  # label rendering
├── Controller/                 # Adminhtml (CRUD) + REST
├── Model/                      # Entities, repositories, ResourceModel/Collection
├── Plugin/CatalogBlock.php     # frontend display plugin
├── Observer/                   # label application (⚠️ N+1 — BACKLOG B-06 P5)
└── view/
    ├── adminhtml/ui_component/alphacommerce_product_label_listing.xml
    └── frontend/layout/catalog_product_view.xml   # referenceBlock (Sprint 6 fix)
```

## 4. Database

| Table | Role |
|---|---|
| `alphacommerce_product_label` | Labels (code, colors, priority, position, dates, status) |
| `alphacommerce_product_label_product` | Label ↔ products link |

## 5. REST API

| Route | Methods |
|---|---|
| `/V1/alphacommerce/product-labels` | GET, POST |
| `/V1/alphacommerce/product-labels/:entityId` | GET, DELETE |
| `/V1/alphacommerce/product-labels/:labelId/products` | GET, POST |
| `/V1/alphacommerce/product-labels/:productId/apply` | POST |

## 6. Admin

- Grid rewritten in 2.4.8 format: removal of `primaryDataSource`, obsolete
  `<templates><filters><select>` block, **`<dataProvider class="...">` child added**
- VirtualType data source removed from `di.xml`
- Form: `use_container => true`, action URL via `getUrl()`, `Registry` explicitly
  injected in `Edit` controller

## 7. Frontend

- Product page + category listings: label rendering via `CatalogBlock` plugin
- Sprint 6 fix: `referenceContainer` → **`referenceBlock`** for `product.info.media`
  and `product.info.details` (these are `block`, not `container` — labels never rendered)

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| `CatalogBlock` plugin for rendering | Display without touching core templates |
| Native Magento 2.4.8 Grid | The `<templates><filters><select>` block is obsolete; native massaction |
| `<dataProvider class="...">` mandatory child | `definition.map.xml` requirement (module-ui) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| C7 | `Block/Adminhtml/Label/Grid.php`: `use Magento\Backend\Block\Widget\Grid` (fatal collision), invalid ctor, renderer + non-existent constant | ✅ Fixed (Phase 1) |
| — | Grid not 2.4.8 compliant (`primaryDataSource`, `<templates><filters><select>`) | ✅ Fixed (v1.5.0) |
| — | Labels never rendered (referenceContainer on blocks) | ✅ Fixed (Sprint 6) |
| P5 | Observer: N+1 on label application | 📋 BACKLOG B-06 P5 |

## 11. Magento concepts taught

- Plugin (`around/after`) on core blocks (`CatalogBlock`)
- Native 2.4.8 admin grid (listing + massactions + Add New button)
- `referenceBlock` vs `referenceContainer`
- REST routes `:param` syntax

## 12. Validation & status

- **Status**: ✅ Stable — frontend and admin validated (Sprint 6)

---

*Sources: `docs/08_CHANGELOG.md` (v1.5.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
