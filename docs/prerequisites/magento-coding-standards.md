# Magento Coding Standards

Magento 2 follows **PSR-12** coding standards with additional Magento-specific rules.

## PSR-12 basics

| Rule | Example |
|---|---|
| **Namespace** | `Vendor\Module` |
| **Class name** | PascalCase (`MyClass`) |
| **Method name** | camelCase (`myMethod()`) |
| **Constant** | UPPER_SNAKE_CASE (`MY_CONSTANT`) |
| **Property** | camelCase (`$myProperty`) |
| **Indent** | 4 spaces |
| **Line length** | 120 characters max |
| **Braces** | K&R style |

## Magento-specific conventions

### Module naming

```
app/code/AlpineCommerce/AutoInvoice/
├── registration.php          # module registration
├── etc/
│   ├── module.xml            # module declaration
│   ├── config.xml            # default config values
│   ├── di.xml                # dependency injection
│   ├── events.xml            # event observers
│   └── system.xml            # admin config
├── Model/                    # business logic
├── Plugin/                   # interceptors
├── Observer/                  # event handlers
├── Service/                   # service contracts
└── Api/                       # service contract interfaces
```

### Service contracts

Every business capability should have an interface in `Api/`:

```php
namespace AlpineCommerce\AutoInvoice\Api;

interface AutoInvoiceInterface
{
    public function processOrder(int $orderId): \AlpineCommerce\AutoInvoice\Api\Data\InvoiceResultInterface;
}
```

### Dependency injection

Use constructor injection, never use object manager directly:

```php
public function __construct(
    private readonly OrderRepositoryInterface $orderRepository,
    private readonly LoggerInterface $logger
) {}
```

## Where to go next

- **Full reference**: [`../magento2/magento-coding-standards.md`](../magento2/magento-coding-standards.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: writing clean, maintainable Magento code*
