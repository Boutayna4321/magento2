# AlpineCommerce_LoyaltyProgram Module — Loyalty Program

> **Status**: 🔄 In finalization (v1.4.0)

## 1. Responsibility

**Loyalty program**: earning and spending points on orders, cart discount,
and incentive messaging.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Point earning** | Plugin: point allocation on invoice |
| **Point spending** | Plugin: deduction on order |
| **Cart discount** | Total collector |
| **Minicart** | Plugin: incentive message |
| **REST API** | `/V1/carts/mine/loyalty-points` |
| **Admin** | Admin interface in finalization |

## 3. Architecture

```
AlpineCommerce/LoyaltyProgram/
├── Api/                        # Service Contracts (points, balance)
├── Model/
│   ├── Total/                  # Total collector (cart discount)
│   └── (Repository in base — InMemory removed)
├── Plugin/
│   ├── Invoice/AfterSave.php   # earning plugin
│   ├── Order/AfterSave.php     # deduction plugin
│   └── LoyaltyIncentive.php    # minicart message
├── Service/PointsCalculator.php # pure calculation service (replaced Helper)
└── etc/db_schema.xml           # referenceId prefix ALPINECOMMERCE_*
```

## 4. Database

| Table | Role |
|---|---|
| `alpinecommerce_loyalty_balance` | Point balance per customer |
| `alpinecommerce_loyalty_order_points` | Points issued/deducted per order |

## 5. REST API

| Route | Role |
|---|---|
| `/V1/carts/mine/loyalty-points` | View/use points (cart mine) |

## 6. Admin

- Admin interface **in finalization** (v1.1 planned — see `ROADMAP.md`)
- Global admin integration validated (Sprint 6)

## 7. Frontend

- Minicart: incentive message (plugin)
- Checkout: discount applied by total collector

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Plugins (invoice/order) | Earning and deduction delegated to Magento plugins |
| Total collector | Native cart discount (extension of the total process) |
| Service class replaces Helper | PointsCalculator is pure calculation, no Magento dependencies |
| Removal of `InMemory/LoyaltyBalanceRepository.php` | Unnecessary — base repository |
| Removal of `InstallSchema.php` / `InstallData.php` | Replaced by `db_schema.xml` / data patches |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Incorrect `referenceId` in `db_schema.xml` | ✅ Fixed — prefix `ALPINECOMMERCE_*` |
| — | Legacy files `Setup/InstallSchema.php` / `InstallData.php` | ✅ Fixed — removed |
| — | Legacy in-memory repository | ✅ Fixed — removed |
| — | Observer-to-plugin conversion complete | ✅ Done — invoice/order hooks now use plugins |
| — | Transactions / complete admin interface | 📋 v1.1 — `ROADMAP.md` |

## 11. Magento concepts taught

- **Total collector** (`collect` on the total process)
- **Plugins** (invoice, order)
- **Plugin** on minicart
- **Service classes** (no Helper anti-pattern)
- Data patches + `db_schema.xml` (referenceId)

## 12. Validation & status

- **Status**: 🔄 In finalization — functional core validated (Sprint 6), admin interface to complete

---

*Sources: `docs/08_CHANGELOG.md` (v1.4.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
