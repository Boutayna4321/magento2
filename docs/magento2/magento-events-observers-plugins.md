# Magento 2 — Events, Observers & Plugins

> **Objective**: understand the two main mechanisms Magento 2 provides to
> extend or modify behavior **without touching core code**: Events/Observers
> and Plugins (Interceptors). This guide covers **Magento 2.4.8 Core** first,
> then shows how AlpineCommerce applies these patterns in its modules.

---

## Table of Contents

1. [Why extend without modifying core?](#1-why-extend-without-modifying-core)
2. [Events & Observers](#2-events--observers)
3. [Plugins (Interceptors)](#3-plugins-interceptors)
4. [Events vs Plugins — When to use which?](#4-events-vs-plugins--when-to-use-which)
5. [Advanced: Preferences](#5-advanced-preferences)
6. [Advanced: Around plugins and calling the original](#6-advanced-around-plugins-and-calling-the-original)
7. [Common pitfalls](#7-common-pitfalls)
8. [Debugging Events and Plugins](#8-debugging-events-and-plugins)
9. [AlpineCommerce reference](#9-alpinecommerce-reference)
10. [Summary](#10-summary)

---

## 1. Why extend without modifying core?

Magento core code lives in `vendor/magento/` (Composer) or `src/vendor/magento/` (local repo). If you modify it directly:
- Your changes are **lost** on Magento upgrade
- Your changes are **invisible** to other developers (not in Git)
- You **break** the separation between core and custom code

**Solution**: use Events/Observers or Plugins.

**Official documentation**: [Extend Magento](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/)

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

**Source**: `vendor/magento/module-backend/Block/Widget/Button.php` (Magento core dispatches events throughout its lifecycle)

**Official documentation**: [Events and Observers](https://developer.adobe.com/commerce/php/architecture/event-driven-architecture/)

### 2.2 Dispatching an event

```php
// Any Magento class
$this->eventManager->dispatch('customer_login', [
    'customer' => $customer
]);
```

**Source**: `vendor/magento/module-customer/Controller/Account/LoginPost.php` — dispatches `customer_login` after successful authentication.

### 2.3 Declaring an observer

```xml
<!-- etc/events.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework/Event/etc/events.xsd">
    <event name="checkout_onepage_controller_success_action">
        <observer name="vendor_module_observer_name"
                  instance="Vendor\Module\Observer\MyObserver"/>
    </event>
</config>
```

```php
// Observer/MyObserver.php
namespace Vendor\Module\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class MyObserver implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
        // React to the event
    }
}
```

**Source**: `vendor/magento/module-backend/etc/events.xml` — Magento core itself uses observers extensively.

### 2.4 Multiple observers on the same event

```xml
<event name="sales_order_place_after">
    <observer name="module_a_order_action"
              instance="Vendor\ModuleA\Observer\OrderAction"/>
    <observer name="module_b_order_action"
              instance="Vendor\ModuleB\Observer\OrderAction"/>
</event>
```

All observers execute **in order** (sorted by `sortOrder` if specified).

**Source**: `vendor/magento/module-sales/etc/events.xml` — multiple observers listen to `sales_order_place_after`.

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

**Official documentation**: [Create an observer](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/events-and-observers/#create-an-observer)

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

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — the generated interceptor class that wraps original methods.

**Official documentation**: [Plugins (Interceptors)](https://developer.adobe.com/commerce/php/development/components/plugins)

### 3.2 Plugin types

| Type | When | Can modify |
|------|------|------------|
| `before` | Before original method | Arguments |
| `after` | After original method | Return value |
| `around` | Instead of original method | Everything (call original or not) |

### 3.3 Declaring a plugin

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <type name="Magento\Shipping\Model\Carrier\FlatRate">
        <plugin name="vendor_module_plugin_name"
                type="Vendor\Module\Plugin\MyPlugin"
                sortOrder="10"
                disabled="false"/>
    </type>
</config>
```

**Source**: `vendor/magento/module-offline-shipping/etc/di.xml` — Magento core registers plugins for its own classes.

### 3.4 Before plugin

```php
// Plugin/MyPlugin.php
namespace Vendor\Module\Plugin;

class MyPlugin
{
    public function beforeCollectRates(
        \Magento\Shipping\Model\Carrier\FlatRate $subject,
        $request
    ): array {
        // Modify the $request before the original method runs
        if ((float) $request->getValue('free_shipping') >= 50) {
            $request->setPackageValue(0); // Set to free
        }
        return [$request]; // Must return array of modified arguments
    }
}
```

**Source**: `vendor/magento/module-tax/Plugin/Shipping/Rate.php` — Magento core uses before plugins to modify shipping rate requests.

### 3.5 After plugin

```php
// Plugin/MyPlugin.php
namespace Vendor\Module\Plugin;

class MyPlugin
{
    public function afterGetPrice(
        \Magento\Catalog\Model\Product $subject,
        $result
    ): float {
        // $result is the return value of the original method
        return $result * 1.2; // Add 20% markup
    }
}
```

**Source**: `vendor/magento/module-tax/Plugin/Model/Product/Attribute/Source/CountryOfManufacture.php` — Magento core uses after plugins to modify product attributes.

### 3.6 Around plugin

```php
// Plugin/MyPlugin.php
namespace Vendor\Module\Plugin;

class MyPlugin
{
    public function aroundSave(
        \Magento\Catalog\Model\Product $subject,
        callable $proceed,
        $data
    ): \Magento\Catalog\Api\Data\ProductInterface {
        // Before
        error_log('Saving product...');
        
        // Call original method
        $result = $proceed($data);
        
        // After
        error_log('Product saved with ID: ' . $result->getId());
        
        return $result;
    }
}
```

**Source**: `vendor/magento/module-logging/Plugin/Catalog/Model/Product.php` — Magento core uses around plugins for logging.

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

### 3.8 How plugins are generated

Magento generates interceptor classes during compilation:

```bash
php bin/magento setup:di:compile
```

**Generated file example**: `generated/code/Magento/Catalog/Model/Product/Interceptor.php`

The interceptor wraps the original class and delegates to plugins:

```php
class Interceptor extends Product implements ProductInterface
{
    public function save()
    {
        // Plugin chain execution
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'save');
        if (!$pluginInfo) {
            return $this->subject->save();
        }
        // ... plugin execution
    }
}
```

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — the base interceptor implementation.

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

**Official documentation**: [Extension points](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/)

---

## 5. Advanced: Preferences

A **preference** is a Magento DI feature that tells Magento: "Whenever you
need `InterfaceA`, use `ImplementationB` instead."

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
    <preference for="Magento\Shipping\Model\Carrier\CarrierInterface"
                type="Vendor\Module\Model\Carrier\CustomCarrier"/>
</config>
```

**Warning**: preferences are **global**. Only one preference per interface.
Use them sparingly. Prefer plugins for modifications.

**Source**: `vendor/magento/module-offline-shipping/etc/di.xml` — Magento core uses preferences for carrier implementations.

**Official documentation**: [Dependency injection preferences](https://developer.adobe.com/commerce/php/architecture/modules/di/)

---

## 6. Advanced: Around plugins and calling the original

```php
public function aroundGetPrice(
    \Magento\Catalog\Model\Product $subject,
    callable $proceed,
    $data
): float {
    // Before
    $originalPrice = $proceed(); // Calls the original getPrice()
    
    // After
    return $originalPrice * 1.1;
}
```

**Rule**: in an `around` plugin, you **must** call `$proceed()` at some point,
unless you intentionally want to skip the original method.

**Source**: `vendor/magento/module-catalog/Plugin/Model/Product/Attribute/Backend/Price.php` — Magento core uses around plugins for price validation.

---

## 7. Common pitfalls

### 7.1 Plugin on a non-public method

```php
// ❌ Won't work: method is protected
protected function calculatePrice() { ... }

// ✅ Must be public
public function calculatePrice() { ... }
```

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — plugins only work on public methods.

### 7.2 Plugin on a final class/method

```php
// ❌ Won't work: class is final
final class Calculator { ... }

// ✅ Must be non-final
class Calculator { ... }
```

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — final classes cannot be intercepted.

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

**Source**: `vendor/magento/framework/Interception/PluginListGenerator.php` — generates the plugin list and detects conflicts.

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

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — before plugins must return an array of arguments.

### 7.5 Observer not firing

```xml
<!-- ❌ Wrong: event name typo -->
<event name="customer_loggin"> <!-- missing 'g' -->

<!-- ✅ Correct -->
<event name="customer_login">
```

**Source**: `vendor/magento/module-customer/etc/events.xml` — correct event names are defined in Magento core.

---

## 8. Debugging Events and Plugins

### 8.1 List all observers for an event

```bash
grep -r "event name=" vendor/magento/module-*/etc/events.xml app/code/*/etc/events.xml
```

**Source**: `vendor/magento/module-*/etc/events.xml` — all Magento core events are defined here.

### 8.2 List all plugins for a class

```bash
grep -r "type name=\"Magento" vendor/magento/module-*/etc/di.xml | grep plugin
```

**Source**: `vendor/magento/module-*/etc/di.xml` — all Magento core plugins are registered here.

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

**Source**: `vendor/magento/framework/Interception/Plugin/Logger.php` — Magento core includes a plugin logger for debugging.

### 8.4 Test an observer

```bash
# Trigger the event manually
php bin/magento cache:flush
# Then trigger the action that dispatches the event (e.g., login, place order)
# Check logs:
tail -f var/log/system.log
```

**Source**: `vendor/magento/module-customer/Controller/Account/LoginPost.php` — dispatches `customer_login` event.

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

**Source**: `vendor/magento/framework/Interception/Interceptor.php` — generated interceptors are cached in `generated/code/`.

---

## 9. AlpineCommerce reference

The following patterns show how AlpineCommerce applies Magento's Events/Observers
and Plugins in its custom modules. These are **project-specific implementations**
built on top of Magento 2 Core extension mechanisms.

### 9.1 Events used

| Event | Module | Observer | Purpose |
|-------|--------|----------|---------|
| `checkout_onepage_controller_success_action` | AutoInvoice | `AutoInvoice` | Create invoice on checkout success |
| `sales_order_place_after` | CreditMemo | `OrderPlaceAfter` | Prepare credit memo data on order placement |
| `sales_order_place_after` | Rma | `OrderPlaceAfter` | Set return window on order placement |

**Source**: `src/app/code/AlpineCommerce/AutoInvoice/etc/events.xml`

### 9.2 Plugins used

| Target class | Plugin class | Type | Purpose |
|--------------|--------------|------|---------|
| `Magento\Shipping\Model\Carrier\FlatRate` | `StorePickup\Plugin\Shipping\FilterFlatRate` | before | Filter flat rate when free shipping threshold met |
| `Magento\Checkout\Block\Cart\Sidebar` | `LoyaltyProgram\Plugin\LoyaltyIncentive` | after | Add loyalty points display to minicart |
| `Magento\Sales\Model\Order` | `CustomerCare\Plugin\Order\AfterPlace` | after | Recalculate VIP status after order placement |
| `Magento\Catalog\Api\ProductRepositoryInterface` | `StoreSetup\Plugin\Product\BeforeSave` | before | Log product save |
| `AlpineCommerce\LoyaltyProgram\Api\LoyaltyBalanceRepositoryInterface` | `LoyaltyProgram\Plugin\Invoice\AfterSave` | after | Award points after invoice save |
| `Magento\Sales\Model\Order` | `CreditMemo\Plugin\OrderCancelPlugin` | after | Auto-create credit memo on cancellation |
| `Magento\Sales\Api\OrderRepositoryInterface` | `LoyaltyProgram\Plugin\Order\AfterSave` | after | Deduct loyalty points after order save |

**Sources**:
- `src/app/code/AlpineCommerce/StorePickup/etc/di.xml`
- `src/app/code/AlpineCommerce/LoyaltyProgram/etc/di.xml`
- `src/app/code/AlpineCommerce/LoyaltyProgram/etc/frontend/di.xml`
- `src/app/code/AlpineCommerce/CustomerCare/etc/di.xml`
- `src/app/code/AlpineCommerce/StoreSetup/etc/di.xml`
- `src/app/code/AlpineCommerce/CreditMemo/etc/di.xml`

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

- **AutoInvoice**: uses observer for checkout success event
- **CustomerCare**: uses plugin on `Order::place()` to recalculate VIP after order placement
- **StoreSetup**: uses plugin on `ProductRepositoryInterface::save()` to log product save
- **StorePickup**: uses plugin to modify Flat Rate carrier behavior
- **LoyaltyProgram**: uses plugins for minicart incentive and invoice point awarding
- **CreditMemo**: uses plugin on `Order::afterCancel()` to auto-create credit memo
- **Rma**: uses observer on `sales_order_place_after` to set return window

---

## Official Magento 2 Documentation

| Topic | Link |
|-------|------|
| Extend Magento | [developer.adobe.com/commerce/php/architecture/modules/extension-attributes/](https://developer.adobe.com/commerce/php/architecture/modules/extension-attributes/) |
| Events and Observers | [developer.adobe.com/commerce/php/architecture/event-driven-architecture/](https://developer.adobe.com/commerce/php/architecture/event-driven-architecture/) |
| Plugins (Interceptors) | [developer.adobe.com/commerce/php/architecture/modules/extension-attributes/plugins/](https://developer.adobe.com/commerce/php/development/components/plugins) |
| Dependency Injection | [developer.adobe.com/commerce/php/architecture/modules/di/](https://developer.adobe.com/commerce/php/architecture/modules/di/) |
| Module Configuration | [developer.adobe.com/commerce/php/architecture/modules/module-configuration/](https://developer.adobe.com/commerce/php/architecture/modules/module-configuration/) |
| Magento 2.4.8 PHP Docs | [developer.adobe.com/commerce/php/](https://developer.adobe.com/commerce/php/) |

---

## Sources

All Magento 2 Core references in this document come from the actual
Magento 2.4.8 source code in this repository under:

- `src/vendor/magento/framework/` — Magento framework
- `src/vendor/magento/module-*/` — Magento core modules

AlpineCommerce-specific implementations are referenced from:

- `src/app/code/AlpineCommerce/` — AlpineCommerce custom modules

*Last updated: 2026-09-07*
