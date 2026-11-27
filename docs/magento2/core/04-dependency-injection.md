# Magento 2 from Scratch — Dependency Injection & Service Contracts

> **Series**: [Learning Path](../README.md) → Phase 1, Article 4 of 5
> **Previous**: [`03-request-lifecycle.md`](03-request-lifecycle.md) — Request lifecycle
> **Next**: [`05-database-model.md`](05-database-model.md) — EAV vs Flat
> **Deep dive**: [`../magento-components.md`](../magento-components.md#8-magento-design-patterns)

---

In the request lifecycle, the Controller called a **Repository** to get data.
How did the Controller *get* the Repository? Through **dependency injection**.

This article explains how Magento creates objects, wires them together, and
why you should always type-hint an **interface**, not a class.

---

## 1. The Problem DI Solves

Without dependency injection, you would write:

```php
class Index
{
    public function execute()
    {
        $repo = new \AlpineCommerce\Blog\Model\BlogPostRepository(); // tight coupling
        $posts = $repo->getList();
    }
}
```

Problems:
- You cannot swap the implementation (e.g., add caching) without changing `Index`.
- You cannot unit-test `Index` without connecting to the database.
- If `BlogPostRepository` itself needs a database connection, you recurse into
  the same problem.

**Dependency injection** solves this: external dependencies are **injected**
into the class, usually via the constructor.

---

## 2. The Object Manager (Magento's DI container)

Magento never uses `new` directly. Instead, everything goes through the
**Object Manager** — a service container that reads `di.xml` config and
automatically wires dependencies.

```php
// src/vendor/magento/framework/ObjectManager/ObjectManager.php
public function create($type, array $arguments = [])
{
    $obj = $this->_factory->create($type, $arguments);
    $obj = $this->_interceptorChain->addObserver($obj); // adds plugins
    return $obj;
}
```

You almost never call the Object Manager directly. Instead, type-hint in the
constructor, and Magento resolves it for you:

```php
class Index extends Action
{
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \AlpineCommerce\Blog\Api\BlogPostRepositoryInterface $repository
    ) {
        parent::__construct($context);
        $this->repository = $repository;
    }
}
```

Magento sees the type hint `BlogPostRepositoryInterface` and looks in `di.xml`
for a **preference** (which concrete class to use).

---

## 3. Service Contracts — interfaces first, always

A **Service Contract** is a public interface for business logic. In Magento,
every entity follows this pattern:

```
Api/Data/        ← Data interfaces (property getters/setters)
Api/             ← Repository interfaces (business operations)
Model/           ← Entity models (implement Api/Data interfaces)
Model/ResourceModel/  ← SQL queries
```

```php
// Api/BlogPostRepositoryInterface.php
interface BlogPostRepositoryInterface
{
    public function save(BlogPostInterface $post): BlogPostInterface;
    public function getById(int $id): BlogPostInterface;
    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;
    public function delete(BlogPostInterface $post): bool;
}
```

**Always** type-hint the **interface** (`Api/...Interface`), never the
concrete class (`Model/...`). This way, if you swap the implementation, no
calling code breaks.

---

## 4. Preferences — mapping interface to implementation

A **preference** tells Magento: "when someone asks for this interface, give
them this class":

```xml
<!-- src/app/code/AlpineCommerce/Blog/etc/di.xml -->
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <preference for="AlpineCommerce\Blog\Api\BlogPostRepositoryInterface"
                type="AlpineCommerce\Blog\Model\BlogPostRepository"/>
</config>
```

Only **one** preference per interface. If two modules declare one, Magento
picks the one with the **higher sort order** in module loading.

---

## 5. Virtual types

Sometimes you need a pre-configured version of a class. A **virtual type**
lets you define it in XML without writing a new class:

```xml
<virtualType name="BlogPostSearchResults" type="AlpineCommerce\Blog\Model\ResourceModel\BlogPost\Collection">
    <arguments>
        <argument name="searchResultsFactory" xsi:type="object">
            AlpineCommerce\Blog\Model\BlogPostSearchResultsFactory
        </argument>
    </arguments>
</virtualType>
```

Most of the time you won't write these — Magento core uses them internally.

---

## 6. Plugins (Interceptors) — modifying behavior

A **plugin** wraps a public method. It is declared in `di.xml`:

```xml
<type name="Magento\Catalog\Api\ProductRepositoryInterface">
    <plugin name="log_product_save"
            type="AlpineCommerce\StoreSetup\Plugin\Product\BeforeSave"
            sortOrder="10"/>
</type>
```

The plugin class has methods named `before{Method}`, `after{Method}`, or
`around{Method}`:

```php
class BeforeSave
{
    public function beforeSave(ProductRepositoryInterface $subject, ProductInterface $product): void
    {
        // runs BEFORE the original method
        $this->logger->info('Product about to be saved: ' . $product->getSku());
    }
}
```

> Plugins only work on `public` methods of non-`final` classes. They cannot
> intercept `private`, `protected`, or `static` methods.

We will cover events, observers, and plugins in detail in
[Phase 3](../README.md#phase-3--extension-points).

---

## 7. Factories — auto-generated constructors

Magento can auto-generate a **Factory** class for any model that doesn't have
a complex constructor. You just declare it in `di.xml`:

```xml
<virtualType name="AlpineCommerce\Blog\Model\BlogPostFactory"
             type="Magento\Framework\ObjectManager\Factory\Dynamic\Developer">
    <arguments>
        <argument name="instance" xsi:type="string">
            AlpineCommerce\Blog\Model\BlogPost
        </argument>
    </arguments>
</virtualType>
```

Then inject and use it:

```php
public function __construct(
    \AlpineCommerce\Blog\Model\BlogPostFactory $factory
) {
    $this->factory = $factory;
}

public function create(): BlogPost
{
    return $this->factory->create();
}
```

---

## 8. What you should remember

1. **Never use `new`** directly — let the Object Manager create objects via
   dependency injection.
2. **Always type-hint interfaces** (`Api/...Interface`), never concrete classes.
3. **Preferences** in `di.xml` map interfaces to implementations.
4. **Plugins** wrap public methods to add behavior without modifying the class.
5. **Factories** are auto-generated helpers for creating entity objects.
6. The Object Manager is the **only** place objects are created; everything
   else receives them through the constructor.

---

## 9. Where to go next

- **Previous**: [Request Lifecycle](03-request-lifecycle.md)
- **Article 5**: [Database Model (EAV vs Flat)](05-database-model.md)
- **Phase 3**: [Events & Observers](06-events-observers.md),
  [Plugins](07-plugins.md), [Preferences](08-preferences.md)
- **Deep dive**: [`../magento-components.md`](../magento-components.md#8-magento-design-patterns)
  — the full design pattern catalog.

---

*Sources: `src/vendor/magento/framework/ObjectManager/ObjectManager.php`,
`src/vendor/magento/framework/Interception/Interceptor.php`,
`src/vendor/magento/framework/App/FrontController.php`,
`src/app/code/AlpineCommerce/Blog/etc/di.xml`*
