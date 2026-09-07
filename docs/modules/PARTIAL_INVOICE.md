# AlpineCommerce_PartialInvoice Module — Partial Invoice

> **Status**: 📝 Planned (v1.0.0)

## 1. Responsibility

Automatically create **partial invoices** for available (in-stock) items when
orders are placed, respecting backorder settings and minimum quantity thresholds.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Auto partial invoicing** | Observer on `checkout_onepage_controller_success_action` |
| **Item-level qty calculation** | Invoices only items with available quantity above threshold |
| **Backorder control** | Optionally skip backordered items |
| **Min qty threshold** | Minimum quantity per item to trigger partial invoice |
| **Payment filter** | Comma-separated list of payment methods (empty = all) |
| **Admin page** | `partialinvoice/partialinvoice/index` — recent partial invoices |
| **System config** | `partialinvoice/general/enabled` + `payment_methods` + `allow_backorders` + `min_qty_to_invoice` |
| **REST API** | Process order, get config, check eligibility |
| **Queue support** | Message queue for async processing |
| **Configuration** | Defaults via `config.xml` |

## 3. Architecture

```
AlpineCommerce/PartialInvoice/
├── Api/
│   ├── PartialInvoiceInterface.php          # service contract
│   └── Data/
│       └── PartialInvoiceMessageInterface.php
├── Block/Adminhtml/Index.php                # admin dashboard block
├── Controller/Adminhtml/Partialinvoice/
│   └── Index.php                            # admin page
├── Model/
│   ├── Data/PartialInvoiceMessage.php
│   └── Queue/
│       ├── PartialInvoiceConsumer.php       # async queue consumer
│       └── PartialInvoicePublisher.php      # async queue publisher
├── Observer/
│   └── AutoPartialInvoice.php               # checkout_onepage_controller_success_action
├── Service/
│   └── PartialInvoiceService.php            # business logic extracted from observer
├── etc/
│   ├── adminhtml/
│   │   ├── acl.xml                          # ACL resources
│   │   ├── menu.xml                         # Sales > Partial Invoice
│   │   └── routes.xml                       # admin route: partialinvoice
│   ├── communication.xml                    # interface preferences
│   ├── config.xml                           # default values
│   ├── di.xml                               # preferences
│   ├── events.xml                           # observer registration
│   ├── queue_consumer.xml                   # async consumer config
│   ├── queue_publisher.xml                  # async publisher config
│   ├── queue_topology.xml                   # queue topology
│   └── webapi.xml                           # REST API routes
├── Test/
│   └── Unit/Model/Queue/PartialInvoiceConsumerTest.php
└── view/adminhtml/
    └── layout/partialinvoice_partialinvoice_index.xml
```

## 4. Database

No custom tables. Uses Magento core `sales_invoice` table.

## 5. REST API

| Method | Path | Description |
|---|---|---|
| POST | `/V1/alpinecommerce/partialinvoice/process/:orderId` | Process partial invoice for an order (admin) |
| GET | `/V1/alpinecommerce/partialinvoice/config/:storeId` | Get partial invoice config (self) |
| GET | `/V1/alpinecommerce/partialinvoice/can-invoice/:orderId` | Check if order is eligible (admin) |

## 6. Admin

- **Sales > Partial Invoice**: recent partial invoices page (last 20)
- **Stores > Configuration > Sales > Partial Invoice**: enable flag, payment
  methods filter, allow backorders, min qty threshold (per website/store view)

## 7. Frontend

None.

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_PartialInvoice
php bin/magento setup:upgrade
```

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Observer on controller event | `checkout_onepage_controller_success_action` fires after checkout success, when order is fully placed |
| Item-level qty calculation | Supports partial invoicing for specific items based on stock availability |
| Service class extraction | `PartialInvoiceService` extracted from observer to enable REST API and queue reuse |
| Backorder filtering | Prevents invoicing items not yet in stock unless explicitly allowed |
| Min qty threshold | Avoids creating invoices for trivial quantities |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Admin block uses `base_grand_total < 0` heuristic to identify partial invoices — may miss some | 📋 Consider more robust detection |

## 11. Magento concepts taught

- Observers (`checkout_onepage_controller_success_action`)
- Service contracts (`PartialInvoiceInterface`)
- REST API (`webapi.xml`)
- Message queues (`queue_consumer.xml`, `queue_publisher.xml`)
- Item-level invoice preparation (`InvoiceService::prepareInvoice` with qty array)
- System configuration (`system.xml`)
- `config.xml` defaults

## 12. Validation & status

- **Status**: 📝 Planned — extracted from inline observer to service class
- Sequence: `Magento_Sales`, `AlpineCommerce_AutoInvoice`

---

*Sources: `src/app/code/AlpineCommerce/PartialInvoice/`*
