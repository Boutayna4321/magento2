# Magento 2 — Payment Providers

> **Target audience**: developers who want to understand **exactly what happens**
> inside Magento 2 when a customer selects and pays with a payment method.
> This document covers **Magento 2.4.8 Core only**. It does not cover
> project-specific modules, customizations, or third-party integrations.

---

## Table of Contents

1. [What Is a Payment Provider in Magento 2?](#1-what-is-a-payment-provider-in-magento-2)
2. [Payment Method Interface](#2-payment-method-interface)
3. [AbstractMethod — Legacy Base Class](#3-abstractmethod--legacy-base-class)
4. [Adapter — Modern Gateway Facade](#4-adapter--modern-gateway-facade)
5. [Offline Payment Methods](#5-offline-payment-methods)
6. [Online Payment Methods](#6-online-payment-methods)
7. [Payment Gateway Architecture](#7-payment-gateway-architecture)
8. [Payment Actions — Order, Authorize, Capture](#8-payment-actions--order-authorize-capture)
9. [Transaction Management](#9-transaction-management)
10. [Payment Flow — Checkout to Capture](#10-payment-flow--checkout-to-capture)
11. [Database Model](#11-database-model)
12. [Payment Events, Observers and Plugins](#12-payment-events-observers-and-plugins)
13. [Admin Payment Management](#13-admin-payment-management)
14. [Common Problems and Debugging](#14-common-problems-and-debugging)

---

## 1. What Is a Payment Provider in Magento 2?

### Business Perspective

A **payment provider** (or **payment method**) is a service that processes
financial transactions between a customer and the store. Magento 2 supports two
categories:

- **Offline payments** — No real-time processing. Examples: Check / Money Order,
  Bank Transfer, Cash on Delivery, Purchase Order. The store receives payment
  outside the system and must manually reconcile.
- **Online payments** — Real-time processing through a payment gateway. Examples:
  PayPal, Braintree, credit card processors. The gateway communicates with
  acquiring banks and returns an authorization or capture result.

### Technical Perspective

Technically, a payment provider is a PHP class that implements
`Magento\Payment\Model\MethodInterface`. Magento 2.4.8 provides two base
implementations:

- `Magento\Payment\Model\Method\AbstractMethod` — Legacy, model-based approach
  (deprecated but still used by offline payments).
- `Magento\Payment\Model\Method\Adapter` — Modern, gateway-based approach that
  delegates to a command pool, validator pool, and value handler pool.

Each payment method is configured via `config.xml` and `system.xml` and
registered in the `payment` section of Magento's configuration tree.

### Difference Between Core Payment Entities

```
Quote Payment
   │  Payment data attached to a quote during checkout.
   │  Lives in quote_payment table (or quote table in newer versions).
   │  Mutable until order is placed.
   ↓
Order Payment
   │  Snapshot of payment data at the moment of order placement.
   │  Lives in sales_order_payment table.
   │  Immutable — should not change after order is placed.
   ↓
Payment Transaction
   │  Individual financial operation linked to the order payment.
   │  Lives in sales_payment_transaction table.
   │  Hierarchical — parent/child relationships for captures, voids, refunds.
   ↓
Invoice
   │  Document proving that a capture (or offline payment) was received.
   │  Lives in sales_invoice table.
   │  Created from order payment.
```

---

## 2. Payment Method Interface

### `Magento\Payment\Model\MethodInterface`

This is the **contract** every payment method must fulfill. Located at
`vendor/magento/module-payment/Model/MethodInterface.php`.

#### Key Constants

| Constant | Value | Meaning |
|----------|-------|---------|
| `ACTION_ORDER` | `'order'` | Place an order without prior authorization |
| `ACTION_AUTHORIZE` | `'authorize'` | Reserve funds without capturing |
| `ACTION_AUTHORIZE_CAPTURE` | `'authorize_capture'` | Reserve and capture in one step |
| `CHECK_USE_FOR_COUNTRY` | `'country'` | Validate country availability |
| `CHECK_USE_FOR_CURRENCY` | `'currency'` | Validate currency availability |
| `CHECK_USE_CHECKOUT` | `'checkout'` | Validate checkout availability |
| `GROUP_OFFLINE` | `'offline'` | Group name for offline methods |

#### Core Methods

```php
interface MethodInterface
{
    // Identity
    public function getCode();                    // e.g., 'checkmo', 'paypal_express'
    public function getTitle();                   // Display name
    public function setStore($storeId);
    public function getStore();

    // Availability checks
    public function canOrder();
    public function canAuthorize();
    public function canCapture();
    public function canCapturePartial();
    public function canCaptureOnce();
    public function canRefund();
    public function canRefundPartialPerInvoice();
    public function canVoid();
    public function canUseInternal();
    public function canUseCheckout();
    public function canEdit();
    public function canFetchTransactionInfo();
    public function canReviewPayment();
    public function canUseForCountry($country);
    public function canUseForCurrency($currencyCode);

    // Gateway flags
    public function isGateway();
    public function isOffline();
    public function isInitializeNeeded();

    // Transaction operations
    public function order(InfoInterface $payment, $amount);
    public function authorize(InfoInterface $payment, $amount);
    public function capture(InfoInterface $payment, $amount);
    public function refund(InfoInterface $payment, $amount);
    public function cancel(InfoInterface $payment);
    public function void(InfoInterface $payment);
    public function fetchTransactionInfo(InfoInterface $payment, $transactionId);
    public function acceptPayment(InfoInterface $payment);
    public function denyPayment(InfoInterface $payment);

    // Configuration
    public function getConfigData($field, $storeId = null);
    public function getConfigPaymentAction();
    public function assignData(DataObject $data);
    public function validate();
    public function isAvailable(?CartInterface $quote = null);
    public function isActive($storeId = null);
    public function initialize($paymentAction, $stateObject);
}
```

---

## 3. AbstractMethod — Legacy Base Class

### `Magento\Payment\Model\Method\AbstractMethod`

Located at `vendor/magento/module-payment/Model/Method/AbstractMethod.php`.

> **Deprecated since 100.0.6** — Use `Magento\Payment\Model\Method\Adapter`
> for new implementations. However, all offline payment methods still extend
> this class in Magento 2.4.8.

#### Protected Properties

```php
protected $_code;                    // Payment method code, e.g., 'checkmo'
protected $_formBlockType;           // Block for checkout form
protected $_infoBlockType;           // Block for order view info
protected $_isGateway = false;       // Online payment gateway
protected $_isOffline = false;       // Offline payment
protected $_canOrder = false;
protected $_canAuthorize = false;
protected $_canCapture = false;
protected $_canCapturePartial = false;
protected $_canCaptureOnce = false;
protected $_canRefund = false;
protected $_canRefundInvoicePartial = false;
protected $_canVoid = false;
protected $_canUseInternal = true;
protected $_canUseCheckout = true;
protected $_isInitializeNeeded = false;
protected $_canFetchTransactionInfo = false;
protected $_canReviewPayment = false;
protected $_canCancelInvoice = false;
```

#### How It Works

`AbstractMethod` stores all configuration in **protected properties** that are
set directly in the subclass constructor or via `initializeData()`. The
`getConfigData()` method reads from `ScopeConfigInterface` using the method's
code as the configuration path prefix.

Example from `Checkmo`:

```php
class Checkmo extends AbstractMethod
{
    protected $_code = 'checkmo';
    protected $_isOffline = true;
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Checkmo::class;
    protected $_infoBlockType = \Magento\OfflinePayments\Block\Info\Checkmo::class;
}
```

#### Status Constants

```php
public const STATUS_UNKNOWN = 'UNKNOWN';
public const STATUS_APPROVED = 'APPROVED';
public const STATUS_ERROR = 'ERROR';
public const STATUS_DECLINED = 'DECLINED';
public const STATUS_VOID = 'VOID';
public const STATUS_SUCCESS = 'SUCCESS';
```

---

## 4. Adapter — Modern Gateway Facade

### `Magento\Payment\Model\Method\Adapter`

Located at `vendor/magento/module-payment/Model/Method/Adapter.php`.

> **Introduced in 100.0.2** — The preferred base class for online payment
> gateways. It delegates all operations to a **command pool**, **validator
> pool**, and **value handler pool**.

#### Architecture

```
Adapter (facade)
   │
   ├── CommandPoolInterface → executes commands (authorize, capture, refund, etc.)
   │     └── Each command = a gateway operation
   │
   ├── ValidatorPoolInterface → validates responses
   │     └── availability, country, currency validators
   │
   ├── ValueHandlerPoolInterface → reads config values
   │     └── active, can_authorize, can_capture, etc.
   │
   └── PaymentDataObjectFactory → wraps payment info for gateway operations
```

#### Key Differences from AbstractMethod

| Aspect | AbstractMethod | Adapter |
|--------|---------------|---------|
| Config storage | Protected properties | `ValueHandlerPoolInterface` via XML config |
| Operation execution | Direct method calls | Command pool delegation |
| Validation | Hardcoded in methods | Validator pool |
| Deprecation status | Deprecated 100.0.6 | Current API |
| Used by | Offline payments | Online payments (PayPal, Braintree) |

#### Example Configuration (di.xml)

```xml
<virtualType name="PayPal\Payment\Method\Adapter" type="Magento\Payment\Model\Method\Adapter">
    <arguments>
        <argument name="code" xsi:type="string">paypal_express</argument>
        <argument name="formBlockType" xsi:type="string">PayPal\Block\Express\Form</argument>
        <argument name="infoBlockType" xsi:type="string">PayPal\Block\Express\Info</argument>
        <argument name="commandPool" xsi:type="object">PayPal\Gateway\CommandPool</argument>
        <argument name="validatorPool" xsi:type="object">PayPal\Gateway\ValidatorPool</argument>
    </arguments>
</virtualType>
```

---

## 5. Offline Payment Methods

### Module: `Magento_OfflinePayments`

Located at `vendor/magento/module-offline-payments/`.

All offline methods extend `Magento\Payment\Model\Method\AbstractMethod` and
set `$_isOffline = true`.

### 5.1 Check / Money Order (`checkmo`)

**File**: `vendor/magento/module-offline-payments/Model/Checkmo.php`

```php
class Checkmo extends AbstractMethod
{
    protected $_code = 'checkmo';
    protected $_isOffline = true;
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Checkmo::class;
    protected $_infoBlockType = \Magento\OfflinePayments\Block\Info\Checkmo::class;
}
```

**Features**:
- Displays mailing address and "payable to" information
- Configurable via `offline_payments/checkmo/` config path
- No transaction processing — purely informational

### 5.2 Bank Transfer (`banktransfer`)

**File**: `vendor/magento/module-offline-payments/Model/Banktransfer.php`

```php
class Banktransfer extends AbstractMethod
{
    protected $_code = 'banktransfer';
    protected $_isOffline = true;
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Banktransfer::class;
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;
}
```

**Features**:
- Displays bank instructions (IBAN, SWIFT, etc.)
- Uses `Instructions` info block for order display
- Configurable bank details via `offline_payments/banktransfer/`

### 5.3 Cash on Delivery (`cashondelivery`)

**File**: `vendor/magento/module-offline-payments/Model/Cashondelivery.php`

```php
class Cashondelivery extends AbstractMethod
{
    protected $_code = 'cashondelivery';
    protected $_isOffline = true;
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Cashondelivery::class;
    protected $_infoBlockType = \Magento\Payment\Block\Info\Instructions::class;
}
```

**Features**:
- Customer pays in cash upon delivery
- No pre-authorization — payment collected by shipping carrier
- Additional fee can be configured via `offline_payments/cashondelivery/`

### 5.4 Purchase Order (`purchaseorder`)

**File**: `vendor/magento/module-offline-payments/Model/Purchaseorder.php`

```php
class Purchaseorder extends AbstractMethod
{
    protected $_code = 'purchaseorder';
    protected $_isOffline = true;
    protected $_formBlockType = \Magento\OfflinePayments\Block\Form\Purchaseorder::class;
    protected $_infoBlockType = \Magento\OfflinePayments\Block\Info\Purchaseorder::class;

    public function assignData(DataObject $data)
    {
        $this->getInfoInstance()->setPoNumber($data->getPoNumber());
        return $this;
    }

    public function validate()
    {
        parent::validate();
        return $this;
    }
}
```

**Features**:
- B2B payment method — requires PO number entry
- Validates PO number presence
- Configurable via `offline_payments/purchaseorder/`

---

## 6. Online Payment Methods

### 6.1 PayPal

**Module**: `Magento_Paypal` + `Magento_PaymentServicesPaypal`

Magento 2.4.8 includes multiple PayPal integrations:

| Module | Purpose |
|--------|---------|
| `Magento_Paypal` | Classic PayPal (Express, Standard, Payflow) |
| `Magento_PaymentServicesPaypal` | Modern PayPal (Smart Payment Buttons, Venmo, Pay Later) |
| `Magento_PaypalGraphQl` | GraphQL mutations for PayPal |
| `Magento_PaymentServicesPaypalGraphQl` | GraphQL for modern PayPal |
| `Magento_ReCaptchaPaypal` | reCAPTCHA protection on PayPal checkout |

#### Modern PayPal Architecture (Payment Services)

The modern PayPal integration uses a **JavaScript SDK** approach:

```
Checkout Page
   │
   ├── PayPal SDK loaded via AddCheckoutComponents observer
   │     └── Renders Smart Payment Buttons
   │
   ├── Customer clicks button → CreatePaypalOrder controller
   │     └── Creates order on PayPal side
   │
   ├── Customer approves → PlaceOrder controller
   │     └── Captures payment, creates Magento order
   │
   └── DomainAssociation controller
       └── Verifies merchant domain with PayPal
```

#### Key Controllers

| Controller | Purpose |
|-----------|---------|
| `SmartButtons/CreatePaypalOrder` | Creates PayPal order from quote |
| `SmartButtons/PlaceOrder` | Places Magento order after PayPal approval |
| `SmartButtons/Cancel` | Cancels PayPal order |
| `SmartButtons/ShippingCallback` | Updates shipping in PayPal |
| `Order/Create` | Creates PayPal order (legacy) |
| `Order/GetCurrentOrder` | Retrieves current PayPal order |
| `SendTrackingInformation` | Sends tracking numbers to PayPal |

#### PayPal Payment Actions

| Action | Behavior |
|--------|----------|
| `Sale` | Immediate capture (Authorization + Capture in one step) |
| `Authorization` | Reserve funds for 3 days (extendable to 29) |
| `Order` | Create a PayPal order (30-day validity) |

### 6.2 Braintree

**Module**: `Magento_Braintree` (if present in codebase)

> Note: `Magento_Braintree` is not present in this codebase but is part of
> Magento 2.4.8 Core. It provides credit card processing, PayPal via Braintree,
> and 3D Secure support.

Braintree uses the **modern Adapter pattern** with a command pool:

| Command | Purpose |
|---------|---------|
| `authorize` | Authorize transaction |
| `capture` | Capture authorized transaction |
| `refund` | Refund captured transaction |
| `void` | Void authorization |

---

## 7. Payment Gateway Architecture

### Gateway Components

```
Payment Gateway
   │
   ├── Config (value handlers)
   │     ├── active
   │     ├── can_authorize
   │     ├── can_capture
   │     ├── can_refund
   │     └── ...
   │
   ├── Validators
   │     ├── AvailabilityValidator — is method available for this quote?
   │     ├── CountryValidator — is billing country allowed?
   │     └── CurrencyValidator — is currency supported?
   │
   ├── Commands (operations)
   │     ├── AuthorizeCommand
   │     ├── CaptureCommand
   │     ├── RefundCommand
   │     ├── VoidCommand
   │     ├── OrderCommand
   │     └── FetchTransactionInfoCommand
   │
   └── Response handlers
         ├── Transaction builder
         ├── Transaction saler (links to order)
         └── Error handler
```

### Gateway Data Flow

```
Customer submits payment
        │
        ▼
Checkout validates payment data
        │
        ▼
Payment method adapter receives command
        │
        ▼
Validator pool checks prerequisites
        │
        ▼
Command executor sends request to gateway
        │
        ▼
Gateway returns response
        │
        ▼
Response handler creates transaction
        │
        ▼
Transaction linked to order payment
```

---

## 8. Payment Actions — Order, Authorize, Capture

### Payment Action Types

Magento 2.4.8 supports three primary payment actions, configured per payment
method at **Stores > Configuration > Sales > Payment Methods**:

| Action | Code | Behavior | Use Case |
|--------|------|----------|----------|
| **Authorize Only** | `authorize` | Reserve funds, capture later | B2B, backorders |
| **Authorize + Capture** | `authorize_capture` | Reserve and capture immediately | Standard retail |
| **Order (Sale)** | `order` | Create a PayPal order (30-day validity) | PayPal express only |

### Authorization Flow

```
Order placed
    │
    ▼
Payment::authorize() called
    │
    ▼
Transaction created: type = 'authorization'
    │
    ▼
Funds reserved at gateway (usually 3-29 days)
    │
    ▼
Order status: pending_payment → processing
    │
    ▼
[Later] Admin captures from order view
    │
    ▼
Payment::capture() called
    │
    ▼
Transaction created: type = 'capture', parent = authorization
    │
    ▼
Funds transferred from customer to merchant
    │
    ▼
Order status: processing
```

### Sale Flow (Authorize + Capture)

```
Order placed
    │
    ▼
Payment::authorize() called
    │
    ▼
Transaction created: type = 'authorization'
    │
    ▼
Payment::capture() called immediately
    │
    ▼
Transaction created: type = 'capture', parent = authorization
    │
    ▼
Order status: processing
```

### Void Flow

```
Authorization exists
    │
    ▼
Admin voids (before capture)
    │
    ▼
Transaction created: type = 'void', parent = authorization
    │
    ▼
Authorization canceled at gateway
    │
    ▼
Order status: canceled (if fully voided)
```

### Refund Flow

```
Capture exists
    │
    ▼
Admin refunds (full or partial)
    │
    ▼
Transaction created: type = 'refund', parent = capture
    │
    ▼
Funds returned to customer
    │
    ▼
Order status: closed (if fully refunded)
```

---

## 9. Transaction Management

### Transaction Model

**Class**: `Magento\Sales\Model\Order\Payment\Transaction`

Located at `vendor/magento/module-sales/Model/Order/Payment/Transaction.php`.

A transaction represents a **single financial operation** on an order. It forms a
hierarchical tree:

```
authorization (root)
    ├── capture (child of authorization)
    │     ├── refund (child of capture)
    │     └── partial_refund (child of capture)
    └── void (child of authorization)
```

### Transaction Types

| Type | Created When | Parent |
|------|-------------|--------|
| `authorization` | Payment authorized | None (root) |
| `order` | PayPal order created | None (root) |
| `capture` | Authorization captured | authorization |
| `void` | Authorization voided | authorization |
| `refund` | Capture refunded | capture |
| `partial_refund` | Partial capture refunded | capture |
| `fetch_transaction_info` | Gateway info fetched | Depends on source |

### Transaction Properties

```php
class Transaction extends AbstractModel implements TransactionInterface
{
    public const RAW_DETAILS = 'raw_details_info';

    protected $_order = null;                    // Parent order payment
    protected $_parentTransaction = null;        // Parent transaction
    protected $_children = null;                 // Child transactions
    protected $_identifiedChildren = null;       // Keyed by txn_id
    protected $_transactionsAutoLinking = true;  // Auto-link parent/child
    protected $_isFailsafe = false;              // Throw on errors
    protected $_eventPrefix = 'sales_order_payment_transaction';
    protected $_eventObject = 'order_payment_transaction';
}
```

### Transaction Linking

Magento automatically links transactions using the `parent_transaction_id`
field. The `Transaction\Manager` class handles:

1. Setting the parent ID on child transactions
2. Closing transactions when appropriate
3. Preventing duplicate transaction IDs

### Transaction Database Table

**Table**: `sales_payment_transaction`

| Column | Type | Purpose |
|--------|------|---------|
| `transaction_id` | PK | Unique transaction ID |
| `parent_id` | FK to `sales_payment_transaction` | Parent transaction |
| `order_id` | FK to `sales_order` | Parent order |
| `payment_id` | FK to `sales_order_payment` | Parent payment record |
| `txn_id` | varchar(100) | Gateway transaction ID |
| `parent_txn_id` | varchar(100) | Gateway parent transaction ID |
| `txn_type` | varchar(15) | authorization, capture, void, refund, etc. |
| `is_closed` | tinyint | Whether transaction is closed |
| `additional_information` | blob | Gateway-specific data (JSON) |
| `created_at` | timestamp | When transaction was created |

---

## 10. Payment Flow — Checkout to Capture

### Step-by-Step Flow

```
1. Customer selects payment method in checkout
   │
   ▼
2. Payment method form rendered (form block)
   │   ├── Offline: simple instructions or PO number field
   │   └── Online: credit card form or redirect button
   │
   ▼
3. Customer enters payment data
   │
   ▼
4. Place Order button clicked
   │
   ▼
5. Quote → Order conversion
   │   ├── quote_payment → sales_order_payment
   │   └── Payment method code stored in sales_order_payment.method
   │
   ▼
6. Payment::place() called (for online methods)
   │   ├── Dispatches sales_order_payment_place_start event
   │   ├── Calls payment method authorize()
   │   ├── Creates authorization transaction
   │   └── Dispatches sales_order_payment_place_end event
   │
   ▼
7. Order saved with payment data
   │
   ▼
8. [If authorize_capture] Payment::capture() called
   │   ├── Creates capture transaction
   │   └── Links to authorization
   │
   ▼
9. Invoice created (if auto-invoicing or manual)
   │   ├── Invoice::register() called
   │   ├── Payment linked to invoice
   │   └── sales_order_payment_pay event dispatched
   │
   ▼
10. Transaction hierarchy finalized
    │   ├── authorization → capture (and optionally refund)
    │   └── All transactions closed appropriately
    │
    ▼
11. Order status updated based on payment state
```

### Key Events During Payment

| Event | Dispatched When | Observers |
|-------|-----------------|-----------|
| `sales_order_payment_place_start` | Before payment place | Custom payment logic |
| `sales_order_payment_place_end` | After payment place | Custom payment logic |
| `sales_order_payment_pay` | Payment captured | Transaction comment plugins |
| `sales_order_payment_cancel_invoice` | Invoice canceled | Custom cancellation logic |

---

## 11. Database Model

### `sales_order_payment`

**Table**: `sales_order_payment`

Stores the payment details for each order. One-to-one with `sales_order`.

| Column | Type | Purpose |
|--------|------|---------|
| `entity_id` | PK | Payment record ID |
| `parent_id` | FK to `sales_order.entity_id` | Parent order |
| `method` | varchar(128) | Payment method code (e.g., 'checkmo') |
| `cc_type` | varchar(20) | Credit card type |
| `cc_number_enc` | varchar(255) | Encrypted card number |
| `cc_last_4` | varchar(4) | Last 4 digits |
| `cc_owner` | varchar(255) | Cardholder name |
| `cc_exp_month` | varchar(20) | Expiration month |
| `cc_exp_year` | varchar(4) | Expiration year |
| `cc_ss_start_month` | varchar(20) | Start month (SS cards) |
| `cc_ss_start_year` | varchar(4) | Start year (SS cards) |
| `po_number` | varchar(255) | Purchase order number |
| `additional_data` | text | Additional payment data |
| `additional_information` | text | JSON: gateway response, 3D Secure, etc. |
| `base_shipping_captured` | decimal(20,4) | Shipping amount captured |
| `shipping_captured` | decimal(20,4) | Shipping captured (store currency) |
| `base_amount_paid` | decimal(20,4) | Total paid (base currency) |
| `amount_paid` | decimal(20,4) | Total paid (store currency) |
| `base_amount_authorized` | decimal(20,4) | Authorized amount (base) |
| `amount_authorized` | decimal(20,4) | Authorized amount (store) |
| `base_amount_canceled` | decimal(20,4) | Canceled amount (base) |
| `amount_canceled` | decimal(20,4) | Canceled amount (store) |
| `base_amount_refunded` | decimal(20,4) | Refunded amount (base) |
| `amount_refunded` | decimal(20,4) | Refunded amount (store) |
| `base_amount_ordered` | decimal(20,4) | Ordered amount (base) |
| `amount_ordered` | decimal(20,4) | Ordered amount (store) |
| `base_shipping_amount` | decimal(20,4) | Shipping amount (base) |
| `shipping_amount` | decimal(20,4) | Shipping amount (store) |
| `last_trans_id` | varchar(255) | Last gateway transaction ID |

### `sales_payment_transaction`

**Table**: `sales_payment_transaction`

Stores individual payment transactions. See §9 for full details.

### Credit Card Encryption

Magento encrypts sensitive credit card data using the store's encryption key:

- `cc_number_enc` — Encrypted card number
- Last 4 digits stored in plain text in `cc_last_4`
- CVV is **never** stored — processed by gateway only
- 3D Secure data stored in `additional_information` JSON field

---

## 12. Payment Events, Observers and Plugins

### Core Payment Events

| Event | Scope | When Dispatched |
|-------|-------|-----------------|
| `sales_order_payment_place_start` | Global | Before payment operation |
| `sales_order_payment_place_end` | Global | After payment operation |
| `sales_order_payment_pay` | Global | Payment captured/paid |
| `sales_order_payment_cancel_invoice` | Global | Invoice canceled |
| `payment_method_is_active` | Method-specific | When checking availability |
| `payment_*_command` | Gateway | During command execution |

### Observer Example: Before Order Payment Save

**File**: `vendor/magento/module-offline-payments/Observer/BeforeOrderPaymentSaveObserver.php`

```php
class BeforeOrderPaymentSaveObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $payment = $observer->getEvent()->getPayment();
        // Filter sensitive data from payment info
    }
}
```

### Plugin: Validate Purchase Order Number

**File**: `vendor/magento/module-offline-payments/Plugin/ValidatePurchaseOrderNumber.php`

Validates that a PO number is present when using the purchaseorder method.

---

## 13. Admin Payment Management

### Payment Method Configuration

**Path**: Stores > Configuration > Sales > Payment Methods

Each payment method has its own configuration section:

| Section | Path | Key Settings |
|---------|------|--------------|
| Check / Money Order | `payment/checkmo` | Title, payable to, mailing address, instructions |
| Bank Transfer | `payment/banktransfer` | Title, account details, instructions |
| Cash on Delivery | `payment/cashondelivery` | Title, fee, instructions |
| Purchase Order | `payment/purchaseorder` | Title, minimum order total |
| PayPal | `payment/paypal_*` | API credentials, payment actions, style |

### Transaction Management

**Path**: Sales > Orders > View > Transactions

The admin order view shows all transactions for the order:

| Transaction Type | Display | Actions |
|-----------------|---------|---------|
| Authorization | Amount, ID, Status | Capture, Void |
| Capture | Amount, ID, Status | Refund (partial/full) |
| Void | Amount, ID, Status | None |
| Refund | Amount, ID, Status | None |

### Payment Actions from Admin

| Action | Menu Path | Behavior |
|--------|-----------|----------|
| Invoice | Sales > Orders > Invoice | Capture payment, create invoice |
| Credit Memo | Sales > Orders > Credit Memo | Refund payment |
| Void | Sales > Orders > Void | Cancel authorization |

---

## 14. Common Problems and Debugging

### Payment Method Not Showing at Checkout

1. Check `isActive()` — method must be enabled in config
2. Check `canUseCheckout()` — must be true for frontend
3. Check `canUseForCountry()` — billing country must be allowed
4. Check `canUseForCurrency()` — currency must be supported
5. Check `isAvailable()` — validator pool must pass all checks

### Authorization Expired

- Authorizations expire after the gateway's time limit (typically 3-29 days)
- Check transaction `created_at` and gateway expiration policy
- Re-authorization may be required

### Capture Fails

- Verify authorization is still valid
- Check `canCapture()` returns true
- Verify amount does not exceed authorization
- Check `canCaptureOnce()` — some methods only allow one capture

### Refund Fails

- Verify capture exists and is settled (gateway-dependent)
- Check `canRefund()` returns true
- Verify refund amount does not exceed captured amount
- Check `canRefundPartialPerInvoice()` for partial refunds

### Transaction Not Linked

- Check `parent_txn_id` in `sales_payment_transaction`
- Verify `_transactionsAutoLinking` is true
- Manually link via `$transaction->setParentTransactionId($parentId)`

---

*Sources: `vendor/magento/module-payment/`, `vendor/magento/module-offline-payments/`, `vendor/magento/module-paypal/`, `vendor/magento/module-sales/`.*
