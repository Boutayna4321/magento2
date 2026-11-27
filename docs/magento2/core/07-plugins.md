# Magento 2 from Scratch — Plugins (Interceptors)

> **Series**: [Learning Path](../README.md) → Phase 3, Article 2 of 3
> **Previous**: [`06-events-observers.md`](06-events-observers.md) — Events & Observers
> **Next**: [`08-preferences.md`](08-preferences.md) — Preferences
> **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)

---

Observers **react** to events. Plugins **intercept** method calls — they can
modify arguments before a method runs, modify the return value after, or
replace the method entirely.

This is how you change what a method *does* without editing the class.

---

## 1. The interception chain

When you call a method on a Magento class, you actually call a generated
**interceptor** that runs all plugins in order:

```
Caller calls $subject->method($arg)
    ↓
[Plugin: before]  ← can modify $arg
    ↓
Original method()  ← the class's actual method
    ↓
[Plugin: after]    ← can modify return value
    ↓
Result returned to caller
```

---

## 2. Plugin types

| Type | Method name prefix | Can modify | When to use |
|------|-------------------|------------|-------------|
| `before` | `before{MethodName}` | Arguments | Change input before the method runs |
| `after` | `after{MethodName}` | Return value | Change output after the method runs |
| `around` | `around{MethodName}` | Everything | Full control (rare — use sparingly) |

---

## 3. Declaring a plugin

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <type name="Magento\Sales\Model\Order">
        <plugin name="alpinecommerce_customer_care_vip"
                type="AlpineCommerce\CustomerCare\Plugin\Order\AfterPlace"
                sortOrder="10"
                disabled="false"/>
    </type>
</config>
```

| Attribute | Required | Purpose |
|-----------|----------|---------|
| `name` | Yes | Unique identifier |
| `type` | Yes | Plugin class |
| `sortOrder` | No | Execution order (lower = first) |
| `disabled` | No | Can be toggled without removing XML |

---

## 4. Before plugin — modify arguments

```php
// src/app/code/AlpineCommerce/CustomerCare/Plugin/Order/AfterPlace.php
namespace AlpineCommerce\CustomerCare\Plugin\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

class AfterPlace
{
    public function beforePlace(Order $subject): ?array
    {
        // Runs BEFORE Order::place()
        // Return array of modified arguments (or null to not modify)
        return null;
    }
}
```

The return value of a `before` plugin must be an **array of arguments** that
replace the originals. Return `null` to leave them unchanged.

---

## 5. After plugin — modify return value

```php
class AfterPlace
{
    public function afterPlace(Order $subject, OrderInterface $result): OrderInterface
    {
        // Runs AFTER Order::place()
        // $result is the return value
        // $subject is the original object

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
}
```

The return value of an `after` plugin **replaces** the original return value.

---

## 6. Around plugin — full control (rare)

```php
public function aroundSave(Model $subject, callable $proceed, $data)
{
    // Before the original method
    $this->logger->info('Saving...');

    // Call the original method
    $result = $proceed($data);

    // After the original method
    $this->logger->info('Saved: ' . $result->getId());

    return $result;
}
```

**Rule**: in an `around` plugin, you **must** call `$proceed(...)` at some
point — otherwise the original method is skipped entirely.

> **Avoid around plugins** unless you need full control. They make debugging
> harder and can cause performance issues. Use `before` or `after` instead.

---

## 7. Plugin limitations

You **cannot** create a plugin on:

- **`final`** classes or methods
- **`static`** methods
- **`private`** or **`protected`** methods (only `public`)
- Classes that are **already intercepted** by another `around` plugin of the
  same module

---

## 8. How AlpineCommerce uses plugins

| Target class | Plugin class | Type | Purpose |
|--------------|-------------|------|---------|
| `Magento\Shipping\Model\Carrier\FlatRate` | `StorePickup\Plugin\Shipping\FilterFlatRate` | `before` | Hide flat rate when store pickup selected |
| `Magento\Sales\Model\Order` | `CustomerCare\Plugin\Order\AfterPlace` | `after` | Recalculate VIP after order placement |
| `Magento\Sales\Api\OrderRepositoryInterface` | `LoyaltyProgram\Plugin\Order\AfterSave` | `after` | Deduct loyalty points after order save |
| `AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface` | `LoyaltyProgram\Plugin\Invoice\AfterSave` | `after` | Award points after invoice save |
| `Magento\Sales\Model\Order` | `CreditMemo\Plugin\OrderCancelPlugin` | `after` | Auto-create credit memo on cancellation |

---

## 9. What you should remember

1. **Plugins** intercept public method calls — `before`, `after`, `around`.
2. **`before`** → modify arguments (return array); **`after`** → modify return
   value; **`around`** → full control (call `$proceed` to invoke original).
3. Declared in `di.xml` with `sortOrder` for ordering.
4. Cannot intercept `final`, `static`, `private`, or `protected` methods.
5. Use **before/after** whenever possible. Avoid `around` unless you need
   full control.
6. Plugins are safer than events for modifying return values; events are
   better for side effects (see [previous article](06-events-observers.md)).

---

## 10. Where to go next

- **Article 3**: [`08-preferences.md`](08-preferences.md) — when to swap
  implementations entirely
- **Previous**: [Events & Observers](06-events-observers.md)
- **Phase 4**: [`09-rest-graphql.md`](09-rest-graphql.md) →
  [`10-ui-components.md`](10-ui-components.md)
- **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)
  — the decision tree, debugging, and all pitfalls covered.

---

*Sources: `src/app/code/AlpineCommerce/CustomerCare/etc/di.xml`,
`src/app/code/AlpineCommerce/CustomerCare/Plugin/Order/AfterPlace.php`,
`src/vendor/magento/framework/Interception/Interceptor.php`,
`src/vendor/magento/framework/Interception/PluginListInterface.php`*
