# AlpineCommerce_LoyaltyProgram Module — Loyalty Program

> **Status**: ✅ Stable (v1.7.0)

## 1. Responsibility

**Loyalty program**: earning and spending points on orders, cart discount,
and incentive messaging.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Point earning** | Plugin on `InvoiceRepositoryInterface::afterSave()` |
| **Point spending** | Plugin on `OrderRepositoryInterface::afterSave()` |
| **Cart discount** | Total collector registered in `etc/sales.xml` |
| **Minicart** | Plugin on `Magento\Checkout\Block\Cart\Sidebar` |
| **Customer balance page** | Frontend route `/loyalty/customer/balance` |
| **REST API** | `POST /V1/carts/mine/loyalty-points` (`setPointsUsed`) |
| **Admin** | Menu Customers → Loyalty Balances, balance grid, points history, manual adjustment |

## 3. Architecture

```
AlpineCommerce/LoyaltyProgram/
├── Api/
│   ├── LoyaltyBalanceRepositoryInterface.php
│   ├── LoyaltyCartManagementInterface.php
│   └── Data/
│       └── LoyaltyBalanceInterface.php
├── Block/
│   └── Customer/
│       └── Balance.php                  # customer balance page
├── Controller/
│   ├── Adminhtml/
│   │   └── Balance/
│   │       ├── Index.php               # /admin/loyalty/balance/index
│   │       ├── View.php               # /admin/loyalty/balance/view
│   │       └── Adjust.php             # POST adjust points
│   └── Customer/
│       └── Balance.php                # /loyalty/customer/balance
├── Ui/
│   ├── Component/
│   │   └── Listing/
│   │       └── Column/
│   │           └── ViewAction.php     # View column for balance grid
│   └── DataProvider/
│       └── Balance.php                # DataProvider for loyalty_balance_listing
├── Model/
│   ├── Checkout/
│   │   ├── LoyaltyCartManagement.php    # setPointsUsed()
│   │   └── LoyaltyConfigProvider.php    # checkout config provider
│   ├── LoyaltyBalance.php
│   ├── LoyaltyBalanceRepository.php
│   ├── LoyaltyOrderPoints.php
│   ├── ResourceModel/
│   │   ├── LoyaltyBalance.php
│   │   ├── LoyaltyBalance/Collection.php
│   │   ├── LoyaltyOrderPoints.php
│   │   └── LoyaltyOrderPoints/Collection.php
│   └── Total/
│       └── Quote/
│           └── LoyaltyDiscount.php      # total collector
├── Plugin/
│   ├── Invoice/
│   │   └── AfterSave.php                # earning plugin
│   ├── Order/
│   │   └── AfterSave.php                # deduction plugin
│   └── LoyaltyIncentive.php             # minicart message
├── Service/
│   └── PointsCalculator.php             # pure calculation service
├── Logger/
│   ├── Logger.php
│   └── Handler/
│       └── Loyalty.php
├── etc/
│   ├── acl.xml                          # main > config > loyalty balance
│   ├── adminhtml/
│   │   ├── menu.xml                    # Customers → Loyalty Balances
│   │   ├── routes.xml                  # admin frontName: loyalty
│   │   └── system.xml                   # enable + discount_sort_order
│   ├── config.xml                       # defaults
│   ├── db_schema.xml                    # ALPINECOMMERCE_LOYALTY_*
│   ├── di.xml                           # preferences + plugins
│   ├── events.xml                       # empty
│   ├── frontend/
│   │   ├── di.xml                       # minicart plugin + config provider
│   │   └── routes.xml                   # frontName: loyalty
│   ├── module.xml
│   ├── sales.xml                        # total collector registration
│   └── webapi.xml                       # POST /V1/carts/mine/loyalty-points
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   ├── loyalty_balance_index.xml  # Balance grid page
    │   │   └── loyalty_balance_view.xml  # Balance view + history page
    │   ├── templates/
    │   │   └── balance/view.phtml        # Balance detail template
    │   └── ui_component/
    │       ├── loyalty_balance_listing.xml        # Balance grid
    │       └── loyalty_order_points_listing.xml  # Points history grid
    └── frontend/
        ├── layout/
        │   ├── checkout_index_index.xml
        │   ├── customer_account.xml
        │   └── loyalty_customer_balance.xml
        └── templates/
            └── customer/balance.phtml
```

## 4. Database

| Table | Role |
|---|---|
| `alpinecommerce_loyalty_balance` | Point balance per customer (customer_id UNIQUE FK to customer_entity) |
| `alpinecommerce_loyalty_order_points` | Points ledger per order (order_id + type UNIQUE) |
| `quote` | Added column: `alpinecommerce_loyalty_points_used` (points used at checkout) |

## 5. REST API

| Method | Path | Role |
|---|---|---|
| POST | `/V1/carts/mine/loyalty-points` | Set points used for cart (`setPointsUsed`) |

## 6. Admin

- **Menu**: Customers → Loyalty Balances (`loyalty/balance/index`)
- **Route**: `/admin/loyalty/balance` (frontName: `loyalty`)
- **Stores > Configuration > Sales > Loyalty Program**: enable flag + discount sort order (per website/store view)
- **Balance Grid**: lists customer_id, name, email, points, updated_at with View action
- **Balance View**: customer details, points history (order_id, type, points, created_at)
- **Manual Adjustment**: earn/deduct points via POST form with transaction safety
- **ACL Resources**: `AlpineCommerce_LoyaltyProgram::balance` (view), `AlpineCommerce_LoyaltyProgram::adjust` (modify)

## 7. Frontend

- Minicart: incentive message via plugin on `Magento\Checkout\Block\Cart\Sidebar`
- Checkout: discount applied by total collector (`LoyaltyDiscount`)
- Customer account: balance page at `/loyalty/customer/balance`

## 8. CLI

No dedicated command.

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Plugins (invoice/order) | Earning and deduction delegated to Magento plugins |
| Total collector via `sales.xml` | Native cart discount (extension of the total process) |
| Service class replaces Helper | `PointsCalculator` is pure calculation, no Magento dependencies |
| Removal of `InMemory/LoyaltyBalanceRepository.php` | Unnecessary — base repository sufficient |
| Removal of `InstallSchema.php` / `InstallData.php` | Replaced by `db_schema.xml` / data patches |
| Frontend route for balance | Customer can view points balance without admin |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Incorrect `referenceId` in `db_schema.xml` | ✅ Fixed — prefix `ALPINECOMMERCE_*` |
| — | Legacy files `Setup/InstallSchema.php` / `InstallData.php` | ✅ Fixed — removed |
| — | Legacy in-memory repository | ✅ Fixed — removed |
| — | Observer-to-plugin conversion complete | ✅ Done — invoice/order hooks now use plugins |
| — | Transactions / complete admin interface | ✅ Done — atomic balance mutations with transaction safety |

## 11. Magento concepts taught

- **Total collector** (`collect` on the total process, registered via `sales.xml`)
- **Plugins** (invoice, order, minicart)
- **Frontend routes** (`frontend/routes.xml`)
- **Config providers** (`CompositeConfigProvider` for checkout JS)
- **Service classes** (no Helper anti-pattern)
- **Database schema** (`db_schema.xml` with referenceId prefix)
- **Admin UI Components** (listing, dataSource, columns, actions)
- **Admin menu & routes** (`adminhtml/menu.xml`, `adminhtml/routes.xml`)
- **ACL resources** (`acl.xml` with hierarchical permissions)
- **Atomic transactions** (balance mutations with transaction safety)

## 12. Validation & status

- **Status**: ✅ Stable — functional core validated (Sprint 6), admin UI completed (v1.7.0)

---

*Sources: `docs/08_CHANGELOG.md` (v1.4.0), `SPRINT_VALIDATION_REPORT.md`,
`SPRINT_INTEGRATION_REPORT.md` (merged into `CHANGELOG.md`).*
