# PHP OOP — Complete Guide for Beginners

> **Target audience**: developers who know basic PHP but are new to
> Object-Oriented Programming. Every concept is illustrated with Magento
> examples from the AlpineCommerce project.

---

## 1. What is OOP?

**OOP** = Object-Oriented Programming. Instead of writing scripts with
functions and global variables, you organize code around **objects** that
combine data (properties) and behavior (methods).

**Why OOP for Magento?**
- Magento 2 is 100% OOP. Every class, controller, model, block follows OOP.
- Understanding OOP is **mandatory** to read or write Magento code.
- OOP makes code reusable, testable, and maintainable.

---

## 2. Class and Object

### 2.1 Class = blueprint

```php
<?php
declare(strict_types=1);

namespace AlpineCommerce\Blog\Model;

class Post
{
}
```

A **class** is a template. It defines what a "Post" is.

### 2.2 Object = instance

```php
$post = new Post();
```

An **object** is a concrete instance created from the class. You can create
as many objects as you want from one class.

---

## 3. Properties (data)

```php
class Post
{
    public int $id;
    public string $title;
    public string $content;
    public ?int $categoryId; // nullable
}
```

Properties store the state of the object.

### 3.1 Visibility

| Keyword | Accessible from | Use in Magento |
|---------|----------------|----------------|
| `public` | Everywhere | API methods (`getTitle()`) |
| `protected` | Class + children | Internal helpers |
| `private` | Class only | Sensitive data, raw properties |

**Best practice**: make properties `private` and expose them via getters/setters.

```php
class Post
{
    private int $id;
    private string $title;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
```

---

## 4. Methods (behavior)

```php
class Post
{
    private string $title;
    private \DateTimeInterface $publishedAt;

    public function isPublished(): bool
    {
        return $this->publishedAt <= new \DateTime();
    }

    public function getExcerpt(int $maxLength = 100): string
    {
        return substr($this->title, 0, $maxLength) . '...';
    }
}
```

Methods are functions inside a class. They represent what the object **can do**.

---

## 5. Constructor

The constructor runs automatically when you create an object. It initializes
the object's state.

```php
class Post
{
    private string $title;
    private string $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }
}

$post = new Post('Hello', 'World'); // title = 'Hello', content = 'World'
```

### 5.1 In Magento: Dependency Injection in constructors

```php
class PostRepository
{
    private PostInterfaceFactory $postFactory;
    private ResourceModel\Post $resource;
    private PostCollectionFactory $collectionFactory;

    public function __construct(
        PostInterfaceFactory $postFactory,
        ResourceModel\Post $resource,
        PostCollectionFactory $collectionFactory
    ) {
        $this->postFactory = $postFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }
}
```

Magento automatically injects dependencies via the constructor. You **never**
call `new PostRepository()` yourself — Magento does it for you.

---

## 6. Inheritance

A class can **extend** another class and inherit its properties/methods.

```php
class Animal
{
    protected string $name;

    public function speak(): string
    {
        return '...';
    }
}

class Dog extends Animal
{
    public function speak(): string
    {
        return 'Woof!';
    }
}

$dog = new Dog();
echo $dog->speak(); // 'Woof!'
```

### 6.1 `parent::` keyword

```php
class Dog extends Animal
{
    public function speak(): string
    {
        return parent::speak() . ' Woof!'; // calls Animal::speak()
    }
}
```

### 6.2 Magento example

```php
// Magento core
abstract class AbstractAction extends \Magento\Framework\App\Action\Action
{
    // ...
}

// AlpineCommerce StorePickup
class Index extends AbstractAction
{
    public function execute(): void
    {
        // inherits $this->resultFactory, $this->_redirect(), etc.
    }
}
```

---

## 7. Abstract classes

An **abstract class** cannot be instantiated. It forces child classes to
implement certain methods.

```php
abstract class PaymentMethod
{
    abstract public function pay(float $amount): string;

    public function refund(float $amount): string
    {
        return 'Refunded ' . $amount;
    }
}

class CreditCard extends PaymentMethod
{
    public function pay(float $amount): string
    {
        return 'Paid ' . $amount . ' by card';
    }
}

// $payment = new PaymentMethod(); // Fatal error
$card = new CreditCard();
echo $card->pay(100); // 'Paid 100 by card'
```

---

## 8. Interfaces

An **interface** is a contract: it defines methods that a class **must**
implement, but provides no implementation.

```php
interface LoggerInterface
{
    public function log(string $message): void;
}

class FileLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        file_put_contents('/tmp/log.txt', $message);
    }
}

class DatabaseLogger implements LoggerInterface
{
    public function log(string $message): void
    {
        // save to DB
    }
}
```

### 8.1 Why interfaces in Magento?

Interfaces allow **swapping implementations** without changing the code that
uses them.

```php
// AlpineCommerce Blog
class PostRepository
{
    public function __construct(
        private PostRepositoryInterface $repository // interface
    ) {}

    public function save(PostInterface $post): PostInterface
    {
        return $this->repository->save($post);
    }
}
```

If tomorrow you need a `CachedPostRepository`, you just create a class that
implements `PostRepositoryInterface` — no change in `PostRepository`.

---

## 9. Traits

A **trait** is a way to reuse methods across unrelated classes (since PHP
does not allow multiple inheritance).

```php
trait Loggable
{
    public function log(string $message): void
    {
        // write to log
    }
}

class Post
{
    use Loggable;
}

class Category
{
    use Loggable;
}

$post = new Post();
$post->log('Post created'); // works

$category = new Category();
$category->log('Category created'); // works
```

---

## 10. Namespaces

Namespaces prevent name collisions. They are like folders for classes.

```php
// File: src/app/code/AlpineCommerce/Blog/Model/Post.php
namespace AlpineCommerce\Blog\Model;

class Post { }

// File: src/app/code/AlpineCommerce/Faq/Model/Post.php
namespace AlpineCommerce\Faq\Model;

class Post { }
```

Both classes are named `Post`, but their full names are:
- `AlpineCommerce\Blog\Model\Post`
- `AlpineCommerce\Faq\Model\Post`

No conflict!

### 10.1 `use` keyword

```php
use AlpineCommerce\Blog\Model\Post;

$post = new Post(); // same as new \AlpineCommerce\Blog\Model\Post()
```

---

## 11. Autoloading (PSR-4)

Writing `require 'Post.php';` everywhere is outdated. **Autoloading** loads
classes automatically when they are first used.

Magento uses **PSR-4** autoloading. The mapping is defined in `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "AlpineCommerce\\Blog\\": "src/app/code/AlpineCommerce/Blog/"
        }
    }
}
```

This means:
- Class `AlpineCommerce\Blog\Model\Post`
- File: `src/app/code/AlpineCommerce/Blog/Model/Post.php`

When PHP encounters `new Post()` (with the proper `use` statement), it
automatically loads the file. No `require` needed.

> **Important**: after modifying `composer.json`, run:
> `composer dump-autoload`

---

## 12. Static methods and properties

A **static** method/property belongs to the **class itself**, not to an
instance.

```php
class Counter
{
    private static int $count = 0;

    public static function increment(): void
    {
        self::$count++;
    }

    public static function getCount(): int
    {
        return self::$count;
    }
}

Counter::increment();
echo Counter::getCount(); // 1
```

### 12.1 Magento example

```php
$state = \Magento\Framework\App\State::getInstance();
$state->setAreaCode('adminhtml');
```

`getInstance()` is a static method. It does not require `new State()`.

> **Best practice**: avoid overusing static. Use dependency injection
> whenever possible.

---

## 13. Type hints and return types

PHP 7.4+/8.x allows strict typing:

```php
declare(strict_types=1);

class PostRepository
{
    public function getById(int $id): ?PostInterface
    {
        // ...
    }

    public function save(PostInterface $post): PostInterface
    {
        // ...
    }
}
```

- `int $id` → argument must be an integer
- `?PostInterface` → return type can be `PostInterface` or `null`
- `: PostInterface` → return type must be `PostInterface`

Magento **requires** `declare(strict_types=1)` at the top of every PHP file.

---

## 14. Dependency Injection (DI)

### 14.1 The concept

Instead of creating dependencies inside a class, you **receive them from
the outside**. This is called **Inversion of Control (IoC)**.

```php
// ❌ Bad: the class creates its own dependency
class PostRepository
{
    public function __construct()
    {
        $this->connection = new \Magento\Framework\App\ResourceConnection();
    }
}

// ✅ Good: the dependency is injected
class PostRepository
{
    public function __construct(
        \Magento\Framework\App\ResourceConnection $connection
    ) {
        $this->connection = $connection;
    }
}
```

### 14.2 Why DI?

- **Testability**: you can inject a mock during unit tests
- **Flexibility**: swap implementations without changing the class
- **Magento does it automatically**: the DI container builds the object graph

### 14.3 Magento DI example

```php
// AlpineCommerce Blog Controller
class Index extends \Magento\Framework\App\Action\Action
{
    private PostRepositoryInterface $postRepository;
    private ResultFactory $resultFactory;

    public function __construct(
        Context $context,
        PostRepositoryInterface $postRepository,
        ResultFactory $resultFactory
    ) {
        parent::__construct($context);
        $this->postRepository = $postRepository;
        $this->resultFactory = $resultFactory;
    }
}
```

Magento reads the type hints in the constructor and automatically provides
the correct implementation.

---

## 15. Exceptions

When something goes wrong, throw an **exception**:

```php
use Magento\Framework\Exception\NoSuchEntityException;

class PostRepository
{
    public function getById(int $id): PostInterface
    {
        $post = $this->connection->fetchRow(...);
        if (!$post) {
            throw new NoSuchEntityException(
                __('Post with ID "%1" does not exist.', $id)
            );
        }
        return $post;
    }
}
```

The caller **must** catch the exception:

```php
try {
    $post = $postRepository->getById(42);
} catch (NoSuchEntityException $e) {
    echo $e->getMessage(); // "Post with ID '42' does not exist."
}
```

---

## 16. Quick reference — Magento file conventions

| Concept | Example in AlpineCommerce |
|---------|---------------------------|
| **Interface** | `Api/Data/PostInterface.php` |
| **Implementation** | `Model/Post.php` (implements `PostInterface`) |
| **Repository** | `Model/PostRepository.php` (implements `PostRepositoryInterface`) |
| **ResourceModel** | `Model/ResourceModel/Post.php` (SQL queries) |
| **Collection** | `Model/ResourceModel/Post/Collection.php` |
| **Block** | `Block/PostList.php` (extends `Template`) |
| **Controller** | `Controller/Index/Index.php` (extends `Action`) |
| **Observer** | `Observer/SavePostAfter.php` |
| **Plugin** | `Plugin/Post/Slugify.php` |
| **UI DataProvider** | `Ui/DataProvider/PostFormDataProvider.php` |

---

## 17. Summary

| OOP Concept | Magento Equivalent |
|-------------|-------------------|
| Class | Every PHP file in `Model/`, `Block/`, `Controller/` |
| Interface | `Api/Data/*Interface.php` |
| Abstract class | `Model/*Abstract.php` |
| Trait | Rare, but exists in core |
| Namespace | Folder structure (`AlpineCommerce\Blog\Model`) |
| Autoloading | PSR-4 via `composer.json` |
| DI constructor | Every class in Magento |
| Visibility | `private` properties + `public` getters/setters |
| Exception | `Magento\Framework\Exception\*` |

---

## 18. Next steps

- Practice: create a simple class with properties, methods, constructor
- Read `src/app/code/AlpineCommerce/Blog/Model/Post.php` (simple entity)
- Read `src/app/code/AlpineCommerce/Blog/Controller/Index/Index.php` (controller)
- Continue with `docs/prerequisites/git-github.md`

---

*Last updated: 2026-08-11.*
