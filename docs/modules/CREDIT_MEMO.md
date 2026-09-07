# AlpineCommerce_CreditMemo Module — Auto Credit Memo

> **Status**: ✅ Done (v1.0.0)

## 1. Responsibility

Automatically create **credit memos** when orders are canceled, optionally
performing automatic refunds based on payment method and configuration.

## 2. Scope & features

| Feature | Description |
|---|---|
| **Auto credit memo** | Plugin on `Magento\Sales\Model\Order::afterCancel()` |
| **Payment filter** | Comma-separated list of payment methods (empty = all) |
| **Auto refund** | Optionally process refund immediately after credit memo creation |
| **Admin page** | `autocreditmemo/creditmemo/index` — recent credit memos |
| **System config** | `autocreditmemo/general/enabled` + `payment_methods` + `auto_refund` |
| **REST API** | Process cancellation, get config |
| **Queue support** | Message queue for async processing |
| **Configuration** | Defaults via `config.xml` |

## 3. Architecture

```
AlpineCommerce/CreditMemo/
├── Api/
│   ├── CreditMemoAutomationInterface.php   # service contract
│   └── Data/
│       ├── CreditMemoMessageInterface.php
│       └── CreditMemoResultInterface.php
├── Block/Adminhtml/Index.php               # admin dashboard block
├── Controller/Adminhtml/Creditmemo/
│   └── Index.php                           # admin page
├── Model/
│   ├── Data/CreditMemoMessage.php
│   ├── Data/CreditMemoResult.php
│   └── Queue/
│       ├── CreditMemoConsumer.php          # async queue consumer
│       └── CreditMemoPublisher.php         # async queue publisher
├── Plugin/
│   └── OrderCancelPlugin.php               # Order::afterCancel() hook
├── Service/
│   └── CreditMemoService.php               # business logic extracted from plugin
├── etc/
│   ├── adminhtml/
│   │   ├── acl.xml                         # ACL resources
│   │   ├── menu.xml                        # Sales > Auto Credit Memo
│   │   └── routes.xml                      # admin route: autocreditmemo
│   ├── communication.xml                   # interface preferences
│   ├── config.xml                          # default values
│   ├── di.xml                              # plugin registration
│   ├── events.xml                          # empty (uses plugin, not observer)
│   ├── queue_consumer.xml                  # async consumer config
│   ├── queue_publisher.xml                 # async publisher config
│   ├── queue_topology.xml                  # queue topology
│   └── webapi.xml                          # REST API routes
├── Test/
│   └── Unit/Model/Queue/CreditMemoConsumerTest.php
└── view/adminhtml/
    └── layout/autocreditmemo_creditmemo_index.xml
```

## 4. Database

No custom tables. Uses Magento core `sales_creditmemo` table.

## 5. REST API

| Method | Path | Description |
|---|---|---|
| POST | `/V1/alpinecommerce/creditmemo/process/:orderId` | Process auto credit memo for an order (admin) |
| GET | `/V1/alpinecommerce/creditmemo/config/:storeId` | Get credit memo config (self) |

## 6. Admin

- **Sales > Auto Credit Memo**: recent credit memos page (last 20)
- **Stores > Configuration > Sales > Auto Credit Memo**: enable flag, payment
  methods filter, auto-refund toggle (per website/store view)

## 7. Frontend

None.

## 8. CLI

```bash
php bin/magento module:enable AlpineCommerce_CreditMemo
php bin/magento setup:upgrade
```

## 9. Architecture decisions

| Decision | Justification |
|---|---|
| Plugin on `Order::afterCancel()` | Intercepts cancellation after it succeeds, ensuring order state is already updated |
| Service class extraction | `CreditMemoService` extracted from plugin to enable REST API and queue reuse |
| REST API for admin | Allows external systems to trigger credit memo processing |
| Queue support | Async processing for high-volume stores |
| Empty `events.xml` | Uses plugin, not observer — no event registration needed |

## 10. Known bugs / limitations

| # | Problem | Status |
|---|---|---|
| — | Admin block reuses `sales_creditmemo` collection without custom filtering | 📋 Consider adding date range / payment method filter |

## 11. Magento concepts taught

- Plugins (`afterCancel` on `Magento\Sales\Model\Order`)
- Service contracts (`CreditMemoAutomationInterface`)
- REST API (`webapi.xml`)
- Message queues (`queue_consumer.xml`, `queue_publisher.xml`)
- Admin blocks with collection reuse
- System configuration (`system.xml`)
- `config.xml` defaults

## 12. Validation & status

- **Status**: ✅ Done — extracted from inline plugin to service class
- Sequence: `Magento_Sales`, `AlpineCommerce_AutoInvoice`

---

*Sources: `src/app/code/AlpineCommerce/CreditMemo/`*
