# Magento 2 — Events, Observers & Plugins

> **Objective**: understand the two main mechanisms Magento 2 provides to
> extend or modify behavior **without touching core code**: Events/Observers
> and Plugins (Interceptors). This guide shows when to use which, with
> AlpineCommerce examples.

---

## 1. Why extend without modifying core?

Magento core code is in `vendor/magento/` or `src/vendor/`. If you modify
it directly:
- Your changes are **lost** on Magento upgrade
- Your changes are **invisible** to other developers (not in Git)
- You **break** the separation between core and custom code

**Solution**: use Events/Observers or Plugins.

---

## 2. Events & Observers

### 2.1 Concept

An **event** is a signal dispatched at a specific point in Magento's
execution. An **observer** is a class that listens to that event and reacts.

```
Magento code executes...
    ↓
$this->eventManager->dispatch('event_name', $data);
    ↓
All observers listening to 'event_name' are executed
    ↓
Execution continues...
```

### 2.2 Dispatching an event

```php
// Any Magento class
$this->eventManager->dispatch('customer_login', [
    'customer' => $customer
]);
```

### 2.3 Declaring an observer

```xml
<!-- etc/events.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <event name="customer_login">
        <observer name="store_setup_log_login"
                  instance="AlpineCommerce\StoreSetup\Observer\CustomerLogin"/>
    </event>
</config>
```

```php
// Observer/CustomerLogin.php
class CustomerLogin
{
    private LoggerInterface $logger;
    
    public function execute(Event $event): void
    {
        $customer = $event->getData('customer');
        $this->logger->info('Customer logged in: ' . $customer->getEmail());
    }
}
```

### 2.4 Multiple observers on the same event

```xml
<event name="sales_order_place_after">
    <observer name="store_setup_log_order"
              instance="AlpineCommerce\StoreSetup\Observer\OrderPlacedAfter"/>
    <observer name="customercare_recalculate_vip"
              instance="AlpineCommerce\CustomerCare\Observer\OrderPlacedAfter"/>
    <observer name="loyalty_add_points"
              instance="AlpineCommerce\LoyaltyProgram\Observer\OrderPlacedAfter"/>
</event>
```

All three observers execute **in order** (sorted by `sortOrder` if specified).

### 2.5 Observer attributes

```xml
<observer name="my_observer"
          instance="Vendor\Module\Observer\MyObserver"
          sortOrder="10"/>
```

| Attribute | Required | Purpose |
|-----------|----------|---------|
| `name` | Yes | Unique identifier |
| `instance` | Yes | Observer class |
| `sortOrder` | No | Execution order (lower = first) |

### 2.6 AlpineCommerce examples

**StoreSetup** — log customer login:
```xml
<!-- etc/events.xml -->
<event name="customer_login">
    <observer name="store_setup_log_login"
              instance="AlpineCommerce\StoreSetup\Observer\CustomerLogin"/>
</event>
```

```php
// Observer/CustomerLogin.php
public function execute(Event $event): void
{
    $customer = $event->getEvent()->getCustomer();
    $this->logger->info('Training CustomerLogin: ' . $customer->getEmail());
}
```

**CustomerCare** — recalculate VIP on order placement:
```xml
<!-- etc/events.xml -->
<event name="sales_order_place_after">
    <observer name="customercare_order_placed"
              instance="AlpineCommerce\CustomerCare\Observer\OrderPlacedAfter"/>
</event>
```

```php
// Observer/OrderPlacedAfter.php
public function execute(Event $event): void
{
    $order = $event->getEvent()->getOrder();
    $this->customerCare->recalculateVipStatus((int) $order->getCustomerId());
}
```

**StoreSetup** — log product save:
```xml
<event name="model_save_after">
    <observer name="store_setup_log_product_save"
              instance="AlpineCommerce\StoreSetup\Observer\ProductSaveAfter"/>
</event>
```

---

## 3. Plugins (Interceptors)

### 3.1 Concept

A **plugin** intercepts a **public method** call. It can modify the arguments
before the method runs, modify the return value after, or replace the method
entirely.

```
Caller
    ↓
Plugin (before)
    ↓
Original method
    ↓
Plugin (after)
    ↓
Return to caller
```

### 3.2 Plugin types

| Type | When | Can modify |
|------|------|------------|
| `before` | Before original method | Arguments |
| `after` | After original method | Return value |
| `around` | Instead of original method | Everything (call original or not) |

### 3.3 Declaring a plugin

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <type name="Magento\Shipping\Model\Carrier\FlatRate">
        <plugin name="storepickup_filter_flatrate"
                type="AlpineCommerce\StorePickup\Plugin\Shipping\FilterFlatRate"
                sortOrder="10"
                disabled="false"/>
    </type>
</config>
```

### 3.4 Before plugin

```php
// Plugin/Shipping/FilterFlatRate.php
class FilterFlatRate
{
    public function beforeCollectRates(
        Carrier\FlatRate $subject,
        $request
    ): array {
        // Modify the $request before the original method runs
        if ($request->getValue('free_shipping') >= 50) {
            $request->setPackageValue(0); // Set to free
        }
        return [$request]; // Must return array of modified arguments
    }
}
```

### 3.5 After plugin

```php
// Plugin/Product/ShowPrice.php
class ShowPrice
{
    public function afterGetPrice(
        Product $subject,
        $result
    ): float {
        // $result is the return value of the original method
        return $result * 1.2; // Add 20% markup
    }
}
```

### 3.6 Around plugin

```php
// Plugin/Logger/LogCalls.php
class LogCalls
{
    public function aroundSave(
        Post $subject,
        callable $proceed,
        $data
    ): PostInterface {
        // Before
        error_log('Saving post...');
        
        // Call original method
        $result = $proceed($data);
        
        // After
        error_log('Post saved with ID: ' . $result->getId());
        
        return $result;
    }
}
```

### 3.7 Plugin attributes

```xml
<plugin name="my_plugin"
        type="Vendor\Module\Plugin\MyPlugin"
        sortOrder="10"
        disabled="false"/>
```

| Attribute | Required | Purpose |
|-----------|----------|---------|
| `name` | Yes | Unique identifier |
| `type` | Yes | Plugin class |
| `sortOrder` | No | Execution order (lower = first) |
| `disabled` | No | Enable/disable without removing XML |

### 3.8 AlpineCommerce examples

**StorePickup** — filter Flat Rate when free shipping threshold met:
```xml
<!-- etc/di.xml -->
<type name="Magento\Shipping\Model\Carrier\FlatRate">
    <plugin name="storepickup_filter_flatrate"
            type="AlpineCommerce\StorePickup\Plugin\Shipping\FilterFlatRate"/>
</type>
```

```php
// Plugin/Shipping/FilterFlatRate.php
class FilterFlatRate
{
    public function beforeCollectRates(
        Carrier\FlatRate $subject,
        $request
    ): array {
        if ((float) $request->getValue('free_shipping') >= 50) {
            $request->setPackageValue(0);
        }
        return [$request];
    }
}
```

**LoyaltyProgram** — modify minicart totals:
```php
// Plugin/Cart/Total/LoyaltyDiscount.php
class LoyaltyDiscount
{
    public function aroundCollectTotal(
        \Magento\Quote\Model\Quote $subject,
        callable $proceed
    ): void {
        // Add loyalty discount to totals
        $proceed(); // Call original
        // Modify the total
    }
}
```

---

## 4. Events vs Plugins — When to use which?

### 4.1 Comparison

| Aspect | Events/Observers | Plugins |
|--------|------------------|---------|
| **Target** | Any method that dispatches an event | Any **public** method |
| **Multiple** | Multiple observers per event | Only one plugin per method per type |
| **Order** | `sortOrder` | `sortOrder` |
| **Arguments** | Can modify (object reference) | Can modify (before/around) |
| **Return value** | Cannot modify | Can modify (after/around) |
| **Performance** | Slightly slower (event dispatch overhead) | Faster (direct interception) |
| **Stability** | Stable (events rarely change) | Fragile (method signature changes break plugin) |

### 4.2 When to use Events/Observers

✅ **Use events when**:
- You want to react to something happening (log, notify, trigger side effect)
- Multiple modules need to react to the same event
- The target method already dispatches an event
- You don't need to modify the return value

❌ **Don't use events when**:
- You need to modify the return value of a method
- You need to prevent the original method from running
- Performance is critical

### 4.3 When to use Plugins

✅ **Use plugins when**:
- You need to modify the return value of a method
- You need to modify the arguments before the method runs
- You need to replace the method entirely (around)
- Performance is important

❌ **Don't use plugins when**:
- The method is `final` or `static`
- The method is `protected`/`private` (plugins only work on `public`)
- The class is already intercepted by another plugin of the same type
- The method signature changes frequently

### 4.4 Decision tree

```
Need to extend Magento behavior?
    │
    ├─ Does the method dispatch an event?
    │   ├─ YES → Use Observer
    │   └─ NO ↓
    │
    ├─ Is the method public?
    │   ├─ YES → Use Plugin
    │   └─ NO ↓
    │
    ├─ Can you use a preference?
    │   ├─ YES → Use Preference (Interface)
    │   └─ NO ↓
    │
    └─ You may need to modify core (last resort)
```

---

## 5. Advanced: Preferences

A **preference** is a Magento DI feature that tells Magento: "Whenever you
need `InterfaceA`, use `ImplementationB` instead."

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <preference for="Magento\Shipping\Model\Carrier\CarrierInterface"
                type="AlpineCommerce\StorePickup\Model\Carrier\StorePickup"/>
</config>
```

**Warning**: preferences are **global**. Only one preference per interface.
Use them sparingly. Prefer plugins for modifications.

---

## 6. Advanced: Around plugins and calling the original

```php
public function aroundGetPrice(
    Product $subject,
    callable $proceed
): float {
    // Before
    $originalPrice = $proceed(); // Calls the original getPrice()
    
    // After
    return $originalPrice * 1.1;
}
```

**Rule**: in an `around` plugin, you **must** call `$proceed()` at some point,
unless you intentionally want to skip the original method.

---

## 7. Common pitfalls

### 7.1 Plugin on a non-public method

```php
// ❌ Won't work: method is protected
protected function calculatePrice() { ... }

// ✅ Must be public
public function calculatePrice() { ... }
```

### 7.2 Plugin on a final class/method

```php
// ❌ Won't work: class is final
final class Calculator { ... }

// ✅ Must be non-final
class Calculator { ... }
```

### 7.3 Multiple plugins of the same type

```xml
<!-- ❌ Conflict: two around plugins on the same method -->
<type name="SomeClass">
    <plugin name="plugin1" type="Plugin1"/>
    <plugin name="plugin2" type="Plugin2"/>
</type>
```

Only one plugin of each type (`before`, `after`, `around`) can be active per
method. The second one is ignored with a warning.

### 7.4 Forgetting to return in before plugins

```php
// ❌ Wrong: must return an array
public function beforeSave(Post $subject, $title)
{
    $title = strtoupper($title);
    // Missing return!
}

// ✅ Correct
public function beforeSave(Post $subject, $title): array
{
    return [strtoupper($title)];
}
```

### 7.5 Observer not firing

```xml
<!-- ❌ Wrong: event name typo -->
<event name="customer_loggin"> <!-- missing 'g' -->

<!-- ✅ Correct -->
<event name="customer_login">
```

---

## 8. Debugging Events and Plugins

### 8.1 List all observers for an event

```bash
grep -r "event name=" src/app/code/AlpineCommerce/*/etc/events.xml
```

### 8.2 List all plugins for a class

```bash
grep -r "type name=\"Magento" src/app/code/AlpineCommerce/*/etc/di.xml | grep plugin
```

### 8.3 Enable plugin logging

```bash
# In etc/di.xml, add:
<type name="Magento\Framework\Interception\Interceptor">
    <arguments>
        <argument name="plugins" xsi:type="array">
            <item name="logger" xsi:type="object">Magento\Framework\Interception\Plugin\Logger</item>
        </argument>
    </arguments>
</type>
```

### 8.4 Test an observer

```bash
# Trigger the event manually
php bin/magento cache:flush
# Then trigger the action that dispatches the event (e.g., login, place order)
# Check logs:
tail -f var/log/system.log
```

### 8.5 Test a plugin

```bash
# Enable developer mode
php bin/magento deploy:mode:set developer

# Clear generated code
rm -rf generated/code/ generated/metadata/

# Recompile
php bin/magento setup:di:compile

# Trigger the method and check if plugin runs
```

---

## 9. AlpineCommerce reference

### 9.1 Events used

| Event | Module | Observer | Purpose |
|-------|--------|----------|---------|
| `customer_login` | StoreSetup | `CustomerLogin` | Log customer login |
| `sales_order_place_after` | StoreSetup | `OrderPlacedAfter` | Log order placement |
| `sales_order_place_after` | CustomerCare | `OrderPlacedAfter` | Recalculate VIP status |
| `checkout_onepage_controller_success_action` | StoreSetup | `CheckoutSuccess` | Log checkout completion |
| `model_save_after` | StoreSetup | `ProductSaveAfter` | Log product save |

### 9.2 Plugins used

| Target class | Plugin class | Type | Purpose |
|--------------|--------------|------|---------|
| `Magento\Shipping\Model\Carrier\FlatRate` | `StorePickup\Plugin\Shipping\FilterFlatRate` | before | Filter flat rate when free shipping threshold met |
| `Magento\Checkout\Block\Cart\Sidebar` | `LoyaltyProgram\Plugin\Cart\Sidebar` | after | Add loyalty points display to minicart |

---

## 10. Summary

| Concept | Purpose | When to use |
|---------|---------|-------------|
| **Event** | Signal dispatched at a specific point | When you need to react to something happening |
| **Observer** | Listens to an event | Multiple reactions to the same event, side effects |
| **Plugin (before)** | Modify arguments before method | Change input parameters |
| **Plugin (after)** | Modify return value after method | Change output without touching logic |
| **Plugin (around)** | Replace method entirely | Full control, logging, caching |
| **Preference** | Replace an interface implementation | When you need a completely different implementation |

### Key rules

1. **Events**: use for side effects (logging, notifications, triggering other actions)
2. **Plugins**: use for modifying behavior (arguments, return value)
3. **Never modify core code**: always use events, plugins, or preferences
4. **One plugin per type per method**: if two plugins target the same method with the same type, only one works
5. **Public methods only**: plugins cannot intercept protected/private/final methods

### AlpineCommerce patterns

- **StoreSetup**: uses events for logging (customer_login, sales_order_place_after)
- **CustomerCare**: uses event to recalculate VIP on order placement
- **StorePickup**: uses plugin to modify Flat Rate carrier behavior
- **LoyaltyProgram**: uses plugin to modify minicart sidebar

---

*Last updated: 2026-08-11.*
