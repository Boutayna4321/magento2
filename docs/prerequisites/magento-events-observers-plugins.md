# Magento Events, Observers & Plugins

Magento 2 provides three main ways to extend behavior without modifying core code.

## Extension methods (least to most intrusive)

| Method | How it works | Use case |
|---|---|---|
| **Plugin** | Intercepts a method call (before/around/after) | Modify return value or behavior of a specific method |
| **Observer** | Reacts to a business event | Run logic when something happens (order placed, customer saved) |
| **Preference** | Replaces a class entirely | Last resort — replace entire implementation |

## Plugins (Interceptors)

Plugins intercept public methods:

```xml
<type name="Magento\Sales\Model\Order">
    <plugin name="my_plugin" type="Vendor\Module\Plugin\OrderPlugin" sortOrder="10"/>
</type>
```

```php
class OrderPlugin
{
    public function beforeCancel(Order $subject): array
    {
        // Before the method runs
    }

    public function afterCancel(Order $subject, bool $result): bool
    {
        // After the method runs
    }

    public function aroundCancel(Order $subject, \Closure $proceed): bool
    {
        // Replace the method entirely
    }
}
```

## Observers

Observers listen to events:

```xml
<event name="sales_order_place_after">
    <observer name="my_observer" instance="Vendor\Module\Observer\MyObserver"/>
</event>
```

```php
class MyObserver implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $order = $observer->getEvent()->getOrder();
    }
}
```

## Where to go next

- **Full reference**: [`../magento2/magento-events-observers-plugins.md`](../magento2/magento-events-observers-plugins.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: all Magento 2 customization*
