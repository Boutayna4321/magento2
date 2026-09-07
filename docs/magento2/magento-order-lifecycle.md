# Magento 2 — Order Lifecycle

> **Target audience**: developers who want to understand **exactly what happens**
> inside Magento 2 when an order is created, invoiced, shipped, and refunded.
> This document covers **Magento 2.4.8 Core only**. It does not cover
> project-specific modules, customizations, or third-party integrations.

---

## Table of Contents

1. [What Is an Order in Magento 2?](#1-what-is-an-order-in-magento-2)
2. [Order Lifecycle — Big Picture](#2-order-lifecycle--big-picture)
3. [Cart and Quote](#3-cart-and-quote)
4. [Checkout](#4-checkout)
5. [Quote → Order Conversion](#5-quote--order-conversion)
6. [The Order Entity](#6-the-order-entity)
7. [Database Model](#7-database-model)
8. [Order States and Statuses](#8-order-states-and-statuses)
9. [Inventory and MSI](#9-inventory-and-msi)
10. [Payments](#10-payments)
11. [Invoices](#11-invoices)
12. [Shipments](#12-shipments)
13. [Credit Memos and Refunds](#13-credit-memos-and-refunds)
14. [Events, Observers and Plugins](#14-events-observers-and-plugins)
15. [REST API and GraphQL](#15-rest-api-and-graphql)
16. [Admin Order Management](#16-admin-order-management)
17. [Complete End-to-End Technical Trace](#17-complete-end-to-end-technical-trace)
18. [Common Problems and Debugging](#18-common-problems-and-debugging)

---

## 1. What Is an Order in Magento 2?

### Business Perspective

An **order** in Magento 2 represents a **completed purchase agreement** between
a customer and the store. It is the immutable record of what the customer bought,
at what price, how they paid, where it was shipped, and what the current
fulfillment status is.

Magento deliberately separates the **shopping phase** (cart, quote) from the
**commitment phase** (order). This separation allows customers to change their
mind, abandon carts, or modify their selections without creating permanent
records. Only when the customer explicitly commits does the quote become an
order.

### Technical Perspective

Technically, an order is a **persistent aggregate root** in the Sales domain.
It is:

- **Immutable in its essentials** — once placed, core data (items, totals,
  addresses, payment) should not change. Magento enforces this by not providing
  setters for most order fields after placement.
- **Composed of related entities** — an order owns addresses, items, payment
  records, status history, invoices, shipments, and credit memos.
- **Created via conversion** — a quote is converted into an order through a
  well-defined pipeline of converters and validators.

### Difference Between Core Entities

```
Product
  │  A catalog entry. Not part of the order lifecycle directly.
  │  Referenced by quote items and order items.
  ↓
Cart
  │  The customer-facing shopping basket. Technically, a Cart IS a Quote.
  │  Mutable. Can be abandoned, merged, or converted.
  ↓
Quote
  │  The internal data model for the cart. Lives in `quote` table.
  │  Holds items, addresses, shipping, payment, totals.
  │  Mutable until converted.
  ↓
Order
  │  The immutable purchase record. Lives in `sales_order`.
  │  Created from a quote. Never deleted (only canceled/held).
  │  Has its own items, addresses, payment, status history.
  ↓
Invoice
  │  A request for payment / proof of capture. Lives in `sales_invoice`.
  │  Created from an order. Can be full or partial.
  ↓
Shipment
  │  Confirmation that items were physically shipped. Lives in `sales_shipment`.
  │  Created from an order (or from an invoice).
  ↓
Credit Memo
  │  A refund document. Lives in `sales_creditmemo`.
  │  Created from an order or an invoice.
```

### Why Magento Uses a Quote Before Creating an Order

The quote serves several critical purposes:

1. **Mutable staging area** — Customers can add/remove items, change addresses,
   change shipping methods, and change payment methods without affecting any
   committed order.

2. **Total calculation sandbox** — Totals (tax, discounts, shipping) are
   expensive to calculate. The quote caches totals and only recalculates when
   data changes.

3. **Validation checkpoint** — Before converting to an order, Magento validates
   the quote (items exist, addresses are valid, payment method is available,
   minimum order amount is met).

4. **Concurrency control** — The `CartMutex` prevents two concurrent requests
   from modifying the same cart simultaneously.

5. **Guest cart masking** — For guest checkouts, the real quote ID is masked
   via `QuoteIdMask` to avoid exposing internal auto-increment IDs through the
   API.

6. **Customer merge** — When a guest is assigned to a customer account, their
   quote can be merged with the customer's existing active quote.

### Main Entities and Their Roles

| Entity | Table | Purpose | Persistent? | Created When |
|--------|-------|---------|-------------|--------------|
| **Quote** | `quote` | Shopping cart / mutable order draft | Yes | First product added or explicit cart creation |
| **Quote Item** | `quote_item` | Single line item in cart | Yes | Product added to cart |
| **Quote Address** | `quote_address` | Billing/shipping address on cart | Yes | Address set during checkout |
| **Quote Payment** | `quote_payment` | Selected payment method on cart | Yes | Payment method selected |
| **Order** | `sales_order` | Immutable purchase record | Yes | `OrderManagement::place()` succeeds |
| **Order Item** | `sales_order_item` | Line item in finalized order | Yes | Quote converted to order |
| **Order Address** | `sales_order_address` | Snapshot of billing/shipping address | Yes | Quote converted to order |
| **Order Payment** | `sales_order_payment` | Payment details for the order | Yes | Quote converted to order |
| **Order Status History** | `sales_order_status_history` | Status change log / comments | Yes | Order state/status changes |
| **Invoice** | `sales_invoice` | Payment capture document | Yes | Admin/system creates from order |
| **Invoice Item** | `sales_invoice_item` | Line items in invoice | Yes | Invoice created |
| **Shipment** | `sales_shipment` | Shipping confirmation | Yes | Admin creates from order |
| **Shipment Item** | `sales_shipment_item` | Line items in shipment | Yes | Shipment created |
| **Shipment Track** | `sales_shipment_track` | Tracking number | Yes | Tracking added to shipment |
| **Credit Memo** | `sales_creditmemo` | Refund document | Yes | Admin creates from order/invoice |
| **Credit Memo Item** | `sales_creditmemo_item` | Line items in credit memo | Yes | Credit memo created |

---

## 2. Order Lifecycle — Big Picture

### Primary Flow

```
Customer
   ↓
Product (browse/catalog)
   ↓
Cart (add to cart)
   ↓
Quote (mutable draft with items, addresses, shipping, payment)
   ↓
Checkout (address → shipping → payment → review)
   ↓
Place Order
   ↓
Sales Order (immutable record)
   ↓
Payment (authorize / capture)
   ↓
Invoice (capture confirmation)
   ↓
Shipment (physical delivery)
   ↓
Delivery
   ↓
Complete
```

### Alternative Paths

```
Order
 ├── Cancel
 │    └── Void payment (if applicable)
 │    └── Return to stock (via Credit Memo)
 │
 ├── Hold
 │    └── Unhold → resumes previous state
 │
 ├── Invoice
 │    ├── Full invoice
 │    └── Partial invoice
 │
 ├── Shipment
 │    ├── Full shipment
 │    └── Partial shipment
 │    └── Tracking numbers
 │
 └── Credit Memo / Refund
      ├── Full refund
      ├── Partial refund
      ├── Online refund (gateway)
      └── Offline refund (manual)
```

### Stage Descriptions

| Stage | Business Meaning | Magento State | Technical Notes |
|-------|-----------------|---------------|-----------------|
| **Cart** | Customer is shopping | N/A (quote) | `quote.is_active = 1` |
| **Checkout** | Customer enters address/payment | N/A (quote) | Totals collected, validation runs |
| **Place Order** | Commitment to purchase | `new` → `processing` | `Order::place()` called, payment processed |
| **Invoice** | Payment captured / billed | Order unchanged | `sales_invoice` created, `total_invoiced` updated |
| **Shipment** | Items dispatched | Order unchanged | `sales_shipment` created, `total_shipped` updated |
| **Complete** | All items shipped | `complete` | `total_qty_ordered == total_qty_shipped` |
| **Cancel** | Order terminated | `canceled` | Payment voided if possible, quantities returned |
| **Closed** | Fully refunded | `closed` | `total_refunded == grand_total` |

---

## 3. Cart and Quote

### Cart vs Quote

In Magento 2, **Cart** and **Quote** are the same entity at the data level.
The term "Cart" is used in service interfaces and API paths for clarity, while
"Quote" is the internal model name.

- **Cart** = the customer-facing shopping basket
- **Quote** = the internal data model (`Magento\Quote\Model\Quote`)
- **CartInterface** = the API contract (`Magento\Quote\Api\Data\CartInterface`)
- **Quote** = the concrete implementation (`Magento\Quote\Model\Quote implements CartInterface`)

### Quote Structure

```
Quote (cart)
  ├── Quote Items
  │    ├── Product reference
  │    ├── Qty, price, options
  │    └── Parent/child (bundle/configurable)
  │
  ├── Billing Address
  │    ├── Customer address data
  │    └── Same-as-billing flag
  │
  ├── Shipping Address
  │    ├── Customer address data
  │    ├── Shipping method
  │    └── Shipping rates
  │
  ├── Payment
  │    ├── Payment method code
  │    ├── Additional data
  │    └── CC info (if applicable)
  │
  └── Totals
       ├── Subtotal
       ├── Tax
       ├── Shipping
       ├── Discount
       └── Grand Total
```

### Quote Interfaces and Models

**`Magento\Quote\Api\CartRepositoryInterface`**
- Primary repository for loading and persisting quotes
- Key methods: `get($cartId)`, `getActive($cartId)`, `save(CartInterface)`, `delete(CartInterface)`
- Implementation: `Magento\Quote\Model\QuoteRepository`
- Maintains an in-memory cache of quotes by ID and customer ID

**`Magento\Quote\Api\CartManagementInterface`**
- High-level cart lifecycle management
- Key methods:
  - `createEmptyCart(): int` — Creates empty guest cart
  - `createEmptyCartForCustomer($customerId): int` — Creates/reuses cart for logged-in customer
  - `assignCustomer($cartId, $customerId, $storeId): bool` — Merges anonymous cart with customer account
  - `placeOrder($cartId, ?PaymentInterface $paymentMethod): int` — Converts cart to order
- Implementation: `Magento\Quote\Model\QuoteManagement`

**`Magento\Quote\Model\Quote`**
- The core quote model
- Key responsibilities:
  - Item management: `addProduct()`, `addItem()`, `updateItem()`, `removeItem()`
  - Address management: `getBillingAddress()`, `getShippingAddress()`, `setBillingAddress()`, `setShippingAddress()`
  - Payment: `getPayment()`, `setPayment()`
  - Totals: `collectTotals()`, `getTotals()`
  - Validation: `validateMinimumAmount()`

### Quote Creation

**Guest cart:**

POST /V1/guest-carts
  → GuestCartManagement::createEmptyCart()
    → QuoteManagement::createEmptyCart()
      → Creates Quote model
      → Sets store ID
      → Creates empty billing + shipping addresses
      → Sets customer_is_guest = 1
      → Enables shipping rate collection
      → Saves via CartRepositoryInterface::save()
      → Creates QuoteIdMask (maps masked_id → real quote_id)

**Logged-in customer cart:**
```
POST /V1/carts/mine
  → CartManagement::createEmptyCartForCustomer($customerId)
    → Tries to load existing active quote for customer
    → If none: creates new Quote, sets customer data
    → Calls _prepareCustomerQuote() (address defaults, address book)
    → Saves
```

### Adding Items to Cart

**REST/GraphQL path:**
```
POST /V1/guest-carts/{cartId}/items
  → GuestCartItemRepository::save()
    → Cart\AddProductsToCart::execute()
      → Loads cart
      → Preloads products by SKU
      → For each item: $cart->addProduct($product, $buyRequest)
        → Validates product is salable
        → Prepares cart candidates via product type instance
        → Checks if item already exists
        → Initializes via Item\Processor::init()
        → Dispatches sales_quote_product_add_after
      → Saves cart
```

### Setting Addresses

**Shipping address:**
```
POST /V1/guest-carts/{cartId}/shipping-information
  → GuestShippingInformationManagement::saveAddressInformation()
    → Loads active quote
    → Validates quote has items
    → Sets shipping address
    → If billing address provided: sets it too
    → Validates addresses
    → Prepares shipping assignment
    → Saves quote
```

**Payment method:**
```
POST /V1/guest-carts/{cartId}/set-payment-information
  → GuestPaymentMethodManagement::set()
    → Loads quote
    → Validates method availability (country, currency, min/max)
    → Imports payment data onto quote->getPayment()
    → Sets payment method code on shipping address
    → Saves quote
```

### Totals Calculation

```
Quote::collectTotals()
  → TotalsCollector::collect($quote)
    → Creates grand Total object
    → Dispatches sales_quote_collect_totals_before
    → For each address:
      → Creates ShippingAssignment
      → For each total collector (sorted by priority):
        → Subtotal
        → Shipping
        → Tax
        → Discount
        → Grand Total
        → Each collector's collect() adds amounts to Total
      → Dispatches sales_quote_address_collect_totals_after
    → Dispatches sales_quote_collect_totals_after
```

### Quote Lifecycle Summary

| Phase | Action | Quote State |
|-------|--------|-------------|
| **Created** | `createEmptyCart()` | `is_active = 1`, empty |
| **Populated** | Items added, addresses set | `is_active = 1`, items > 0 |
| **Totals collected** | `collectTotals()` | `is_active = 1`, totals cached |
| **Converted** | `placeOrder()` → `submitQuote()` | `is_active = 0`, `converted_at` set |
| **Deactivated** | Order placed successfully | `is_active = 0`, not in active list |
| **Restored** | `restoreQuote()` (reorder) | `is_active = 1` again |

---

## 4. Checkout

### What Magento 2 Checkout Means

In Magento 2, **Checkout** is not a single class. It is a **process** that
transforms a quote into an order. The term encompasses:

- The customer-facing checkout pages (onepage checkout)
- The REST/GraphQL API endpoints for checkout
- The underlying service contracts that orchestrate address, shipping,
  payment, and order placement
- The `Magento\Checkout\Model\Session` that tracks the current checkout state

### Checkout vs Cart vs Quote vs Order

| Concept | Nature | Lifetime | API Representation |
|---------|--------|----------|-------------------|
| **Cart** | Abstract shopping container | Created on first add-to-cart, destroyed on order placement or abandonment | `/V1/guest-carts`, `/V1/carts/mine` |
| **Quote** | Data model for cart | Same as cart | Same endpoints (Quote IS the cart) |
| **Checkout** | The PROCESS of converting cart to order | From cart creation to order placement | Multiple endpoints |
| **Order** | Immutable purchase record | Created at order placement, never destroyed | `/V1/orders` |

### Checkout Technical Flow

```
Checkout UI / API
       ↓
Shipping/Billing Address
       ↓
Shipping Method Selection
       ↓
Payment Method Selection
       ↓
Totals Recalculation
       ↓
Place Order
       ↓
Quote → Order Conversion
       ↓
Order Persistence
```

### Checkout Session

**`Magento\Checkout\Model\Session`** (`module-checkout/Model/Session.php`)
- Extends `\Magento\Framework\Session\SessionManager`
- Tracks the current quote, last order, and checkout step progress
- Key properties:
  - `_quote` — the current Quote object
  - `_order` — the last placed order
  - `lastQuoteId`, `lastSuccessQuoteId`, `lastOrderId`, `lastRealOrderId`
  - Step data via `setStepData()` / `getStepData()`
- Key methods:
  - `getQuote()` — lazy-loads quote from repository, handles masked IDs for guests
  - `clearQuote()` — dispatches `checkout_quote_destroy`, nulls quote
  - `restoreQuote()` — reactivates last quote for reorder
  - `loadCustomerQuote()` — merges customer's existing active quote

### Onepage Checkout (Frontend)

**`Magento\Checkout\Controller\Onepage`** (`module-checkout/Controller/Onepage.php`)
- Abstract base controller for all onepage checkout actions
- Delegates business logic to `Magento\Checkout\Model\Type\Onepage`
- Handles step rendering: `_getShippingMethodsHtml()`, `_getPaymentMethodsHtml()`, `_getReviewHtml()`

**`Magento\Checkout\Model\Type\Onepage`** (`module-checkout/Model/Type/Onepage.php`)
- The **real checkout engine** (not a controller, but a model)
- Key methods:
  - `saveShipping($data, $customerAddressId)` — saves shipping address, enables shipping_method step
  - `saveShippingMethod($shippingMethod)` — sets shipping method, enables payment step
  - `savePayment($data)` — imports payment data, enables review step
  - `saveOrder()` — validates, prepares quote, calls `quoteManagement->submit()`, sends email, dispatches events
- Events dispatched:
  - `checkout_type_onepage_save_order_after` — after order placed
  - `checkout_submit_all_after` — after checkout complete

### Quote Masking for Guests

Guest carts use **QuoteIdMask** to avoid exposing internal auto-increment IDs:

- `Magento\Quote\Model\QuoteIdMask` maps `masked_id` (UUID-like string) ↔ `quote_id` (internal integer)
- Created when guest creates a cart via `GuestCartManagement::createEmptyCart()`
- All guest API calls use the `masked_id` in the URL: `/V1/guest-carts/{maskedId}/...`
- Resolved internally: `$quoteIdMask->load($cartId, 'masked_id')` → `$quoteIdMask->getQuoteId()`
- Deleted when guest is assigned to a customer account

---

## 5. Quote → Order Conversion

### The Conversion Pipeline

The quote-to-order conversion is orchestrated by `Magento\Quote\Model\QuoteManagement` and involves multiple validators, converters, and event dispatchers.

### Entry Point

```
CartManagementInterface::placeOrder($cartId, ?PaymentInterface $paymentMethod)
  → QuoteManagement::placeOrder($cartId, $paymentMethod)
    → [CartMutex] — prevents concurrent modifications
    → placeOrderRun($cartId, $paymentMethod)
```

### `placeOrderRun()` Flow

```php
protected function placeOrderRun($cartId, $paymentMethod)
{
    // 1. Load active quote
    $quote = $this->quoteRepository->getActive($cartId);

    // 2. Set payment method if provided
    if ($paymentMethod) {
        $this->quotePaymentToOrderPayment->convert(...);
        $quote->collectTotals();
    }

    // 3. Set customer fields for guest
    if ($quote->getCustomerIsGuest()) {
        $quote->setCustomerEmail(...);
        $quote->setCustomerFirstname(...);
        $quote->setCustomerLastname(...);
        $quote->setCustomerIsGuest(true);
        $quote->setCustomerGroupId(NOT_LOGGED_IN_ID);
    }

    // 4. Record remote IP
    $quote->setRemoteIp(...);
    $quote->setXForwardedFor(...);

    // 5. Dispatch before event
    $this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

    // 6. Submit quote → convert to order
    $order = $this->submit($quote);

    // 7. Store checkout session data
    $checkoutSession->setLastQuoteId(...);
    $checkoutSession->setLastOrderId($order->getEntityId());
    $checkoutSession->setLastOrderIncrementId($order->getIncrementId());
    $checkoutSession->setLastSuccessQuoteId($quote->getId());

    // 8. Dispatch after event
    $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);

    return $order->getEntityId();
}
```

### `submitQuote()` — The Core Conversion

```php
protected function submitQuote(Quote $quote, $orderData = [])
{
    $order = $this->orderFactory->create();

    // 1. Validate quote
    $this->submitQuoteValidator->validateQuote($quote);

    // 2. Prepare customer data
    if (!$quote->getCustomerIsGuest()) {
        $this->_prepareCustomerQuote($quote);
        $this->customerManagement->populateCustomerInfo($quote);
    }
    $this->customerManagement->validateAddresses($quote);

    // 3. Reserve order ID
    $quote->reserveOrderId();

    // 4. Convert addresses
    if ($quote->isVirtual()) {
        $orderData = $this->quoteAddressToOrder->convert($quote->getBillingAddress(), $orderData);
    } else {
        $shippingAddress = $this->quoteAddressToOrderAddress->convert(
            $quote->getShippingAddress(),
            ['address_type' => 'shipping', 'email' => $quote->getCustomerEmail()]
        );
        $shippingAddress->setData('quote_address_id', $quote->getShippingAddress()->getId());
        $order->setShippingAddress($shippingAddress);
        $order->setShippingMethod($quote->getShippingAddress()->getShippingMethod());
    }

    // Billing address always created
    $billingAddress = $this->quoteAddressToOrderAddress->convert(
        $quote->getBillingAddress(),
        ['address_type' => 'billing', 'email' => $quote->getCustomerEmail()]
    );
    $order->setBillingAddress($billingAddress);
    $order->setAddresses([$shippingAddress, $billingAddress]);

    // 5. Convert payment
    $order->setPayment($this->quotePaymentToOrderPayment->convert($quote->getPayment()));

    // 6. Convert items
    $order->setItems($this->resolveItems($quote));

    // 7. Set customer and quote linkage
    $order->setCustomerId($quote->getCustomer()->getId());
    $order->setQuoteId($quote->getId());
    $order->setCustomerEmail($quote->getCustomerEmail());
    $order->setIncrementId($quote->getReservedOrderId());

    // 8. Validate order
    $this->submitQuoteValidator->validateOrder($order);

    // 9. Dispatch before event
    $this->eventManager->dispatch('sales_model_service_quote_submit_before', [
        'order' => $order, 'quote' => $quote
    ]);

    // 10. Place order (persist + payment)
    try {
        $order = $this->orderManagement->place($order);
        $quote->setIsActive(false);
        $this->eventManager->dispatch('sales_model_service_quote_submit_success', [
            'order' => $order, 'quote' => $quote
        ]);
        $this->quoteRepository->save($quote);
    } catch (\Exception $e) {
        $this->rollbackAddresses($quote, $order, $e);
        throw $e;
    }

    return $order;
}
```

### Address Conversion

**Quote Address → Order Address:**
- `Magento\Quote\Model\Quote\Address\ToOrderAddress`
- Uses fieldset `sales_convert_quote_address` → `to_order_address`
- Fields copied: prefix, firstname, middlename, lastname, suffix, company, street, city, region, region_id, postcode, country_id, telephone, fax, email

**Quote Address → Order-Level Fields:**
- `Magento\Quote\Model\Quote\Address\ToOrder`
- Uses fieldset `sales_convert_quote_address` → `to_order`
- Copies totals: subtotal, tax, discount, shipping, grand_total

### Item Conversion

**Quote Item → Order Item:**
- `Magento\Quote\Model\Quote\Item\ToOrderItem`
- Uses fieldset `quote_convert_item` → `to_order_item`
- Preserves parent/child relationships for bundles/configurables
- Serializes product options (custom options, bundle options)
- `quote_item_id` is preserved for traceability

### Payment Conversion

**Quote Payment → Order Payment:**
- `Magento\Quote\Model\Quote\Payment\ToOrderPayment`
- Uses fieldset `sales_convert_quote_payment` → `to_order_payment`
- Copies: method, additional_data, additional_information, po_number, CC data

### Fieldsets

Magento uses **fieldsets** (defined in `module-sales/etc/fieldset.xml`) to declaratively map fields between entities.

| Fieldset | Aspect | Source → Target |
|----------|--------|-----------------|
| `sales_convert_quote` | `to_order` | Quote fields → Order |
| `sales_convert_quote_address` | `to_order` | Address totals → Order |
| `sales_convert_quote_address` | `to_order_address` | Address fields → OrderAddress |
| `sales_convert_quote_payment` | `to_order_payment` | Payment fields → OrderPayment |
| `quote_convert_item` | `to_order_item` | Quote item → OrderItem |
| `quote_convert_address_item` | `to_order_item` | Address item → OrderItem |
| `quote_convert_item` | `to_order_item_discount` | Discount fields → OrderItem |

---

## 6. The Order Entity

### OrderInterface

**`Magento\Sales\Api\Data\OrderInterface`** (`module-sales/Api/Data/OrderInterface.php`)

The primary data contract for an order. Key areas:

**Core Identity:**
- `ENTITY_ID`, `INCREMENT_ID`, `QUOTE_ID`, `STORE_ID`, `CUSTOMER_ID`, `CUSTOMER_EMAIL`

**State/Status:**
- `STATE`, `STATUS`, `HOLD_BEFORE_STATE`, `HOLD_BEFORE_STATUS`

**Totals (base and display currency):**
- `BASE_GRAND_TOTAL`, `GRAND_TOTAL`, `BASE_SUBTOTAL`, `SUBTOTAL`
- `BASE_TAX_AMOUNT`, `TAX_AMOUNT`
- `BASE_SHIPPING_AMOUNT`, `SHIPPING_AMOUNT`
- `BASE_DISCOUNT_AMOUNT`, `DISCOUNT_AMOUNT`
- `BASE_TOTAL_PAID`, `TOTAL_PAID`, `BASE_TOTAL_DUE`, `TOTAL_DUE`
- `BASE_TOTAL_REFUNDED`, `TOTAL_REFUNDED`
- `BASE_TOTAL_INVOICED`, `TOTAL_INVOICED`
- `BASE_TOTAL_SHIPPED`, `TOTAL_SHIPPED`

**Relations:**
- `ITEMS` (OrderItemInterface[])
- `BILLING_ADDRESS` (OrderAddressInterface)
- `SHIPPING_ADDRESS` (OrderAddressInterface)
- `PAYMENT` (OrderPaymentInterface)
- `STATUS_HISTORIES` (OrderStatusHistoryInterface[])

### Order Model

**`Magento\Sales\Model\Order`** (`module-sales/Model/Order.php`)
- Extends `AbstractModel` implements `EntityInterface`, `OrderInterface`
- 4,722 lines — the largest and most central model in the Sales domain

**Order States (constants):**
```php
STATE_NEW            = 'new'
STATE_PENDING_PAYMENT = 'pending_payment'
STATE_PROCESSING     = 'processing'
STATE_COMPLETE       = 'complete'
STATE_CLOSED         = 'closed'
STATE_CANCELED       = 'canceled'
STATE_HOLDED         = 'holded'
STATE_PAYMENT_REVIEW = 'payment_review'
```

**Action Flags:**
```php
ACTION_FLAG_CANCEL    = 'cancel'
ACTION_FLAG_HOLD      = 'hold'
ACTION_FLAG_UNHOLD    = 'unhold'
ACTION_FLAG_EDIT      = 'edit'
ACTION_FLAG_CREDITMEMO = 'creditmemo'
ACTION_FLAG_INVOICE   = 'invoice'
ACTION_FLAG_REORDER   = 'reorder'
ACTION_FLAG_SHIP      = 'ship'
ACTION_FLAG_COMMENT   = 'comment'
```

**Key Methods:**

| Method | Purpose |
|--------|---------|
| `place()` | Dispatches `sales_order_place_before`, calls `_placePayment()`, dispatches `sales_order_place_after` |
| `hold()` | Sets state to `STATE_HOLDED` with default status |
| `unhold()` | Restores state/status from `hold_before_state`/`hold_before_status` |
| `cancel()` | Calls payment->cancel() and registerCancellation() |
| `canCancel()` | Complex logic: checks voidability, unhold, payment review, invoicing, state |
| `canHold()` | Checks not in canceled/payment_review/complete/closed/holded |
| `canShip()` | Checks virtual, canceled, action flag, item shipping availability |
| `canInvoice()` | Checks unhold, payment review, state, action flag, items with qty to invoice |
| `canCreditmemo()` | Checks refundability based on total paid/refunded |
| `addCommentToStatusHistory()` | Creates history entry with status/comment/notification |
| `getTotalDue()` | `max(grand_total - total_paid, 0)` |
| `getConfig()` | Returns order configuration (states, statuses) |

### Order Address

**`Magento\Sales\Model\Order\Address`** (`module-sales/Model/Order/Address.php`)
- Extends `AbstractModel` implements `OrderAddressInterface`, `AddressModelInterface`
- Constants: `TYPE_BILLING = 'billing'`, `TYPE_SHIPPING = 'shipping'`
- Represents a **snapshot** of a customer address at the time of order
- Immutable from a customer-address-update perspective
- Stores: prefix, firstname, middlename, lastname, suffix, company, street, city, region, region_id, postcode, country_id, telephone, fax, email

### Order Item

**`Magento\Sales\Model\Order\Item`** (`module-sales/Model/Order/Item.php`)
- Extends `AbstractModel` implements `OrderItemInterface`
- Tracks quantities: `qty_ordered`, `qty_invoiced`, `qty_shipped`, `qty_refunded`, `qty_canceled`, `qty_backordered`
- Item statuses: PENDING, SHIPPED, BACKORDERED, RETURNED, CANCELED, PARTIAL, MIXED, REFUNDED, INVOICED
- Parent/child relationships for bundle/configurable products
- Key methods: `canInvoice()`, `canShip()`, `canRefund()`, `getQtyToShip()`, `getQtyToInvoice()`, `getQtyToRefund()`

### Order Payment

**`Magento\Sales\Model\Order\Payment`** (`module-sales/Model/Order/Payment.php`)
- Extends `Info` implements `OrderPaymentInterface`
- Key responsibilities:
  - Payment processing: `place()`, `authorize()`, `capture()`, `refund()`, `void()`, `cancel()`
  - Transaction management: `addTransaction()`, `setTransactionId()`
  - Totals tracking: `amount_ordered`, `amount_paid`, `amount_authorized`, `amount_refunded`
- Uses operation classes: `SaleOperation`, `AuthorizeOperation`, `CaptureOperation`, `OrderOperation`
- For `authorize_capture`: calls `saleOperation->execute()` which auto-creates an invoice

### Order Status History

**`Magento\Sales\Model\Order\Status\History`** (`module-sales/Model/Order/Status/History.php`)
- Extends `AbstractModel` implements `OrderStatusHistoryInterface`
- A single status change entry
- Contains: comment, status, is_customer_notified, is_visible_on_front
- Created automatically during state transitions or via `addCommentToStatusHistory()`
- Saved automatically with the order via the entity_id backend model

### Order Repository

**`Magento\Sales\Api\OrderRepositoryInterface`**
- `get($id)` — loads order from DB, enriches with extension attributes
- `getList(SearchCriteriaInterface)` — search orders
- `save(OrderInterface)` — persists order
- `delete(OrderInterface)` — deletes order

Implementation: `Magento\Sales\Model\OrderRepository`
- Maintains an in-memory `$registry` to avoid duplicate loads
- Handles shipping assignment persistence from extension attributes

---

## 7. Database Model

### Canonical Tables vs Grid/Index Tables

Magento has two categories of tables in the order lifecycle:

**Canonical transactional tables:**
- `sales_order`, `sales_order_item`, `sales_order_address`, `sales_order_payment`
- `sales_invoice`, `sales_invoice_item`
- `sales_shipment`, `sales_shipment_item`, `sales_shipment_track`
- `sales_creditmemo`, `sales_creditmemo_item`
- `sales_order_status_history`
- `sales_payment_transaction`

**Grid/index tables:**
- `sales_order_grid` — denormalized order data for admin grid
- `sales_invoice_grid` — denormalized invoice data
- `sales_shipment_grid` — denormalized shipment data
- `sales_creditmemo_grid` — denormalized credit memo data

**Rule:** Grid tables are **derived** from canonical tables. They are synchronized
via observers on relation events. **Never** use grid tables as the source of truth
for order data.

### Sales Tables Relationship Diagram

```
sales_order (PK: entity_id)
   │
   ├── sales_order_item (PK: item_id, FK: order_id → sales_order.entity_id)
   │    └── parent_item_id (self-reference for bundle/grouped children)
   │
   ├── sales_order_address (PK: entity_id, FK: parent_id → sales_order.entity_id)
   │    ├── address_type: 'billing' | 'shipping'
   │    └── quote_address_id (link back to quote)
   │
   ├── sales_order_payment (PK: entity_id, FK: parent_id → sales_order.entity_id)
   │    └── 1:1 with order
   │
   ├── sales_order_status_history (PK: entity_id, FK: parent_id → sales_order.entity_id)
   │
   ├── sales_payment_transaction (PK: transaction_id, FK: order_id → sales_order.entity_id)
   │    ├── parent_id (self-reference for transaction hierarchy)
   │    └── payment_id → sales_order_payment.entity_id
   │
   ├── sales_invoice (PK: entity_id, FK: order_id → sales_order.entity_id)
   │    └── sales_invoice_item (PK: entity_id, FK: parent_id → sales_invoice.entity_id)
   │
   ├── sales_shipment (PK: entity_id, FK: order_id → sales_order.entity_id)
   │    ├── sales_shipment_item (PK: entity_id, FK: parent_id → sales_shipment.entity_id)
   │    └── sales_shipment_track (PK: entity_id, FK: parent_id → sales_shipment.entity_id)
   │
   └── sales_creditmemo (PK: entity_id, FK: order_id → sales_order.entity_id)
        └── sales_creditmemo_item (PK: entity_id, FK: parent_id → sales_creditmemo.entity_id)
```

### Detailed Table Specifications

#### `sales_order`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `increment_id` | VARCHAR(50), UNIQUE + store_id | Human-readable order number |
| `state` | VARCHAR(32) | Internal workflow state |
| `status` | VARCHAR(32) | Visible status label |
| `store_id` | SMALLINT UNSIGNED | FK to `store` |
| `customer_id` | INT UNSIGNED | FK to `customer_entity`, SET NULL on delete |
| `quote_id` | INT | Link back to originating quote |
| `billing_address_id` | INT | FK to `sales_order_address` |
| `shipping_address_id` | INT | FK to `sales_order_address` |
| `base_grand_total` | DECIMAL(20,4) | |
| `grand_total` | DECIMAL(20,4) | |
| `base_total_paid` | DECIMAL(20,4) | Cumulative paid amount |
| `total_paid` | DECIMAL(20,4) | |
| `base_total_invoiced` | DECIMAL(20,4) | |
| `total_invoiced` | DECIMAL(20,4) | |
| `base_total_refunded` | DECIMAL(20,4) | |
| `total_refunded` | DECIMAL(20,4) | |
| `total_qty_ordered` | DECIMAL(12,4) | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |
| `hold_before_state` | VARCHAR(32) | |
| `hold_before_status` | VARCHAR(32) | |

**Indexes:** `increment_id`+`store_id` (unique), `status`, `state`, `store_id`, `created_at`, `customer_id`, `quote_id`, `updated_at`

**Lifecycle:**
- **Created:** When `QuoteManagement::submit()` calls `OrderManagement::place()`
- **Updated:** On status changes, payment captures, invoicing, shipping, refunds
- **Deleted:** Rarely; typically archived/canceled

#### `sales_order_item`

| Column | Type | Notes |
|--------|------|-------|
| `item_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `parent_item_id` | INT UNSIGNED | Self-reference for bundle/grouped children |
| `quote_item_id` | INT UNSIGNED | Link to originating quote item |
| `product_id` | INT UNSIGNED | |
| `sku` | VARCHAR(255) | |
| `name` | VARCHAR(255) | |
| `product_type` | VARCHAR(255) | simple, configurable, bundle, etc. |
| `qty_ordered` | DECIMAL(12,4) | |
| `qty_invoiced` | DECIMAL(12,4) | Updated when invoice created |
| `qty_shipped` | DECIMAL(12,4) | Updated when shipment created |
| `qty_refunded` | DECIMAL(12,4) | Updated when credit memo created |
| `qty_canceled` | DECIMAL(12,4) | Updated when order canceled |
| `price` | DECIMAL(20,4) | |
| `base_price` | DECIMAL(20,4) | |
| `row_total` | DECIMAL(20,4) | |
| `base_row_total` | DECIMAL(20,4) | |
| `product_options` | LONGTEXT | Serialized options (custom, bundle, configurable) |
| `locked_do_invoice` | SMALLINT | |
| `locked_do_ship` | SMALLINT | |

**Lifecycle:**
- **Created:** During `QuoteManagement::resolveItems()`, via `ToOrderItemConverter`
- **Updated:** Quantities updated as invoices, shipments, credit memos, cancellations occur
- **Deleted:** CASCADE when parent order deleted

#### `sales_order_address`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `address_type` | VARCHAR(255) | billing or shipping |
| `quote_address_id` | INT | Link back to originating quote address |
| `customer_address_id` | INT | FK to customer address book |
| `firstname`, `lastname`, `middlename`, `prefix`, `suffix` | VARCHAR(255) | |
| `street` | VARCHAR(255) | |
| `city` | VARCHAR(255) | |
| `region` | VARCHAR(255) | |
| `region_id` | INT | |
| `postcode` | VARCHAR(255) | |
| `country_id` | VARCHAR(2) | |
| `telephone` | VARCHAR(255) | |
| `email` | VARCHAR(255) | |

**Lifecycle:**
- **Created:** During `QuoteManagement::submitQuote()`, via `ToOrderAddressConverter`
- **Updated:** Rarely after creation
- **Deleted:** CASCADE when parent order deleted

#### `sales_order_payment`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `method` | VARCHAR(128) | Payment method code |
| `quote_payment_id` | INT | Link to originating quote payment |
| `amount_ordered` | DECIMAL(20,4) | |
| `base_amount_ordered` | DECIMAL(20,4) | |
| `amount_paid` | DECIMAL(20,4) | |
| `base_amount_paid` | DECIMAL(20,4) | |
| `amount_authorized` | DECIMAL(20,4) | |
| `base_amount_authorized` | DECIMAL(20,4) | |
| `amount_refunded` | DECIMAL(20,4) | |
| `base_amount_refunded` | DECIMAL(20,4) | |
| `additional_data` | TEXT | Serialized additional data |
| `additional_information` | TEXT | Payment-specific info (e.g. CC last 4, txn IDs) |
| `transaction_id` | VARCHAR(255) | Last transaction ID |
| `po_number` | VARCHAR(32) | Purchase order number |

**Lifecycle:**
- **Created:** During `QuoteManagement::submitQuote()`, via `ToOrderPaymentConverter`
- **Updated:** As payments are captured, authorized, refunded
- **Deleted:** CASCADE when parent order deleted

#### `sales_order_status_history`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `status` | VARCHAR(32) | Status at time of change |
| `comment` | TEXT | Status comment |
| `is_customer_notified` | INT | Whether customer was notified |
| `is_visible_on_front` | SMALLINT UNSIGNED | Whether visible on frontend |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Lifecycle:**
- **Created:** Whenever order status changes, or via `addCommentToStatusHistory()`
- **Updated:** Never (append-only)
- **Deleted:** CASCADE when parent order deleted

#### `sales_payment_transaction`

| Column | Type | Notes |
|--------|------|-------|
| `transaction_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `parent_id` | INT UNSIGNED | Self-reference for transaction hierarchy |
| `payment_id` | INT UNSIGNED | FK to `sales_order_payment.entity_id`, CASCADE |
| `txn_id` | VARCHAR(100) | Gateway transaction ID |
| `parent_txn_id` | VARCHAR(100) | Parent transaction ID |
| `txn_type` | VARCHAR(15) | order, authorization, capture, refund, void |
| `is_closed` | SMALLINT UNSIGNED | Whether transaction is closed |
| `additional_information` | BLOB | JSON blob with gateway-specific data |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |

**Unique Key:** `(order_id, payment_id, txn_id)`

**Lifecycle:**
- **Created:** By payment gateway command infrastructure for each payment action
- **Updated:** `is_closed` may change when transaction closes
- **Deleted:** CASCADE when parent order/payment deleted

#### `sales_invoice`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `store_id` | SMALLINT | FK to `store`, SET NULL |
| `increment_id` | VARCHAR(50) | Human-readable invoice number |
| `state` | INT | 1=open, 2=paid, 3=canceled |
| `transaction_id` | VARCHAR(255) | Payment transaction ID |
| `base_grand_total` | DECIMAL(20,4) | |
| `grand_total` | DECIMAL(20,4) | |
| `total_qty` | DECIMAL(12,4) | Total items qty |
| `base_total_refunded` | DECIMAL(20,4) | |
| `email_sent` | SMALLINT | |
| `send_email` | SMALLINT | |
| `can_void_flag` | SMALLINT | |
| `billing_address_id` | INT | FK to `sales_order_address` |
| `shipping_address_id` | INT | FK to `sales_order_address` |
| `base_currency_code` | VARCHAR(3) | |
| `order_currency_code` | VARCHAR(3) | |
| `store_currency_code` | VARCHAR(3) | |
| `global_currency_code` | VARCHAR(3) | |
| `discount_description` | VARCHAR(255) | |
| `customer_note` | TEXT | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**Indexes:** `increment_id`+`store_id` (unique), `order_id`, `store_id`, `grand_total`, `state`, `created_at`, `updated_at`

**Lifecycle:**
- **Created:** When admin/system creates invoice from order
- **Updated:** State, totals during voiding/partial invoicing
- **Deleted:** When voided/deleted from admin

#### `sales_invoice_item`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_invoice.entity_id`, CASCADE |
| `order_item_id` | INT | FK to `sales_order_item.item_id` |
| `product_id` | INT UNSIGNED | |
| `sku` | VARCHAR(255) | |
| `name` | VARCHAR(255) | |
| `qty` | DECIMAL(12,4) | Invoiced quantity |
| `price` | DECIMAL(20,4) | |
| `base_price` | DECIMAL(20,4) | |
| `row_total` | DECIMAL(20,4) | |
| `base_row_total` | DECIMAL(20,4) | |
| `discount_amount` | DECIMAL(20,4) | |
| `base_discount_amount` | DECIMAL(20,4) | |
| `tax_amount` | DECIMAL(20,4) | |
| `base_tax_amount` | DECIMAL(20,4) | |
| `tax_ratio` | TEXT | |
| `price_incl_tax` | DECIMAL(20,4) | |
| `base_price_incl_tax` | DECIMAL(20,4) | |
| `row_total_incl_tax` | DECIMAL(20,4) | |
| `base_row_total_incl_tax` | DECIMAL(20,4) | |
| `discount_tax_compensation_amount` | DECIMAL(20,4) | |
| `base_discount_tax_compensation_amount` | DECIMAL(20,4) | |
| `base_cost` | DECIMAL(20,4) | |
| `additional_data` | TEXT | |
| `description` | TEXT | |

**Lifecycle:**
- **Created:** During invoice creation, one per invoiced order item
- **Updated:** Rarely
- **Deleted:** CASCADE when parent invoice deleted

#### `sales_shipment`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `store_id` | SMALLINT | FK to `store`, SET NULL |
| `increment_id` | VARCHAR(50) | Human-readable shipment number |
| `customer_id` | INT UNSIGNED | |
| `shipping_address_id` | INT | FK to `sales_order_address` |
| `billing_address_id` | INT | FK to `sales_order_address` |
| `shipment_status` | INT | Status code |
| `total_weight` | DECIMAL(12,4) | |
| `total_qty` | DECIMAL(12,4) | |
| `email_sent` | SMALLINT | |
| `send_email` | SMALLINT | |
| `packages` | TEXT | Packed products |
| `shipping_label` | MEDIUMBLOB | Label binary data |
| `customer_note` | TEXT | |
| `customer_note_notify` | SMALLINT | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**Indexes:** `order_id`, `store_id`, `total_qty`, `created_at`, `updated_at`

**Lifecycle:**
- **Created:** When admin creates shipment from order
- **Updated:** `shipment_status`, `total_qty`, `total_weight`
- **Deleted:** Rare; CASCADE when order deleted

#### `sales_shipment_item`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_shipment.entity_id`, CASCADE |
| `order_item_id` | INT | FK to `sales_order_item.item_id` |
| `product_id` | INT UNSIGNED | |
| `sku` | VARCHAR(255) | |
| `name` | VARCHAR(255) | |
| `qty` | DECIMAL(12,4) | Shipped quantity |
| `weight` | DECIMAL(12,4) | |
| `price` | DECIMAL(20,4) | |
| `row_total` | DECIMAL(20,4) | |
| `description` | TEXT | |
| `additional_data` | TEXT | |

**Lifecycle:**
- **Created:** During shipment creation, one per shipped order item
- **Updated:** Rarely
- **Deleted:** CASCADE when parent shipment deleted

#### `sales_shipment_track`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_shipment.entity_id`, CASCADE |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id` |
| `track_number` | TEXT | Tracking number |
| `description` | TEXT | |
| `title` | VARCHAR(255) | |
| `carrier_code` | VARCHAR(32) | |
| `weight` | DECIMAL(12,4) | |
| `qty` | DECIMAL(12,4) | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**Lifecycle:**
- **Created:** When admin adds tracking numbers to shipment
- **Updated:** `track_number` or `description` if updated
- **Deleted:** CASCADE when parent shipment deleted

#### `sales_creditmemo`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `order_id` | INT UNSIGNED | FK to `sales_order.entity_id`, CASCADE |
| `store_id` | SMALLINT | FK to `store`, SET NULL |
| `increment_id` | VARCHAR(50) | Human-readable credit memo number |
| `invoice_id` | INT | FK to `sales_invoice.entity_id` |
| `state` | INT | 1=open, 2=refunded, 3=canceled |
| `creditmemo_status` | INT | Status code |
| `base_grand_total` | DECIMAL(20,4) | |
| `grand_total` | DECIMAL(20,4) | |
| `base_total_refunded` | DECIMAL(20,4) | |
| `transaction_id` | VARCHAR(255) | Refund transaction ID |
| `email_sent` | SMALLINT | |
| `send_email` | SMALLINT | |
| `adjustment` | DECIMAL(20,4) | |
| `base_adjustment` | DECIMAL(20,4) | |
| `adjustment_positive` | DECIMAL(20,4) | |
| `adjustment_negative` | DECIMAL(20,4) | |
| `base_adjustment_positive` | DECIMAL(20,4) | |
| `base_adjustment_negative` | DECIMAL(20,4) | |
| `shipping_amount` | DECIMAL(20,4) | |
| `base_shipping_amount` | DECIMAL(20,4) | |
| `shipping_incl_tax` | DECIMAL(20,4) | |
| `base_shipping_incl_tax` | DECIMAL(20,4) | |
| `discount_amount` | DECIMAL(20,4) | |
| `base_discount_amount` | DECIMAL(20,4) | |
| `discount_description` | VARCHAR(255) | |
| `customer_note` | TEXT | |
| `customer_note_notify` | SMALLINT | |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | TIMESTAMP | ON UPDATE CURRENT_TIMESTAMP |

**Indexes:** `order_id`, `store_id`, `creditmemo_status`, `state`, `created_at`, `updated_at`

**Lifecycle:**
- **Created:** When admin creates credit memo from order/invoice
- **Updated:** `state`, totals during partial refunds
- **Deleted:** When voided/deleted

#### `sales_creditmemo_item`

| Column | Type | Notes |
|--------|------|-------|
| `entity_id` | INT UNSIGNED, PK, AUTO_INCREMENT | |
| `parent_id` | INT UNSIGNED | FK to `sales_creditmemo.entity_id`, CASCADE |
| `order_item_id` | INT | FK to `sales_order_item.item_id` |
| `product_id` | INT UNSIGNED | |
| `sku` | VARCHAR(255) | |
| `name` | VARCHAR(255) | |
| `qty` | DECIMAL(12,4) | Refunded quantity |
| `price` | DECIMAL(20,4) | |
| `base_price` | DECIMAL(20,4) | |
| `row_total` | DECIMAL(20,4) | |
| `base_row_total` | DECIMAL(20,4) | |
| `discount_amount` | DECIMAL(20,4) | |
| `base_discount_amount` | DECIMAL(20,4) | |
| `tax_amount` | DECIMAL(20,4) | |
| `base_tax_amount` | DECIMAL(20,4) | |
| `tax_ratio` | TEXT | |
| `price_incl_tax` | DECIMAL(20,4) | |
| `row_total_incl_tax` | DECIMAL(20,4) | |
| `discount_tax_compensation_amount` | DECIMAL(20,4) | |
| `base_discount_tax_compensation_amount` | DECIMAL(20,4) | |
| `base_cost` | DECIMAL(20,4) | |
| `additional_data` | TEXT | |
| `description` | TEXT | |

**Lifecycle:**
- **Created:** During credit memo creation, one per refunded order item
- **Updated:** Rarely
- **Deleted:** CASCADE when parent credit memo deleted

---

## 8. Order States and Statuses

### State vs Status

**State** is the internal workflow stage of an order. It is controlled
programmatically and defines what actions are possible (canInvoice, canShip,
canCancel, etc.).

**Status** is a visible label within a state. Multiple statuses can map to a
single state. Statuses are configurable per state in the admin.

### Available States

| State | Meaning | Typical Statuses | Possible Transitions |
|-------|---------|-----------------|---------------------|
| `new` | Order placed, not yet processed | `pending` | → `processing`, `payment_review`, `canceled` |
| `pending_payment` | Awaiting payment | `pending_payment` | → `processing`, `canceled` |
| `processing` | Payment received, order being fulfilled | `processing` | → `complete`, `canceled`, `holded` |
| `complete` | All items shipped | `complete` | → `closed` |
| `closed` | Fully refunded/canceled | `closed`, `canceled` | terminal |
| `canceled` | Order canceled | `canceled` | terminal |
| `holded` | Order on hold | `holded` | → previous state via unhold |
| `payment_review` | Under payment review | `payment_review`, `fraud` | → `processing`, `canceled` |

### State Transitions

- `new` + payment placed → `processing`
- `processing` + all items shipped → `complete`
- `complete` + refunded → `closed`
- Any state + hold → `holded`
- `holded` + unhold → previous state restored
- Any state + cancel → `canceled` (if `canCancel()`)

### Custom Statuses

Administrators can create custom statuses and map them to states via:
`Stores → Configuration → Sales → Order Status`

The mapping is stored in:
- `sales_order_status` — status definitions
- `sales_order_status_state` — state-to-status mappings

---

## 9. Inventory and MSI

### Reservation Lifecycle

1. **Order Placement** → negative reservation appended (`inventory_reservation`, qty = -N)
2. **Salable Quantity** = physical quantity + reservations - min_qty
3. **Shipment** → source deduction (`inventory_source_item.quantity` decremented) + compensating reservation (+N)
4. **Net result** → reservation balance = 0, physical stock reduced

### Key Tables

**`inventory_reservation`**: `reservation_id` (PK), `stock_id`, `sku`, `quantity` (negative for order, positive for shipment/refund), `metadata` (JSON)

**`inventory_source_item`**: `source_item_id` (PK), `source_code`, `sku`, `quantity` (physical), `status` (in/out of stock)

**`inventory_stock`**: `stock_id` (PK), `name`

**`inventory_stock_sales_channel`**: `type` + `code` (PK), `stock_id` — links stock to websites

### Key Classes

- `AppendReservationsAfterOrderPlacementPlugin` — creates negative reservations on order placement
- `PlaceReservationsForSalesEvent` — builds and appends reservations
- `SourceDeductionProcessor` — observer on shipment creation, deducts physical stock + compensating reservation
- `GetSalableQty` — calculates salable qty: physical + reservations - min_qty

### Distinction

- **Reservation**: logical hold on salable quantity, does NOT change physical stock
- **Salable Quantity**: dynamically calculated, what customers see
- **Physical Source Deduction**: actual stock reduction, happens during shipment/refund

---

## 10. Payments

### Payment Architecture

**`Magento\Payment\Model\MethodInterface`**
- Actions: `order` (auth+capture), `authorize` (hold), `authorize_capture` (auth then capture)
- Capabilities: `canOrder()`, `canAuthorize()`, `canCapture()`, `canRefund()`, `canVoid()`

**`Magento\Payment\Model\Method\AbstractMethod`**
- Base implementation with capability flags
- `getConfigPaymentAction()` returns configured action

### Payment Flow

```
Payment Method
  ↓
Order\Payment::place()
  ↓
processAction(action)
  ↓
Operation class (Sale/Authorize/Capture/Order)
  ↓
Gateway call (online) or local update (offline)
  ↓
Transaction recorded
  ↓
Order state/status updated
```

### Operations

- **OrderOperation**: single call auth+capture, no Magento invoice
- **AuthorizeOperation**: funds reserved, no invoice created
- **SaleOperation** (authorize_capture): auto-creates invoice, calls gateway sale()
- **CaptureOperation**: captures existing authorization/invoice

### Online vs Offline

- **Online**: gateway HTTP call (Braintree, PayPal, Authorize.Net)
- **Offline**: no gateway call, local totals only (checkmo, banktransfer)

### Transactions

**`sales_payment_transaction`**: `transaction_id` (PK), `order_id`, `payment_id`, `parent_id`, `txn_id`, `txn_type` (order/authorization/capture/refund/void), `is_closed`, `additional_information`

---

## 11. Invoices

### Invoice Creation

**`Magento\Sales\Model\Service\InvoiceService`**
- `prepareInvoice(Order $order, array $qtys = [])` — builds Invoice from order
- Validates items can be invoiced, converts items, collects totals

**`Magento\Sales\Model\Order\Invoice`**
- States: `STATE_OPEN = 1`, `STATE_PAID = 2`, `STATE_CANCELED = 3`
- Capture modes: `CAPTURE_ONLINE`, `CAPTURE_OFFLINE`
- `register()` — updates order totals, calls capture if online
- `pay()` — marks paid, updates `total_paid`

### Full vs Partial

- Full invoice: all items with `qtyToInvoice > 0`
- Partial invoice: specific item quantities passed to `prepareInvoice()`

### Invoice Capture Flow

1. `InvoiceService::prepareInvoice()` — creates invoice, attaches to order
2. `Invoice::register()` — validates, updates totals, calls capture
3. `InvoiceRepository::save()` — persists invoice
4. For `authorize_capture`: invoice auto-created during `Order::place()` via `SaleOperation`

---

## 12. Shipments

### Shipment Creation

**`Magento\Sales\Model\Order\ShipmentFactory`**
- `create(Order $order, array $items = [], $tracks = null)` — builds shipment
- `prepareItems()` — validates shippable items, clamps quantities
- `prepareTracks()` — adds tracking numbers

**`Magento\Sales\Model\Order\Shipment`**
- `register()` — registers items, computes totalQty
- `addTrack(Track $track)` — adds tracking number

### Full vs Partial

- Full shipment: empty `$items` → all shippable qty
- Partial shipment: `[itemId => qty, ...]`

### Tracking

- Added via factory or `$shipment->addTrack()`
- Persisted in `sales_shipment_track`
- Contains: `carrier_code`, `title`, `number`

---

## 13. Credit Memos and Refunds

### Credit Memo Creation

**`Magento\Sales\Model\Service\CreditmemoService`**
- `refund(CreditmemoInterface $creditmemo, $offlineRequested = false)` — final refund
- `validateForRefund()` — checks amounts, order state

**`Magento\Sales\Model\Order\Creditmemo`**
- States: `STATE_OPEN = 1`, `STATE_REFUNDED = 2`, `STATE_CANCELED = 3`
- `canRefund()` — checks payment method capability
- `canCancel()` — true if state == OPEN

### Online vs Offline Refund

- **Online**: `Creditmemo->getDoTransaction() == true` + invoice attached → gateway `refund()` called
- **Offline**: no gateway call, local counters updated

### Refund Flow

1. Build credit memo from order/invoice
2. `CreditmemoService::refund()`
3. `RefundOperation::execute()` — updates order totals, processes items
4. `Order\Payment::refund()` — gateway call if online
5. Return to stock via `ReturnProcessor` (module-sales-inventory)

---

## 14. Events, Observers and Plugins

### Key Events

| Event | Dispatched From | Data | Typical Use |
|-------|----------------|------|-------------|
| `checkout_submit_before` | `QuoteManagement::placeOrderRun()` | `['quote' => $quote]` | Pre-order validation |
| `sales_model_service_quote_submit_before` | `QuoteManagement::submitQuote()` | `['order' => $order, 'quote' => $quote]` | Before order placement |
| `sales_model_service_quote_submit_success` | `QuoteManagement::submitQuote()` | `['order' => $order, 'quote' => $quote]` | After successful order |
| `checkout_submit_all_after` | `QuoteManagement::placeOrderRun()` + `Onepage::saveOrder()` | `['order' => $order, 'quote' => $quote]` | Post-checkout processing |
| `sales_order_place_before` | `Order::place()` | `['order' => $this]` | Before payment processing |
| `sales_order_place_after` | `Order::place()` | `['order' => $this]` | After payment processing |
| `sales_order_payment_place_start` | `Order\Payment::place()` | `['payment' => $this]` | Payment start |
| `sales_order_payment_place_end` | `Order\Payment::place()` | `['payment' => $this]` | Payment end |
| `sales_order_save_after` | `AbstractModel::afterSave()` | `['object' => $order]` | After order saved |
| `sales_order_invoice_save_after` | `AbstractModel::afterSave()` | `['object' => $invoice]` | After invoice saved |
| `sales_order_shipment_save_after` | `AbstractModel::afterSave()` | `['object' => $shipment]` | After shipment saved |
| `sales_order_creditmemo_save_after` | `AbstractModel::afterSave()` | `['object' => $creditmemo]` | After credit memo saved |
| `checkout_onepage_controller_success_action` | `Onepage\Controller\Success::execute()` | `['order_ids' => [...], 'order' => $order]` | Success page only |

### Extension Mechanisms

**Event/Observer:**
- Declarative in `events.xml`
- Dispatched via `EventManager`
- Loose coupling, multiple observers per event
- Used for cross-cutting concerns (logging, notifications, third-party integrations)

**Plugin/Interceptor:**
- Configured in `di.xml`
- AOP-based: `before*`, `after*`, `around*` methods
- Can modify arguments, return values, or replace execution
- Used for method-level interception

**Preference:**
- DI-level class replacement
- Global replacement of an interface/class
- Last resort; breaks extensibility

---

## 15. REST API and GraphQL

### REST Endpoints

| Method | Path | Service | Purpose |
|--------|------|---------|---------|
| POST | `/V1/guest-carts` | `GuestCartManagement::createEmptyCart()` | Create guest cart |
| POST | `/V1/carts/mine` | `CartManagement::createEmptyCartForCustomer()` | Create customer cart |
| POST | `/V1/guest-carts/{cartId}/items` | `GuestCartItemRepository::save()` | Add items |
| POST | `/V1/guest-carts/{cartId}/shipping-information` | `GuestShippingInformationManagement::saveAddressInformation()` | Set address |
| POST | `/V1/guest-carts/{cartId}/set-payment-information` | `GuestPaymentMethodManagement::set()` | Set payment |
| **POST** | **`/V1/guest-carts/{cartId}/payment-information`** | **`GuestPaymentInformationManagement::savePaymentInformationAndPlaceOrder()`** | **Checkout** |
| PUT | `/V1/guest-carts/{cartId}/order` | `GuestCartManagement::placeOrder()` | Place order |
| GET | `/V1/orders` | `OrderRepository::getList()` | List orders |
| GET | `/V1/orders/{id}` | `OrderRepository::get()` | Get order |
| GET | `/V1/carts/{cartId}/payment-methods` | `PaymentMethodManagement::getList()` | Payment methods |

### GraphQL Checkout Flow

1. `addProductsToCart(cartId, cartItems)` — add products
2. `setShippingAddressesOnCart(cartId, addresses)` — set address
3. `setBillingAddressOnCart(cartId, billingAddress)` — set billing
4. `setShippingMethodsOnCart(cartId, methods)` — set shipping
5. `setPaymentMethodOnCart(cartId, paymentMethod)` — set payment
6. `placeOrder(cartId)` — place order

---

## 16. Admin Order Management

### Admin Grid

- `sales_order_grid` — denormalized order list
- Synced via `SalesOrderIndexGridSyncInsert` observer
- Used for order listing/search/filtering

### Order View

- Admin order view loads from `sales_order` + related entities
- Actions: invoice, shipment, credit memo, cancel, hold, unhold, reorder
- Each action uses corresponding service/repository

### Actions

- **Invoice**: `InvoiceService::prepareInvoice()` → `InvoiceRepository::save()` → `Invoice::capture()`
- **Shipment**: `ShipmentFactory::create()` → `Shipment::register()` → `ShipmentRepository::save()`
- **Credit Memo**: `CreditmemoFactory::createByOrder()` → `CreditmemoService::refund()`
- **Cancel**: `Order::cancel()` → `OrderRepository::save()`
- **Hold/Unhold**: `Order::hold()` / `Order::unhold()`

---

## 17. Complete End-to-End Technical Trace

### Scenario: Customer Places Order with Braintree authorize_capture

| Layer | Explanation |
|-------|-------------|
| **Business** | Customer adds product, enters address, selects shipping, pays with Braintree, clicks Place Order |
| **API** | `POST /V1/guest-carts/{id}/payment-information` |
| **Service Contract** | `GuestPaymentInformationManagementInterface::savePaymentInformationAndPlaceOrder()` |
| **Magento Service** | `GuestPaymentInformationManagement` → `GuestCartManagement` → `QuoteManagement` |
| **PHP** | `QuoteManagement::placeOrderRun()` → `submitQuote()` → `OrderManagement::place()` → `Order::place()` → `Order\Payment::place()` → `SaleOperation::execute()` |
| **Database** | `quote` (deactivated), `sales_order` (created), `sales_order_item` (created), `sales_order_address` (created), `sales_order_payment` (created), `sales_invoice` (auto-created), `sales_payment_transaction` (capture recorded), `inventory_reservation` (negative qty) |
| **Events** | `checkout_submit_before` → `sales_model_service_quote_submit_before` → `sales_order_place_before` → `sales_order_payment_place_start` → `sales_order_payment_place_end` → `sales_order_place_after` → `sales_model_service_quote_submit_success` → `checkout_submit_all_after` |
| **Plugins** | `VerifyIsGuestCheckoutEnabledBeforeSavePaymentInformation` (guest validation), `AppendReservationsAfterOrderPlacementPlugin` (inventory) |
| **Payment** | Braintree `sale()` called, $34.00 captured, transaction ID recorded |
| **Inventory** | Negative reservation for 1 unit, salable qty decreased |
| **Email** | Order confirmation email sent |

### Scenario: Admin Ships Order

| Layer | Explanation |
|-------|-------------|
| **Business** | Admin creates shipment, adds tracking number |
| **Admin UI** | Order view → Shipments → Create Shipment |
| **Service** | `ShipmentService` → `ShipmentFactory::create()` |
| **PHP** | `Shipment::register()` → `ShipmentRepository::save()` |
| **Database** | `sales_shipment`, `sales_shipment_item`, `sales_shipment_track` created; `sales_order_item.qty_shipped` updated; `inventory_source_item.quantity` decremented; compensating reservation added |
| **Events** | `sales_order_shipment_save_after` |

### Scenario: Customer Refund

| Layer | Explanation |
|-------|-------------|
| **Business** | Admin creates credit memo, processes refund |
| **Admin UI** | Order view → Invoices → Credit Memo |
| **Service** | `CreditmemoService::refund()` |
| **PHP** | `RefundOperation::execute()` → `Order\Payment::refund()` |
| **Database** | `sales_creditmemo`, `sales_creditmemo_item` created; `sales_order_item.qty_refunded` updated; `sales_order.total_refunded` updated; return-to-stock if applicable |
| **Events** | `sales_order_creditmemo_save_after` |

---

## 18. Common Problems and Debugging

### Order Not Created

- Check `core_config_data` for payment method enabled/disabled
- Validate quote has items (`$quote->getItemsQty() > 0`)
- Check shipping/billing addresses are set
- Verify payment method is available for country/currency
- Check minimum order amount configuration
- Review `var/log/exception.log` for stack traces
- Enable developer mode: `bin/magento deploy:mode:set developer`

### Stock Not Behaving as Expected

- Check `inventory_reservation` for negative/positive entries
- Verify `inventory_source_item.quantity` matches expectations
- Remember: reservation does NOT change physical stock until shipment
- Salable qty = physical + reservations - min_qty
- Run `bin/magento indexer:reindex` if grid/index stale

### Invoice Cannot Be Created

- Check `$order->canInvoice()`: state must not be canceled/complete/closed
- Verify items have `qtyToInvoice > 0` (not already fully invoiced)
- Check `ACTION_FLAG_INVOICE` is not set to false
- For `authorize_capture`: invoice is auto-created during `placeOrder()`
- For `authorize`: invoice must be created manually later

### Shipment Cannot Be Created

- Check `$order->canShip()`: order must not be virtual/canceled
- Verify items have `qtyToShip > 0`
- Check `ACTION_FLAG_SHIP` is not set to false
- Ensure invoice exists if required by configuration

### Debugging Techniques

- **Magento logs**: `var/log/system.log`, `var/log/exception.log`
- **Database queries**: enable query logging in `app/etc/env.php`
- **Magento CLI**: `bin/magento cache:flush`, `bin/magento indexer:reindex`, `bin/magento setup:di:compile`
- **Xdebug**: set breakpoints in `QuoteManagement::submitQuote()`, `Order::place()`
- **Event tracing**: enable `dev/debug/debug_logging` in `core_config_data`
- **Plugin tracing**: check generated interceptors in `generated/code/`

---

## Appendix: Quick Reference

### Order State Machine

```
                    ┌─────────────────┐
                    │     new         │
                    └────────┬────────┘
                             │ payment placed
                    ┌────────▼────────┐
                    │  processing     │◄─────┐
                    └────────┬────────┘      │
                             │ all shipped   │ hold/unhold
                    ┌────────▼────────┐      │
                    │   complete      │      │
                    └────────┬────────┘      │
                             │ refunded       │
                    ┌────────▼────────┐      │
                    │    closed       │      │
                    └─────────────────┘      │
                                            ┌──▼───────┐
                                            │ holded   │
                                            └──────────┘

                    ┌─────────────────┐
                    │  payment_review │
                    └────────┬────────┘
                             │ accept/deny
                    ┌────────▼────────┐
                    │  processing     │
                    └─────────────────┘

                    ┌─────────────────┐
                    │   canceled      │
                    └─────────────────┘  (terminal)
```

### Database Table Quick Reference

| Table | Purpose | PK | FK To |
|-------|---------|----|-------|
| `quote` | Cart/quote | `entity_id` | `store_id` → `store` |
| `quote_item` | Quote line items | `item_id` | `quote_id` → `quote` |
| `quote_address` | Quote addresses | `address_id` | `quote_id` → `quote` |
| `quote_payment` | Quote payment | `payment_id` | `quote_id` → `quote` |
| `sales_order` | Order header | `entity_id` | `customer_id` → `customer_entity` |
| `sales_order_item` | Order items | `item_id` | `order_id` → `sales_order` |
| `sales_order_address` | Order addresses | `entity_id` | `parent_id` → `sales_order` |
| `sales_order_payment` | Order payment | `entity_id` | `parent_id` → `sales_order` |
| `sales_order_status_history` | Status history | `entity_id` | `parent_id` → `sales_order` |
| `sales_payment_transaction` | Transactions | `transaction_id` | `order_id` → `sales_order` |
| `sales_invoice` | Invoices | `entity_id` | `order_id` → `sales_order` |
| `sales_invoice_item` | Invoice items | `entity_id` | `parent_id` → `sales_invoice` |
| `sales_shipment` | Shipments | `entity_id` | `order_id` → `sales_order` |
| `sales_shipment_item` | Shipment items | `entity_id` | `parent_id` → `sales_shipment` |
| `sales_shipment_track` | Tracking | `entity_id` | `parent_id` → `sales_shipment` |
| `sales_creditmemo` | Credit memos | `entity_id` | `order_id` → `sales_order` |
| `sales_creditmemo_item` | Credit memo items | `entity_id` | `parent_id` → `sales_creditmemo` |
| `inventory_reservation` | Stock reservations | `reservation_id` | `stock_id` → `inventory_stock` |
| `inventory_source_item` | Physical stock | `source_item_id` | `source_code` → `inventory_source` |

---

**Document version**: 1.0
**Magento version**: 2.4.8
**Last updated**: 2026-09-04
