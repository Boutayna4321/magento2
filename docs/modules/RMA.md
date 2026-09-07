# AlpineCommerce_Rma Module — Return Merchandise Authorization (RMA)

> **Status**: ✅ Done (v1.0.0)

## 1. Responsibility

Manage the complete **return merchandise authorization (RMA)** workflow:
customers request returns, admins approve/reject, items are received, and
refunds are processed. Also sets a **return window** on orders at placement
time.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Return window** | Observer on `sales_order_place_after` sets `rma_allowed_until` |
| **Customer request** | Frontend page for customers to submit return requests |
| **Admin workflow** | Approve, reject, mark received, refund, close RMA |
| **Return reasons** | Configurable reasons (Wrong Item, Damaged, etc.) |
| **RMA numbering** | Auto-generated RMA numbers with prefix |
| **Notifications** | Customer and admin email notifications |
| **REST API** | Full CRUD via service contracts |
| **Database** | Custom tables: `alpinecommerce_rma`, `alpinecommerce_rma_item` |
| **System config** | `rma/general/*` settings |
| **Configuration** | Defaults via `config.xml` |

## 3. Architecture

```
AlpineCommerce/Rma/
├── Api/
│   ├── Data/
│   │   ├── RmaCreationDataInterface.php
│   │   ├── RmaExtensionInterface.php
│   │   ├── RmaInterface.php
│   │   └── RmaItemInterface.php
│   ├── RmaItemRepositoryInterface.php
│   ├── RmaRepositoryInterface.php
│   └── RmaServiceInterface.php             # full workflow: create, approve, reject, receive, refund, close
├── Block/
│   ├── Adminhtml/Index.php                 # admin RMA list block
│   └── Customer/Request.php                # frontend customer orders block
├── Controller/
│   ├── Adminhtml/Rma/
│   │   ├── AbstractRma.php                 # base admin controller
│   │   ├── Approve.php
│   │   ├── Close.php
│   │   ├── Index.php
│   │   ├── MarkReceived.php
│   │   ├── Refund.php
│   │   └── Reject.php
│   └── Index/
│       └── Request.php                     # frontend return request
├── Model/
│   ├── Data/RmaCreationData.php
│   ├── Rma.php                             # main RMA model
│   ├── RmaItem.php                         # RMA item model (qty tracking)
│   ├── RmaException.php
│   ├── Rma/State.php                       # state constants
│   ├── ResourceModel/
│   │   ├── Rma.php
│   │   ├── Rma/Collection.php
│   │   ├── RmaItem.php
│   │   └── RmaItem/Collection.php
│   ├── RmaFactory.php
│   ├── RmaItemFactory.php
│   ├── RmaItemRepository.php
│   ├── RmaRepository.php
│   └── RmaSearchResults.php
├── Observer/
│   └── OrderPlaceAfter.php                 # sets return window on order
├── Service/
│   ├── ReturnWindow.php                    # return deadline calculation
│   └── RmaService.php                      # workflow: create, approve, reject, receive, refund, close
├── Test/
│   ├── Integration/Api/RmaApiTest.php
│   ├── Unit/Model/Rma/StateTest.php
│   └── Unit/Service/RmaServiceTest.php
├── etc/
│   ├── acl.xml                             # ACL resources
│   ├── adminhtml/
│   │   ├── acl.xml
│   │   ├── menu.xml                        # Sales > RMA
│   │   └── routes.xml                      # admin route: rma
│   ├── config.xml                          # default values + reasons
│   ├── db_schema.xml                       # alpinecommerce_rma, alpinecommerce_rma_item
│   ├── di.xml
│   ├── events.xml                          # sales_order_place_after
│   ├── frontend/routes.xml                 # frontend route: rma
│   └── webapi.xml                          # REST API routes
└── view/
    ├── adminhtml/layout/rma_rma_index.xml
    └── frontend/layout/rma_index_request.xml
```

## 4. Database

### `alpinecommerce_rma`

| Column | Type | Purpose |
|---|---|---|
| `rma_id` | PK | Unique RMA ID |
| `order_id` | FK to `sales_order` | Parent order |
| `customer_id` | FK to `customer_entity` | Customer who requested return |
| `status` | varchar(50) | pending, approved, rejected, closed |
| `reason` | text | Return reason |
| `created_at` | timestamp | Creation time |
| `updated_at` | timestamp | Last update time |

Indexes: `order_id`, `customer_id`

### `alpinecommerce_rma_item`

| Column | Type | Purpose |
|---|---|---|
| `item_id` | PK | Unique item ID |
| `rma_id` | FK to `alpinecommerce_rma` | Parent RMA |
| `order_item_id` | FK to `sales_order_item` | Original order item |
| `product_name` | varchar(255) | Product name at time of return |
| `sku` | varchar(255) | Product SKU |
| `qty_ordered` | decimal | Quantity originally ordered |
| `qty_invoiced` | decimal | Quantity invoiced |
| `qty_shipped` | decimal | Quantity shipped |
| `qty_requested` | decimal | Quantity customer wants to return |
| `qty_approved` | decimal | Quantity admin approved |
| `qty_received` | decimal | Quantity physically received |
| `qty_refunded` | decimal | Quantity refunded |
| `created_at` | timestamp | Creation time |

## 5. REST API

| Method | Path | Description |
|---|---|---|
| GET | `/V1/alpinecommerce/rmas` | List RMAs (admin) |
| GET | `/V1/alpinecommerce/rmas/:rmaId` | Get RMA by ID (admin) |
| POST | `/V1/alpinecommerce/rmas` | Create RMA (customer self) |
| GET | `/V1/alpinecommerce/rmas/order/:orderId` | Get RMAs for order (admin) |
| GET | `/V1/alpinecommerce/rmas/customer/me` | Get RMAs for logged-in customer (self) |
| PUT | `/V1/alpinecommerce/rmas/:rmaId/approve` | Approve RMA (admin) |
| PUT | `/V1/alpinecommerce/rmas/:rmaId/reject` | Reject RMA (admin) |
| POST | `/V1/alpinecommerce/rmas/:rmaId/receive` | Mark items received (admin) |
| POST | `/V1/alpinecommerce/rmas/:rmaId/refund` | Process refund (admin) |
| PUT | `/V1/alpinecommerce/rmas/:rmaId/close` | Close RMA (admin) |

## 6. Admin

- **Sales > RMA**: list of all RMA requests with status filters
- **Sales > RMA > Approve/Reject/Refund/Close**: action buttons per RMA
- **Stores > Configuration > Sales > RMA (Returns)**: enable, return days,
  approval requirement, auto-generation, prefix, notifications

## 7. Frontend

- **Customer return request**: `rma/index/request` — customers select an order
  and submit a return request
- Requires customer login
- Validates return window (`rma_allowed_until` set by observer)
- Checks for existing pending RMA on the same order

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_Rma
php bin/magento setup:upgrade
```

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Observer on `sales_order_place_after` | Sets return window immediately after order placement |
| Service class `RmaService` | Encapsulates full workflow: create, approve, reject, receive, refund, close |
| `ReturnWindow` service | Single source of truth for deadline calculation, reused in observer and controllers |
| Custom DB tables | `alpinecommerce_rma` and `alpinecommerce_rma_item` for full RMA tracking |
| Frontend controller | Customer-initiated return requests without admin intervention |
| REST API | Full workflow exposed for headless/integration scenarios |
| State machine | Explicit status constants (pending, approved, rejected, closed) |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | No email template customization — uses default Magento notification system | 📋 Consider adding email templates |
| — | No shipping label generation | 📋 Future enhancement |

## 11. Magento concepts taught

- Observers (`sales_order_place_after`)
- Service contracts (`RmaServiceInterface`, `RmaRepositoryInterface`, `RmaItemRepositoryInterface`)
- REST API (full CRUD via `webapi.xml`)
- Custom database tables (`db_schema.xml`)
- Frontend + Admin controllers
- Admin blocks with collection reuse
- ACL (`acl.xml`)
- System configuration (`system.xml`)
- State machine pattern

## 12. Validation & status

- **Status**: ✅ Done — full RMA workflow implemented
- Sequence: `Magento_Sales`, `Magento_Customer`, `AlpineCommerce_AutoInvoice`
- Integration tests: `Test/Integration/Api/RmaApiTest.php`
- Unit tests: `Test/Unit/Model/Rma/StateTest.php`, `Test/Unit/Service/RmaServiceTest.php`

---

*Sources: `src/app/code/AlpineCommerce/Rma/`*
