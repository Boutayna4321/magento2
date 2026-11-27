# Magento 2 from Scratch — Events & Observers

> **Series**: [Learning Path](../README.md) → Phase 3, Article 1 of 3
> **Previous**: [`../js/04-integrating-js.md`](../js/04-integrating-js.md) — Integrating JS
> **Next**: [`07-plugins.md`](07-plugins.md) — Plugins (Interceptors)
> **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)

---

Magento dispatches **events** at every important moment: when an order is
placed, when a customer logs in, when a product is saved. Your module can
**observe** these events and react — logging, sending emails, triggering side
effects — **without modifying Magento's core code**.

This is the safest and most common extension mechanism.

---

## 1. The event dispatch mechanism

Magento uses an **event manager** (`Magento\Framework\Event\Manager`):

```php
// Inside Magento's core code
$this->eventManager->dispatch('sales_order_place_after', [
    'order' => $order
]);
```

When this runs, Magento finds every observer registered for
`sales_order_place_after` in `events.xml` files and calls their `execute()`
method in order.

```
Magento code executes...
    ↓
$this->eventManager->dispatch('event_name', $data)
    ↓
All observers registered for 'event_name' execute (in sortOrder)
    ↓
Execution continues...
```

---

## 2. Declaring an observer

### Step 1: `etc/events.xml`

```xml
<!-- src/app/code/AlpineCommerce/AutoInvoice/etc/events.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <event name="checkout_onepage_controller_success_action">
        <observer name="alpinecommerce_autoinvoice_create_invoice"
                  instance="AlpineCommerce\AutoInvoice\Observer\AutoInvoice"/>
    </event>
</config>
```

- `name` on the observer — unique identifier across the system.
- `instance` — the full class path of the observer.

### Step 2: The observer class

```php
// src/app/code/AlpineCommerce/AutoInvoice/Observer/AutoInvoice.php
namespace AlpineCommerce\AutoInvoice\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AutoInvoice implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        // ... create invoice
    }
}
```

---

## 3. Event areas

Observers can be **area-specific**:

```
etc/events.xml                        ← global (all areas)
etc/frontend/events.xml               ← frontend only
etc/adminhtml/events.xml              ← admin only
etc/webapi_rest/events.xml            ← REST API only
```

> **Best practice**: Always scope your events to the narrowest area. Don't use
> a global `events.xml` if you only need frontend events.

---

## 4. Common Magento events

| Event | When it fires | Typical use |
|-------|--------------|-------------|
| `sales_order_place_after` | After order is placed | Auto-invoice, set return window |
| `checkout_onepage_controller_success_action` | After checkout success | Partial invoice, send notification |
| `customer_login` | After customer logs in | Redirect, log activity |
| `catalog_product_save_after` | After product is saved | Update external system |
| `controller_action_predispatch` | Before any controller runs | Global checks, maintenance |
| `customer_register_success` | After customer registers | Send welcome email |

---

## 5. Multiple observers on the same event

```xml
<event name="sales_order_place_after">
    <observer name="autoinvoice"
              instance="AlpineCommerce\AutoInvoice\Observer\AutoInvoice"/>
    <observer name="rma"
              instance="AlpineCommerce\Rma\Observer\OrderPlaceAfter"/>
</event>
```

Both run, in the order defined by `sortOrder`:

```xml
<observer name="autoinvoice" instance="..." sortOrder="10"/>
<observer name="rma" instance="..." sortOrder="20"/>
```

**Lower sortOrder runs first.**

---

## 6. How AlpineCommerce uses observers

| Module | Event | Observer | Purpose |
|--------|-------|----------|---------|
| AutoInvoice | `checkout_onepage_controller_success_action` | `Observer/AutoInvoice.php` | Create invoice after checkout |
| Rma | `sales_order_place_after` | `Observer/OrderPlaceAfter.php` | Set RMA return window |
| CreditMemo | `sales_order_place_after` | `Observer/OrderPlaceAfter.php` | Prepare credit memo data |

```php
// src/app/code/AlpineCommerce/Rma/Observer/OrderPlaceAfter.php
class OrderPlaceAfter implements ObserverInterface
{
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
}
```

---

## 7. Observer pitfalls

| Mistake | Symptom | Fix |
|---------|---------|-----|
| Typo in event name | Observer never fires | Check the exact event name in `vendor/magento` |
| Missing `implements ObserverInterface` | Class not called | Implement the interface |
| Throwing an exception | Stops the original action | Wrap in try/catch, log errors |
| Heavy logic in observer | Slows down the action | Defer to a background job if possible |

---

## 8. What you should remember

1. **Events** are signals dispatched at specific points (`$this->eventManager->dispatch()`).
2. **Observers** listen and react — they cannot modify the return value of the
   original method.
3. Declare observers in `events.xml`, implement `ObserverInterface`.
4. Events can be **global** or **area-specific** (`frontend/`, `adminhtml/`).
5. Use `sortOrder` to control execution order.
6. Observers are best for **side effects**: logging, notifications, triggering
   other actions. For modifying return values, use **plugins**.

---

## 9. Where to go next

- **Article 2**: [Plugins](07-plugins.md) — modify method behavior without
  events
- **Article 3**: [`08-preferences.md`](08-preferences.md) — swap interface implementations
- **Previous**: [`../js/04-integrating-js.md`](../js/04-integrating-js.md)
- **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)
  — comparison table, debugging, and all AlpineCommerce observer/plugin mappings.

---

*Sources: `src/app/code/AlpineCommerce/AutoInvoice/etc/events.xml`,
`src/app/code/AlpineCommerce/Rma/etc/events.xml`,
`src/vendor/magento/framework/Event/Manager.php`,
`src/vendor/magento/module-sales/etc/events.xml`*
