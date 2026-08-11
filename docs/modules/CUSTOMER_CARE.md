# AlpineCommerce_CustomerCare Module — Customer Care (VIP, attributes, REST, cron)

> **Status**: ✅ Done — module enabled, compiled, verified (CLI, REST, admin grid/form, cron)

## 1. Responsibility

Manage the **customer lifecycle**: enrich the customer profile with custom
attributes, compute a **lifetime spend**, and automatically assign a **VIP
level** based on configurable thresholds. The module also exposes the customer
VIP status over **REST** and keeps it up to date via an **observer** and a
**cron job**.

## 2. Scope & features

### Included (v1.0)

| Feature | Description |
|---|---|
| **Customer attributes (EAV)** | `customer_type` (B2C/B2B/Wholesale, admin + account form), `customer_notes` (textarea, admin), `vip_level` (select, computed), `lifetime_spent` (decimal, computed) |
| **VIP program** | Bronze / Silver / Gold, thresholds configurable per website |
| **Service contracts** | `Api/CustomerCareInterface` + `Api/Data/VipStatusInterface` |
| **Observer** | `sales_order_place_after` → recompute VIP for the order's customer |
| **Cron** | `UpdateVipLevels` daily at 02:00 — recompute all customers |
| **REST API** | `/V1/customercare/*` (see §5) |
| **Admin grid** | `Customer Type`, `VIP Level`, `Lifetime Spent` columns |
| **Admin config** | Stores > Configuration > AlpineCommerce > Customer Care |

### Assumed exclusions (v1.1+)

- Price/remise % per VIP level (planned)
- Email templates (welcome, birthday) (planned)
- Frontend "My Account" VIP widget (planned)

## 3. Architecture

```
AlpineCommerce/CustomerCare/
├── Api/
│   ├── CustomerCareInterface.php          # service contract
│   └── Data/VipStatusInterface.php        # data object contract
├── Model/
│   ├── CustomerCare.php                   # service implementation
│   ├── Config.php                         # reads system config (thresholds)
│   ├── VipLevel.php                       # level constants
│   ├── VipLevelCalculator.php             # spend → level
│   ├── VipStatus.php                      # data object
│   ├── ResourceModel/LifetimeSpent.php    # SUM(grand_total) of completed orders
│   └── Attribute/Source/                  # select option sources
│       ├── CustomerType.php
│       └── VipLevelSource.php
├── Observer/OrderPlacedAfter.php          # event listener
├── Cron/UpdateVipLevels.php               # nightly recompute
├── Setup/Patch/Data/
│   ├── AddCustomerCareAttributes.php      # EAV attributes
│   ├── AddCustomerCareFormAssignments.php # customer_form_attribute rows
│   └── AddCustomerCareAttributesToSet.php # attribute set membership
├── etc/
│   ├── module.xml / di.xml / config.xml / system.xml (adminhtml)
│   ├── events.xml / crontab.xml / webapi.xml / acl.xml
├── view/adminhtml/ui_component/customer_listing.xml   # grid columns
└── i18n/en_US.csv
```

### VIP computation flow

```
sales_order_place_after (Observer)  ┐
cron:run UpdateVipLevels (daily)    ┼─> recalculateVipStatus(customerId)
REST POST /vip-status/:id           ┘
                                          │
                  LifetimeSpent->sumCompletedOrders(customerId)  (state: complete/closed)
                                          │
                    VipLevelCalculator->calculate(spent, websiteId)
                                          │
            set lifetime_spent + vip_level on the customer (EAV) and save
```

## 4. Database

No custom tables. Uses the customer EAV storage:

- `eav_attribute` (attribute_id 157-160 on this install) + `customer_eav_attribute`
- `customer_form_attribute` (admin/customer forms)
- `eav_entity_attribute` (attribute set 1 membership — required for EAV saves)
- `customer_entity_varchar` / `customer_entity_decimal` (values)
- `sales_order` (read-only: `SUM(grand_total)` where `state IN (complete, closed)`)

## 5. REST API

| Route | Method | Auth | Description |
|---|---|---|---|
| `/V1/customercare/vip-status/:customerId` | GET | admin (`::manage`) | Get a customer's VIP status |
| `/V1/customercare/me/vip-status` | GET | customer (`self`) | Current customer's VIP status |
| `/V1/customercare/vip-status/:customerId` | POST | admin | Recompute VIP for a customer |
| `/V1/customercare/recalculate-all` | POST | admin | Recompute VIP for all customers |

Response shape (`VipStatusInterface`):

```json
{
  "customer_id": 1,
  "vip_level": "bronze",
  "lifetime_spent": 244.64,
  "bronze_threshold": 100,
  "silver_threshold": 500,
  "gold_threshold": 1000
}
```

## 6. Admin

- **Customers > All Customers**: new columns `Customer Type`, `VIP Level`,
  `Lifetime Spent` (filters: select/select/range).
- **Customer edit form**: fields `Customer Type`, `Internal Notes`, `VIP
  Level`, `Lifetime Spent` under the Account Information tab.
- **Stores > Settings > Configuration > AlpineCommerce > Customer Care**:
  enable flag + Bronze/Silver/Gold thresholds.

## 7. Frontend

- `customer_type` is available on the customer account create/edit forms
  (customer form assignment) — the store theme must render it explicitly.

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_CustomerCare
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cron:run            # schedules UpdateVipLevels
```

## 9. Architecture decisions

- **Service contracts**: business logic behind `Api/` interfaces (Magento
  best practice for any module).
- **Observer over plugin**: `sales_order_place_after` is the natural hook —
  no need to intercept a specific method.
- **Cron + observer**: the observer is real-time for new orders; the nightly
  cron repairs data (e.g. orders completed offline) — idempotent and cheap.
- **EAV attributes**: customer data lives in EAV, so profile fields are
  created via `CustomerSetup` data patches.
- **Set membership is mandatory**: `user_defined=true` attributes are NOT
  auto-added to the default attribute set; without `addAttributeToSet`, EAV
  saves silently drop the value (found & fixed during validation).
- **Form wiring is manual**: `used_in_forms` only works for Magento's default
  entities; custom attributes need explicit `customer_form_attribute` rows.

## 10. Known bugs / limitations

- `recalculateAll()` loads every customer individually — fine for this shop,
  revisit for large customer bases.
- Guest orders (no `customer_id`) are ignored by the observer.
- Lifetime spend counts `complete` and `closed` states only (not `pending`/`processing`).
- The frontend theme must render `customer_type` itself; this module only
  registers the form attribute.

## 11. Magento concepts taught

- Data patches (`DataPatchInterface`, dependencies, aliases)
- Customer EAV attributes (`CustomerSetup`), form assignments, attribute sets
- Service contracts + `@api` interfaces (WebAPI serialization requires
  docblocks on interface methods)
- Observers + events (`sales_order_place_after`)
- Cron jobs (`crontab.xml`)
- System configuration (`system.xml`, `config.xml` defaults, per-website scope)
- REST routing (`webapi.xml`, `self` resource, `%customer_id%`)
- ACL resources + UI component listing columns with filters

## 12. Validation & status

- `module:enable`, `setup:upgrade` (3 data patches), `setup:di:compile`,
  `cache:flush` — OK
- **Calculator boundaries**: 50→none, 99.99→none, 100→bronze, 499.99→bronze,
  500→silver, 999.99→silver, 1000→gold, 5000→gold
- **Lifetime spend + observer**: roni (39.64 + 205.00 + 570.00 = 814.64) →
  **silver**; observer `sales_order_place_after` recomputes on each order
- **REST**: `/V1/customercare/me/vip-status` → HTTP 200 with the full status;
  admin GET/POST/recalculate-all all OK
- **Admin grid + form**: merged `customer_listing` contains the 3 new columns;
  customer edit page shows all 4 attributes
- **Cron**: `UpdateVipLevels` executes without error

---

*Sources: this session's implementation + validation logs (REST cURL, CLI PHP
scripts, `UiComponentFactory` dump).*
