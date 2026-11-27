# Magento 2 from Scratch — Preferences

> **Series**: [Learning Path](../README.md) → Phase 3, Article 3 of 3
> **Previous**: [`07-plugins.md`](07-plugins.md) — Plugins
> **Next**: [`09-rest-graphql.md`](09-rest-graphql.md) — REST & GraphQL
> **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)

---

A **preference** tells Magento's dependency-injection container: "Whenever
someone asks for this interface, give them this concrete class instead."

This is how you swap an implementation without touching any calling code.

---

## 1. The DI resolution chain

When Magento sees a type hint like:

```php
public function __construct(
    \Magento\Catalog\Api\ProductRepositoryInterface $repository
)
```

The Object Manager looks in `di.xml` for a **preference** that maps
`ProductRepositoryInterface` → `ProductRepository`. If none is found, it
falls back to the default Magento implementation.

```
Type hint: ProductRepositoryInterface
        ↓
di.xml preference lookup
        ↓
Concrete class returned
```

---

## 2. Declaring a preference

```xml
<!-- etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <preference for="Magento\Catalog\Api\ProductRepositoryInterface"
                type="AlpineCommerce\StoreSetup\Model\ProductRepository"/>
</config>
```

- **`for`** — the interface (or abstract class) you want to replace
- **`type`** — the concrete class to use instead

When any code asks for `ProductRepositoryInterface`, Magento gives it
`AlpineCommerce\StoreSetup\Model\ProductRepository` instead.

---

## 3. When to use preferences

| Use case | Mechanism |
|----------|-----------|
| Change what a method **returns** | Plugin (`after`) |
| Change method **arguments** before it runs | Plugin (`before`) |
| **Replace** the entire implementation | Preference |
| **Extend** a class with new methods | Plugin or new class |

> **Rule of thumb**: use **preferences** sparingly — they are global. Only one
> preference per interface (the last module to declare one wins). Use **plugins**
> for most modifications.

---

## 4. Preference vs plugin — example

### Using a plugin (recommended)

```xml
<type name="Magento\Catalog\Api\ProductRepositoryInterface">
    <plugin name="log_save"
            type="AlpineCommerce\StoreSetup\Plugin\Product\BeforeSave"
            sortOrder="10"/>
</type>
```

```php
class BeforeSave
{
    public function beforeSave(
        ProductRepositoryInterface $subject,
        ProductInterface $product
    ): void {
        $this->logger->info('Product about to be saved: ' . $product->getSku());
    }
}
```

### Using a preference (when you need full replacement)

```php
class ProductRepository implements ProductRepositoryInterface
{
    public function save(ProductInterface $product): ProductInterface
    {
        $this->logger->info('About to save: ' . $product->getSku());
        return $this->subject->save($product);  // calls the original
    }
}
```

> **Note**: With a preference, you re-implement the interface. You must delegate
> to the original logic yourself (often via `$this->subject` injected via the
> constructor). Plugins handle this automatically.

---

## 5. Preference conflicts

If two modules declare a preference for the same interface:

```xml
<!-- Module A -->
<preference for="SomeInterface" type="ModuleA\Impl"/>

<!-- Module B -->
<preference for="SomeInterface" type="ModuleB\Impl"/>
```

Magento picks **one** — the one whose module loads **last** (highest module
sort order). This is silent — no error, no warning. Other modules' preference
is simply ignored.

---

## 6. What you should remember

1. **Preferences** map an interface to a concrete class in `di.xml`.
2. They are **global** — only one preference per interface wins.
3. Use **plugins** for modifications; use **preferences** only when you need
   to completely replace an implementation.
4. With a preference, you must delegate to the original logic yourself.
5. Preference conflicts are **silent** — the last-declared module wins.

---

## 7. Where to go next

- **Phase 4**: [`09-rest-graphql.md`](09-rest-graphql.md) — REST & GraphQL APIs
- **Previous**: [Plugins](07-plugins.md)
- **Deep dive**: [`../magento-events-observers-plugins.md`](../magento-events-observers-plugins.md)
  — the full comparison table and decision tree.

---

*Sources: `src/vendor/magento/framework/ObjectManager/etc/config.xsd`,
`src/app/code/AlpineCommerce/StoreSetup/etc/di.xml`,
`src/vendor/magento/module-catalog/etc/di.xml`*
