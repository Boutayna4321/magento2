# AlpineCommerce — Order Lifecycle Modifications

> **Target audience**: developers who want to understand **exactly what happens**
> inside AlpineCommerce custom modules when an order is placed, invoiced,
> canceled, or refunded.
> This document covers **AlpineCommerce modules currently present in
> `src/app/code/AlpineCommerce/`**. It does not cover Magento 2 Core behavior.

---

## Table of Contents

1. [What This Document Covers](#1-what-this-document-covers)
2. [AlpineCommerce Modules — Big Picture](#2-alpinecommerce-modules--big-picture)
3. [AutoInvoice — Automatic Invoice Creation](#3-autoinvoice--automatic-invoice-creation)
4. [PartialInvoice — Automatic Partial Invoice Creation](#4-partialinvoice--automatic-partial-invoice-creation)
5. [CreditMemo — Automatic Credit Memo on Cancel](#5-creditmemo--automatic-credit-memo-on-cancel)
6. [CustomerCare — VIP Recalculation After Order](#6-customercare--vip-recalculation-after-order)
7. [LoyaltyProgram — Points Deduction on Order Save](#7-loyaltyprogram--points-deduction-on-order-save)
8. [Rma — Return Window on Order Placement](#8-rma--return-window-on-order-placement)
9. [StorePickup — Store Pickup Shipping Method](#9-storepickup--store-pickup-shipping-method)
10. [Modules That Do NOT Modify Order Lifecycle](#10-modules-that-do-not-modify-order-lifecycle)
11. [Events Hooked by AlpineCommerce](#11-events-hooked-by-alpinecommerce)
12. [Common Problems and Debugging](#12-common-problems-and-debugging)

---

## 1. What This Document Covers

### Business Perspective

AlpineCommerce custom modules extend Magento 2's order lifecycle with
automated post-order actions: auto-invoicing, auto-partial-invoicing,
auto-credit-memo generation on cancellation, loyalty points management, and
customer VIP level recalculation. Some modules also modify the checkout
experience (store pickup) and extend orders with return windows (RMA).

### Technical Perspective

Technically, AlpineCommerce modules hook into Magento 2 events and plugins
to execute business logic at specific lifecycle points:

- **`sales_order_place_after`** — Order placed, but before it is fully saved
- **`checkout_onepage_controller_success_action`** — Checkout success
- **`Order::afterCancel()` plugin** — After order cancellation
- **`OrderRepositoryInterface::afterSave()` plugin** — After order saved

### Modules Included

This document covers **7 modules** that directly modify order behavior:

| Module | Order Lifecycle Impact |
|--------|------------------------|
| `AlpineCommerce_AutoInvoice` | Auto-creates invoices after order placement |
| `AlpineCommerce_PartialInvoice` | Auto-creates partial invoices on checkout success |
| `AlpineCommerce_CreditMemo` | Auto-creates credit memo on order cancellation |
| `AlpineCommerce_CustomerCare` | Recalculates VIP status after order placement |
| `AlpineCommerce_LoyaltyProgram` | Deducts loyalty points after order save |
| `AlpineCommerce_Rma` | Sets return window on order placement |
| `AlpineCommerce_StorePickup` | Adds store pickup shipping method |

### Modules NOT Covered Here

The following AlpineCommerce modules do **not** modify order lifecycle:

`Blog`, `Faq`, `ProductLabels`, `ProductQuestions`, `ProductReviews`,
`CustomerGrid`, `EuVat`, `Gdpr`, `HealthCheck`, `Hreflang`, `LegalPages`,
`StoreLocator`, `StoreSetup`, `Test`

---

## 2. AlpineCommerce Modules — Big Picture

### Order Lifecycle with AlpineCommerce Extensions

```
Standard Magento Order Lifecycle
    │
    ▼
Order Placed (sales_order_place_after)
    │
    ├──► Rma: Set return window (rma_allowed_until, rma_enabled)
    │
    ├──► AutoInvoice: Try to create full invoice (if enabled)
    │
    └──► CustomerCare: Recalculate VIP status (Order::afterPlace plugin)
    │
    ▼
Order Saved (OrderRepositoryInterface::afterSave)
    │
    └──► LoyaltyProgram: Deduct loyalty points used at checkout
    │
    ▼
Checkout Success (checkout_onepage_controller_success_action)
    │
    └──► PartialInvoice: Try to create partial invoice for available items
    │
    ▼
Order Canceled (Order::afterCancel plugin)
    │
    └──► CreditMemo: Auto-create credit memo and optionally refund
    │
    ▼
Shipping Selection
    │
    └──► StorePickup: Filter shipping methods, offer store pickup
```

### Module Communication

AlpineCommerce modules operate **independently** — they do not communicate
with each other. Each module listens to events or applies plugins without
knowledge of other AlpineCommerce modules. This makes them loosely coupled
and individually enable/disable-able.

---

## 3. AutoInvoice — Automatic Invoice Creation

### Module: `AlpineCommerce_AutoInvoice`

### Responsibility

Automatically create **invoices** when orders are placed, filtered by payment
method and scoped per website/store view.

### Events Hooked

| Event | Observer | File |
|-------|----------|------|
| `sales_order_place_after` | `AutoInvoice` | `Observer/AutoInvoice.php` |

### Flow

```
sales_order_place_after fired
    │
    ▼
Observer checks conditions
    │   ├── Is module enabled? (autoinvoice/general/enabled)
    │   ├── Is payment method allowed? (autoinvoice/general/payment_methods)
    │   ├── Is order already invoiced? (total_invoiced > 0)
    │   └── Can order be invoiced? (order->canInvoice())
    │
    ▼
[If all conditions pass]
    │
    ▼
InvoiceService::prepareInvoice($order)
    │
    ▼
Invoice configured
    │   ├── CAPTURE_ONLINE (requested capture case)
    │   ├── register() — marks as paid
    │   └── setIsPaid(true)
    │
    ▼
Order + Invoice saved
    │   └── Status history comment added: "Invoice #X created automatically."
```

### Configuration

| Path | Scope | Purpose |
|------|-------|---------|
| `autoinvoice/general/enabled` | Website/Store | Enable/disable module |
| `autoinvoice/general/payment_methods` | Website/Store | Comma-separated list of allowed payment methods (empty = all) |

### Code Reference

**File**: `src/app/code/AlpineCommerce/AutoInvoice/Observer/AutoInvoice.php`

```php
public function execute(Observer $observer): void
{
    $order = $observer->getEvent()->getOrder();

    if (!$order instanceof OrderInterface) {
        return;
    }

    if ((int) $order->getTotalInvoiced() > 0) {
        return; // Already invoiced
    }

    $storeId = (int) $order->getStoreId();

    if (!$this->isModuleEnabled($storeId)) {
        return;
    }

    $paymentMethod = (string) $order->getPayment()->getMethodInstance()->getCode();
    if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
        return;
    }

    if (!$order->canInvoice()) {
        return;
    }

    // ... create invoice
}
```

### Impact on Order Lifecycle

- **Before**: Order placed → pending_payment or processing (manual invoicing)
- **After**: Order placed → processing (invoice auto-created if conditions met)

### Interaction with Other Modules

- **PartialInvoice**: Both try to invoice. `AutoInvoice` checks `total_invoiced > 0`
  to avoid double-invoicing. If `PartialInvoice` runs first, `AutoInvoice` skips.
- **CreditMemo**: No direct interaction. Credit memo is created on cancellation.

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Observer on `sales_order_place_after` | Reliable hook after order is fully placed |
| `CAPTURE_ONLINE` capture case | Attempts online capture for supported payment methods |
| `setIsPaid(true)` | Marks invoice as paid immediately |
| Payment method filter | Some payment methods (e.g., offline) should not be auto-invoiced |

---

## 4. PartialInvoice — Automatic Partial Invoice Creation

### Module: `AlpineCommerce_PartialInvoice`

### Responsibility

Automatically create **partial invoices** for available (in-stock) items when
orders are placed, respecting backorder settings and minimum quantity thresholds.

### Events Hooked

| Event | Observer | File |
|-------|----------|------|
| `checkout_onepage_controller_success_action` | `AutoPartialInvoice` | `Observer/AutoPartialInvoice.php` |

> **Note**: Uses `checkout_onepage_controller_success_action` instead of
> `sales_order_place_after`. This is a **controller action event**, not an
> entity event. It fires after checkout success, when the order is already
> placed and saved.

### Flow

```
checkout_onepage_controller_success_action fired
    │
    ▼
Observer checks conditions
    │   ├── Is module enabled? (partialinvoice/general/enabled)
    │   ├── Is payment method allowed? (partialinvoice/general/payment_methods)
    │   ├── Is order already invoiced? (total_invoiced > 0)
    │   └── Can order be invoiced? (order->canInvoice())
    │
    ▼
Calculate items to invoice
    │   For each order item:
    │   ├── Calculate qty available = qty_ordered - qty_invoiced
    │   ├── Check min_qty_to_invoicethreshold
    │   ├── Check backorder settings
    │   └── Add to invoice list if criteria met
    │
    ▼
[If items available]
    │
    ▼
InvoiceService::prepareInvoice($order, $itemsQty)
    │
    ▼
Invoice configured
    │   ├── CAPTURE_ONLINE
    │   ├── register()
    │   └── setIsPaid(true)
    │
    ▼
Order + Invoice saved
    │   └── Status history: "Partial Invoice #X created automatically for N item(s)."
```

### Configuration

| Path | Scope | Purpose |
|------|-------|---------|
| `partialinvoice/general/enabled` | Website/Store | Enable/disable module |
| `partialinvoice/general/payment_methods` | Website/Store | Comma-separated payment methods (empty = all) |
| `partialinvoice/general/allow_backorders` | Website/Store | Allow invoicing backordered items |
| `partialinvoice/general/min_qty_to_invoice` | Website/Store | Minimum quantity threshold to trigger partial invoice |

### Code Reference

**File**: `src/app/code/AlpineCommerce/PartialInvoice/Observer/AutoPartialInvoice.php`

```php
foreach ($order->getAllItems() as $item) {
    $qtyOrdered = (float) $item->getQtyOrdered();
    $qtyInvoiced = (float) $item->getQtyInvoiced();
    $qtyAvailable = $qtyOrdered - $qtyInvoiced;

    if ($qtyAvailable <= $minQty) {
        continue;
    }

    if (!$allowBackorders && !$this->canInvoiceItem($item)) {
        continue;
    }

    $itemsQty[$item->getItemId()] = $qtyAvailable;
}
```

### Impact on Order Lifecycle

- **Before**: Order placed → all items pending invoicing
- **After**: Order placed → available items invoiced, backordered items pending

### Interaction with Other Modules

- **AutoInvoice**: Both try to invoice. `PartialInvoice` runs on checkout success
  (after order is saved), while `AutoInvoice` runs on `sales_order_place_after`
  (during order placement). In practice, `AutoInvoice` may create a full invoice
  first, causing `PartialInvoice` to skip due to `total_invoiced > 0`.

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Observer on `checkout_onepage_controller_success_action` | Fires after order is fully placed and saved |
| Item-level qty calculation | Supports partial invoicing for specific items |
| Backorder filtering | Prevents invoicing items not yet in stock |
| Min qty threshold | Avoids creating invoices for trivial quantities |

---

## 5. CreditMemo — Automatic Credit Memo on Cancel

### Module: `AlpineCommerce_CreditMemo`

### Responsibility

Automatically create **credit memos** when orders are canceled, optionally
performing automatic refunds based on payment method and configuration.

### Events Hooked

| Event | Plugin | File |
|-------|--------|------|
| `Order::afterCancel()` | `OrderCancelPlugin` | `Plugin/OrderCancelPlugin.php` |

> **Note**: Uses a **plugin** (`afterCancel`) rather than an observer. This
> intercepts the `Order::cancel()` method after it executes.

### Flow

```
Admin clicks "Cancel" on order
    │
    ▼
Order::cancel() executed (core)
    │
    ▼
Plugin::afterCancel() executed
    │
    ▼
Plugin checks conditions
    │   ├── Was cancellation successful? (result = true)
    │   ├── Is module enabled? (autocreditmemo/general/enabled)
    │   ├── Is payment method allowed? (autocreditmemo/general/payment_methods)
    │   └── Can order be credited? (order->canCreditmemo())
    │
    ▼
Calculate refund quantities
    │   For each item:
    │   └── qty_to_refund = qty_ordered - qty_refunded
    │
    ▼
CreditmemoFactory::createByOrder($order, ['qtys' => $qtys])
    │
    ▼
Credit memo configured
    │   ├── refundToStoreCreditAmount = 0
    │   ├── Comment text set
    │   ├── Customer note set
    │   └── Customer note notify = false
    │
    ▼
[If auto_refund enabled]
    │   └── CreditmemoService::refund($creditmemo)
    │
    [If auto_refund disabled]
    │   └── Creditmemo::STATE_OPEN, saved without refund
    │
    ▼
Order + Credit memo saved
```

### Configuration

| Path | Scope | Purpose |
|------|-------|---------|
| `autocreditmemo/general/enabled` | Website/Store | Enable/disable module |
| `autocreditmemo/general/payment_methods` | Website/Store | Comma-separated payment methods (empty = all) |
| `autocreditmemo/general/auto_refund` | Website/Store | Automatically process refund after credit memo creation |

### Code Reference

**File**: `src/app/code/AlpineCommerce/CreditMemo/Plugin/OrderCancelPlugin.php`

```php
public function afterCancel(Order $subject, bool $result): bool
{
    if (!$result) {
        return $result;
    }

    $storeId = (int) $subject->getStoreId();
    if (!$this->isModuleEnabled($storeId)) {
        return $result;
    }

    $payment = $subject->getPayment();
    if (!$payment) {
        return $result;
    }

    $paymentMethod = (string) $payment->getMethodInstance()->getCode();
    if (!$this->isPaymentMethodAllowed($paymentMethod, $storeId)) {
        return $result;
    }

    if (!$subject->canCreditmemo()) {
        return $result;
    }

    // ... create credit memo
}
```

### Impact on Order Lifecycle

- **Before**: Order canceled → items returned to stock, no refund document created
- **After**: Order canceled → credit memo created, optionally refunded

### Interaction with Other Modules

- **AutoInvoice**: No direct interaction. If order was auto-invoiced, credit memo
  can reference that invoice for refund.
- **PartialInvoice**: Same — credit memo is created regardless of which invoice
  type was generated.

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Plugin on `Order::afterCancel()` | Intercepts cancellation after it succeeds |
| Separate credit memo from refund | Allows manual review before funds are returned |
| Payment method filter | Some payment methods (e.g., offline) may not support auto-refund |
| `createByOrder()` factory | Creates credit memo with correct item quantities |

---

## 6. CustomerCare — VIP Recalculation After Order

### Module: `AlpineCommerce_CustomerCare`

### Responsibility

Recalculate customer **VIP status** after each order is placed, based on
lifetime spend and configurable thresholds.

### Events Hooked

| Event | Plugin | File |
|-------|--------|------|
| `Order::afterPlace()` | `AfterPlace` | `Plugin/Order/AfterPlace.php` |

> **Note**: Uses a **plugin** on `Magento\Sales\Model\Order::afterPlace()`. This
> fires after the order is placed, before it is fully saved.

### Flow

```
Order::afterPlace() executed
    │
    ▼
Plugin checks conditions
    │   └── Order has customer_id? (VIP only for registered customers)
    │
    ▼
CustomerCare::recalculateVipStatus(customerId)
    │
    ▼
Calculate lifetime spend (SUM of completed orders)
    │
    ▼
Determine VIP level based on thresholds
    │   Bronze / Silver / Gold
    │
    ▼
Update customer attributes (vip_level, lifetime_spent)
    │
    ▼
Order returned (unchanged)
```

### Configuration

| Path | Scope | Purpose |
|------|-------|---------|
| `customercare/general/vip_thresholds` | Website | Thresholds for Bronze/Silver/Gold levels |
| `customercare/general/vip_enabled` | Website | Enable/disable VIP calculation |

### Code Reference

**File**: `src/app/code/AlpineCommerce/CustomerCare/Plugin/Order/AfterPlace.php`

```php
public function afterPlace(Order $subject, OrderInterface $result): OrderInterface
{
    if (!$result || !$result->getCustomerId()) {
        return $result;
    }

    try {
        $this->customerCare->recalculateVipStatus((int) $result->getCustomerId());
    } catch (\Exception $e) {
        $this->logger->error(
            sprintf('CustomerCare: failed to update VIP for customer %s: %s',
                $result->getCustomerId(), $e->getMessage())
        );
    }

    return $result;
}
```

### Impact on Order Lifecycle

- **Before**: Order placed → customer attributes unchanged
- **After**: Order placed → customer VIP status and lifetime spend recalculated

### Interaction with Other Modules

- **LoyaltyProgram**: Both interact with customer data. `CustomerCare` calculates
  lifetime spend including orders with loyalty deductions. `LoyaltyProgram`
  tracks points per order.

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Plugin on `OrderRepositoryInterface::afterSave()` | Catches all order saves, including programmatic |
| VIP recalculation in separate plugin | Decouples from points deduction logic |
| Try-catch with logging | VIP calculation failure should not block order placement |

---

## 7. LoyaltyProgram — Points Deduction on Order Save

### Module: `AlpineCommerce_LoyaltyProgram`

### Responsibility

Deduct **loyalty points** used during checkout from the customer's balance
after the order is saved.

### Events Hooked

| Event | Plugin | File |
|-------|--------|------|
| `OrderRepositoryInterface::afterSave()` | `AfterSave` | `Plugin/Order/AfterSave.php` |

> **Note**: Also has a plugin on `Invoice::afterSave()` (`Plugin/Invoice/AfterSave.php`)
> that likely adds points when an invoice is created.

### Flow

```
Order placed and saved
    │
    ▼
Plugin::afterSave() executed
    │
    ▼
Plugin checks conditions
    │   ├── Order has quote_id?
    │   ├── Order has customer_id?
    │   └── Points ledger type != 'deduct' (skip if already processed)
    │
    ▼
Load quote
    │   └── Get points_used from quote
    │
    ▼
[If pointsUsed > 0]
    │   │
    │   ▼
    │   Load customer balance
    │   │
    │   ▼
    │   Deduct points
    │   │   balance->setPoints(max(0, balance->getPoints() - pointsUsed))
    │   │
    │   ▼
    │   Save balance
    │   │
    │   ▼
    │   Create ledger entry
    │   │   type = 'deduct'
    │   │   points = -pointsUsed
    │   │   order_id = order->getId()
    │
    ▼
Order returned
```

### Code Reference

**File**: `src/app/code/AlpineCommerce/LoyaltyProgram/Plugin/Order/AfterSave.php`

```php
public function afterSave(OrderRepositoryInterface $subject, OrderInterface $result): OrderInterface
{
    if (!$result || !$result->getQuoteId() || !$result->getCustomerId()) {
        return $result;
    }

    $ledger = $this->loyaltyOrderPointsFactory->create();
    $this->loyaltyOrderPointsResource->load($ledger, $result->getId(), 'order_id');

    if ($ledger->getType() === self::LEDGER_TYPE_DEDUCT) {
        return $result; // Already processed
    }

    $quote = $this->quoteRepository->get((int) $result->getQuoteId());
    $pointsUsed = (int) $quote->getData(LoyaltyDiscount::QUOTE_FIELD_POINTS_USED);
    if ($pointsUsed <= 0) {
        return $result;
    }

    $balance = $this->balanceRepository->getByCustomerId((int) $result->getCustomerId());
    $balance->setPoints(max(0, $balance->getPoints() - $pointsUsed));
    $this->balanceRepository->save($balance);

    $ledger->setData('order_id', (int) $result->getId());
    $ledger->setData('type', self::LEDGER_TYPE_DEDUCT);
    $ledger->setData('points', -$pointsUsed);
    $this->loyaltyOrderPointsResource->save($ledger);
}
```

### Impact on Order Lifecycle

- **Before**: Order placed → loyalty points unchanged
- **After**: Order placed → loyalty points deducted from customer balance

### Interaction with Other Modules

- **CustomerCare**: CustomerCare uses `Order::afterPlace()` while LoyaltyProgram uses
  `OrderRepositoryInterface::afterSave()`. They execute independently without
  ordering guarantees. If `CustomerCare` runs first, it recalculates VIP based
  on lifetime spend (which now includes this order).

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Plugin on `OrderRepositoryInterface::afterSave()` | Reliable hook after order persistence |
| Ledger-based tracking | Allows audit trail of points per order |
| `max(0, balance)` | Prevents negative balances |
| Quote lookup for points used | Points are selected during checkout, stored on quote |

---

## 8. Rma — Return Window on Order Placement

### Module: `AlpineCommerce_Rma`

### Responsibility

Set a **return window** on each order at placement time, defining until when
the customer can request a return (RMA).

### Events Hooked

| Event | Observer | File |
|-------|----------|------|
| `sales_order_place_after` | `OrderPlaceAfter` | `Observer/OrderPlaceAfter.php` |

### Flow

```
sales_order_place_after fired
    │
    ▼
Observer checks conditions
    │   └── Is module enabled? (rma/general/enabled)
    │
    ▼
Calculate return allowed until
    │   └── now + allow_return_days (default 30)
    │
    ▼
Set order data
    │   ├── rma_allowed_until = calculated date
    │   └── rma_enabled = 1
    │
    ▼
Add status history comment
    │   └── "RMA return allowed until YYYY-MM-DD HH:MM:SS"
    │
    ▼
Order saved
```

### Code Reference

**File**: `src/app/code/AlpineCommerce/Rma/Observer/OrderPlaceAfter.php`

```php
public function execute(Observer $observer): void
{
    $order = $observer->getEvent()->getOrder();

    if (!$order instanceof OrderInterface) {
        return;
    }

    $storeId = (int) $order->getStoreId();

    if (!$this->isModuleEnabled($storeId)) {
        return;
    }

    $returnAllowedUntil = $this->timezone->date()
        ->add(new \DateInterval('P' . (int) $this->getAllowReturnDays($storeId) . 'D'))
        ->format('Y-m-d H:i:s');

    $order->setData('rma_allowed_until', $returnAllowedUntil);
    $order->setData('rma_enabled', 1);

    $order->addStatusHistoryComment(
        __('RMA return allowed until %1.', $returnAllowedUntil)
    )->setIsCustomerNotified(false);

    $order->save();
}
```

### Impact on Order Lifecycle

- **Before**: Order placed → no return window set
- **After**: Order placed → `rma_allowed_until` and `rma_enabled` set on order

### Configuration

| Path | Scope | Purpose |
|------|-------|---------|
| `rma/general/enabled` | Website/Store | Enable/disable RMA window |
| `rma/general/allow_return_days` | Website/Store | Number of days for return window (default: 30) |

### Interaction with Other Modules

- No direct interactions. RMA data is stored on the order entity and used by
  the RMA module's frontend and admin controllers.

### Architecture Decisions

| Decision | Justification |
|----------|---------------|
| Observer on `sales_order_place_after` | Order is placed, return window can be calculated |
| Custom order attributes (`rma_allowed_until`, `rma_enabled`) | Extends order without schema changes if using EAV-like approach |
| Timezone-aware date calculation | Correct date handling across store views |

---

## 9. StorePickup — Store Pickup Shipping Method

### Module: `AlpineCommerce_StorePickup`

### Responsibility

Add a **store pickup** shipping method to Magento, allowing customers to
collect orders from physical store locations instead of home delivery.

### Impact on Order Lifecycle

StorePickup modifies the **shipping method selection** phase of checkout,
which occurs before order placement.

### Shipping Method Filtering

**File**: `src/app/code/AlpineCommerce/StorePickup/Plugin/Shipping/FilterFlatRate.php`

When store pickup is selected, `StorePickup` **filters out** `flatrate` and
`freeshipping` methods from available options:

```php
class FilterFlatRate
{
    public function aroundCollectRates(\Magento\OfflineShipping\Model\Carrier\Flatrate $subject, \Closure $proceed)
    {
        // If store pickup is selected in quote, return false (hide flatrate)
    }
}
```

Same logic applies to `freeshipping` via `FilterFreeShipping` plugin.

### Configuration

**File**: `src/app/code/AlpineCommerce/StorePickup/etc/config.xml`

```xml
<carriers>
    <storepickup>
        <active>1</active>
        <sallowspecific>0</sallowspecific>
        <model>AlpineCommerce\StorePickup\Model\Carrier\StorePickup</model>
        <name>Store Pickup</name>
        <title>Store Pickup</title>
        <sort_order>1</sort_order>
        <price>0.00</price>
    </storepickup>
</carriers>
```

### Impact on Order Lifecycle

- **Before**: Checkout → shipping methods: flatrate, freeshipping, table rates
- **After**: Checkout → shipping methods: flatrate, freeshipping, table rates, storepickup
  (with flatrate/freeshipping filtered when storepickup is selected)

### Order Data Impact

When store pickup is selected:
- `order.shipping_method` = `storepickup_storepickup`
- `order.shipping_description` = Store Pickup
- `order.shipping_amount` = 0.00 (or configured price)

### Interaction with Other Modules

- No direct interactions with other AlpineCommerce modules.
- Works with standard Magento shipping and totals calculation.

---

## 10. Modules That Do NOT Modify Order Lifecycle

The following AlpineCommerce modules exist but **do not** modify order
lifecycle behavior:

| Module | Purpose | Why Not Included |
|--------|---------|------------------|
| `AlpineCommerce_Blog` | Blog posts and categories | Content management only |
| `AlpineCommerce_Faq` | FAQ management | Content management only |
| `AlpineCommerce_ProductLabels` | Product label management | Catalog only |
| `AlpineCommerce_ProductQuestions` | Product Q&A | Catalog only |
| `AlpineCommerce_ProductReviews` | Product reviews | Catalog only |
| `AlpineCommerce_CustomerGrid` | Customer admin grid | Admin UX only |
| `AlpineCommerce_EuVat` | EU VAT validation | Checkout validation only |
| `AlpineCommerce_Gdpr` | GDPR compliance | Customer data only |
| `AlpineCommerce_HealthCheck` | System health checks | Admin monitoring only |
| `AlpineCommerce_Hreflang` | SEO hreflang tags | SEO only |
| `AlpineCommerce_LegalPages` | Legal pages CMS | Content only |
| `AlpineCommerce_StoreLocator` | Store locator | Content only |
| `AlpineCommerce_StoreSetup` | Store initialization | Setup utility only |
| `AlpineCommerce_Test` | Performance and E2E tests | Testing only |

---

## 11. Events Hooked by AlpineCommerce

### Complete Event Map

| Event | Module | Type | File |
|-------|--------|------|------|
| `sales_order_place_after` | AutoInvoice | Observer | `Observer/AutoInvoice.php` |
| `sales_order_place_after` | CreditMemo | Observer | `Observer/OrderPlaceAfter.php` |
| `sales_order_place_after` | Rma | Observer | `Observer/OrderPlaceAfter.php` |
| `checkout_onepage_controller_success_action` | PartialInvoice | Observer | `Observer/AutoPartialInvoice.php` |
| `Order::afterCancel()` | CreditMemo | Plugin | `Plugin/OrderCancelPlugin.php` |
| `Magento\Sales\Model\Order::afterPlace()` | CustomerCare | Plugin | `Plugin/Order/AfterPlace.php` |
| `OrderRepositoryInterface::afterSave()` | LoyaltyProgram | Plugin | `Plugin/Order/AfterSave.php` |

### Event Timing

```
Order::place()
    │
    ▼
sales_order_place_after event
    │
    ├──► AutoInvoice observer
    └──► Rma observer
    │
    ▼
Order::afterPlace() plugins
    │
    └──► CustomerCare plugin
    │
    ▼
Order saved via repository
    │
    ▼
OrderRepositoryInterface::afterSave() plugins
    │
    └──► LoyaltyProgram plugin
    │
    ▼
checkout_onepage_controller_success_action
    │
    └──► PartialInvoice observer
    │
    ▼
Order::cancel() [if canceled]
    │
    └──► CreditMemo plugin
```

### Execution Order

Events execute in Magento's standard event dispatch order. Plugins execute in
their configured `sortOrder` (default varies by module). There is **no
guaranteed ordering** between AlpineCommerce modules — each should be
idempotent and safe to run in any order.

---

## 12. Common Problems and Debugging

### AutoInvoice Not Creating Invoice

1. Check `autoinvoice/general/enabled` is true
2. Check payment method is in `autoinvoice/general/payment_methods` list
3. Check `order->canInvoice()` returns true
4. Check `order->getTotalInvoiced()` is 0
5. Verify `sales_order_place_after` event is dispatched

### PartialInvoice Creating Empty Invoice

1. Check `partialinvoice/general/min_qty_to_invoice` threshold
2. Check backorder settings vs. actual stock
3. Verify `order->canInvoice()` after partial invoice calculation
4. Check `Invoice::getTotalQty()` > 0 before saving

### CreditMemo Not Created on Cancel

1. Check `autocreditmemo/general/enabled` is true
2. Check payment method is in allowed list
3. Check `order->canCreditmemo()` returns true
4. Verify plugin is registered in `di.xml` (CreditMemo uses plugin, not observer)

### VIP Not Recalculated

1. Check order has `customer_id` (guest orders skipped)
2. Check `customercare/general/vip_enabled` is true
3. Verify `CustomerCare::recalculateVipStatus()` completes without error
4. Check `lifetime_spent` calculation includes this order

### Loyalty Points Not Deducted

1. Check order has `quote_id`
2. Check `QUOTE_FIELD_POINTS_USED` is > 0 on the quote
3. Verify ledger entry doesn't already exist (type = 'deduct')
4. Check customer balance is loaded correctly

### RMA Window Not Set

1. Check `rma/general/enabled` is true
2. Verify `rma_allowed_until` and `rma_enabled` columns exist on `sales_order`
3. Check timezone configuration for correct date calculation

### StorePickup Not Showing

1. Check `storepickup` carrier is active in `config.xml`
2. Verify plugins on `Flatrate` and `Freeshipping` are registered in `di.xml`
3. Check checkout configuration allows store pickup selection

---

*Sources: `src/app/code/AlpineCommerce/AutoInvoice/`, `src/app/code/AlpineCommerce/PartialInvoice/`, `src/app/code/AlpineCommerce/CreditMemo/`, `src/app/code/AlpineCommerce/CustomerCare/`, `src/app/code/AlpineCommerce/LoyaltyProgram/`, `src/app/code/AlpineCommerce/Rma/`, `src/app/code/AlpineCommerce/StorePickup/`.*
