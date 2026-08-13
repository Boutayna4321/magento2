# Magento 2 — Testing

> **Objective**: learn how to test Magento 2 code. This guide covers unit
> tests, integration tests, and API functional tests, with practical
> AlpineCommerce examples.

---

## 1. Why Test?

Testing ensures that:
- Code works as expected
- Changes don't break existing functionality
- Bugs are caught early
- Code is maintainable and refactorable

**In Magento**, testing is critical because:
- Magento is complex (EAV, DI, events, plugins)
- Changes can have unintended side effects across modules
- Production bugs are costly

---

## 2. Types of Tests in Magento

### 2.1 Overview

| Type | Scope | Speed | Database | Example |
|------|-------|-------|----------|---------|
| **Unit** | Single class | Fast (< 1s) | No | Test `PostRepository::getTitle()` |
| **Integration** | Multiple classes | Medium (1-10s) | Yes (test DB) | Test repository with real DB |
| **API Functional** | REST/GraphQL API | Slow (10-60s) | Yes | Test `/rest/V1/blog/posts` |
| **Static** | Code analysis | Fast | No | PHPStan, PHP_CodeSniffer |

### 2.2 Magento's test directory structure

```
src/dev/tests/
├── unit/                    ← Unit tests
│   └── framework/tests/unit/
├── integration/             ← Integration tests
│   └── tests/
├── api-functional/          ← API functional tests
│   ├── test-rest/
│   ├── test-graphql/
│   └── test-soap/
└── static/                  ← Static tests (PHPStan, etc.)
    └── tests/
```

---

## 3. Unit Tests

### 3.1 What is a unit test?

A **unit test** tests a single class in isolation. Dependencies are mocked
(replaced with fake objects).

### 3.2 Example: Testing a simple class

```php
// Model/VipLevelCalculator.php
class VipLevelCalculator
{
    public function calculate(float $lifetimeSpent): string
    {
        if ($lifetimeSpent >= 1000) {
            return 'gold';
        }
        if ($lifetimeSpent >= 500) {
            return 'silver';
        }
        if ($lifetimeSpent >= 100) {
            return 'bronze';
        }
        return 'none';
    }
}
```

```php
// Test/Unit/Model/VipLevelCalculatorTest.php
class VipLevelCalculatorTest extends \PHPUnit\Framework\TestCase
{
    private VipLevelCalculator $calculator;
    
    protected function setUp(): void
    {
        $this->calculator = new VipLevelCalculator();
    }
    
    public function testCalculateReturnsNoneForLowSpending(): void
    {
        $this->assertEquals('none', $this->calculator->calculate(50));
    }
    
    public function testCalculateReturnsBronzeFor100(): void
    {
        $this->assertEquals('bronze', $this->calculator->calculate(100));
    }
    
    public function testCalculateReturnsSilverFor500(): void
    {
        $this->assertEquals('silver', $this->calculator->calculate(500));
    }
    
    public function testCalculateReturnsGoldFor1000(): void
    {
        $this->assertEquals('gold', $this->calculator->calculate(1000));
    }
    
    public function testCalculateReturnsGoldForHighSpending(): void
    {
        $this->assertEquals('gold', $this->calculator->calculate(5000));
    }
}
```

### 3.3 Running unit tests

```bash
# Run all unit tests
vendor/bin/phpunit

# Run a specific test file
vendor/bin/phpunit tests/Unit/Model/VipLevelCalculatorTest.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### 3.4 Unit test best practices

| Practice | Example |
|----------|---------|
| One assertion per test | `testCalculateReturnsBronzeFor100` |
| Descriptive test names | `testCalculateReturnsNoneForLowSpending` |
| Test edge cases | 0, 99.99, 100, 499.99, 500 |
| Test exceptions | `$this->expectException(InvalidArgumentException::class)` |
| Use `setUp()` for common initialization | Create object once per test |

---

## 4. Integration Tests

### 4.1 What is an integration test?

An **integration test** tests multiple classes working together, often with
a real database connection.

### 4.2 Example: Testing a repository

```php
// Test/Integration/Model/PostRepositoryTest.php
class PostRepositoryTest extends \Magento\TestFramework\TestCase\AbstractTestCase
{
    private PostRepositoryInterface $postRepository;
    private ResourceConnection $connection;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->postRepository = ObjectManager::getInstance()
            ->get(PostRepositoryInterface::class);
        $this->connection = ObjectManager::getInstance()
            ->get(ResourceConnection::class);
    }
    
    public function testSaveAndLoadPost(): void
    {
        // Create
        $post = $this->postFactory->create();
        $post->setTitle('Test Post');
        $post->setContent('Test Content');
        $saved = $this->postRepository->save($post);
        
        $this->assertNotNull($saved->getId());
        
        // Load
        $loaded = $this->postRepository->getById($saved->getId());
        $this->assertEquals('Test Post', $loaded->getTitle());
        $this->assertEquals('Test Content', $loaded->getContent());
        
        // Cleanup
        $this->postRepository->delete($loaded);
    }
}
```

### 4.3 Running integration tests

```bash
# Run all integration tests
vendor/bin/phpunit dev/tests/integration/tests/

# Run with a specific database
vendor/bin/phpunit \
    --bootstrap dev/tests/integration/bootstrap.php \
    --phpunit dev/tests/integration/phpunit.xml \
    tests/Integration/Model/PostRepositoryTest.php
```

### 4.4 Integration test best practices

| Practice | Why |
|----------|-----|
| Clean up test data | Don't pollute the database |
| Use fixtures | Set up known state before test |
| Test real interactions | Verify classes work together |
| Roll back transactions | Keep DB clean between tests |

---

## 5. API Functional Tests

### 5.1 What is an API functional test?

An **API functional test** tests REST or GraphQL endpoints end-to-end.

### 5.2 Example: Testing REST API

```php
// Test/Api/PostRepositoryTest.php (REST functional test)
class PostRepositoryTest extends \Magento\TestFramework\TestCase\WebapiAbstract
{
    public function testCreatePost(): void
    {
        $postData = [
            'title' => 'API Test Post',
            'content' => 'Created via REST API'
        ];
        
        $response = $this->webapiCall(
            '/rest/V1/alphacommerce/blog/posts',
            'POST',
            [],
            $postData
        );
        
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals('API Test Post', $response['title']);
        
        // Cleanup
        $this->webapiCall(
            '/rest/V1/alphacommerce/blog/posts/' . $response['id'],
            'DELETE'
        );
    }
}
```

### 5.3 Running API functional tests

```bash
# REST tests
vendor/bin/phpunit dev/tests/api-functional/

# GraphQL tests
vendor/bin/phpunit dev/tests/api-functional/test-graphql/

# Specific test
vendor/bin/phpunit dev/tests/api-functional/tests/rest/PostRepositoryTest.php
```

---

## 6. Test Configuration

### 6.1 phpunit.xml

```xml
<!-- phpunit.xml -->
<?xml version="1.0"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>tests/Unit/</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>tests/Integration/</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 6.2 Test directory in a module

```
AlpineCommerce/Blog/
├── Test/
│   ├── Unit/
│   │   ├── Model/
│   │   │   └── VipLevelCalculatorTest.php
│   │   └── Block/
│   │       └── PostListTest.php
│   ├── Integration/
│   │   ├── Model/
│   │   │   └── PostRepositoryTest.php
│   │   └── Controller/
│   │       └── IndexTest.php
│   └── Api/
│       └── Rest/
│           └── PostRepositoryTest.php
```

---

## 7. Mocking Dependencies

### 7.1 Why mock?

When testing a class, you don't want to use real dependencies (database,
API calls, etc.). Mocks are fake objects that simulate real behavior.

### 7.2 Example with PHPUnit mocks

```php
// Testing StorePickup plugin with mocked carrier
class FilterFlatRateTest extends \PHPUnit\Framework\TestCase
{
    public function testBeforeCollectRatesFiltersFreeShipping(): void
    {
        // Create mock
        $carrierMock = $this->createMock(Carrier\FlatRate::class);
        $requestMock = $this->createMock(RateRequest::class);
        
        // Set expectations
        $requestMock->method('getValue')
            ->with('free_shipping')
            ->willReturn(60);
        
        // Create plugin and test
        $plugin = new FilterFlatRate();
        $result = $plugin->beforeCollectRates($carrierMock, $requestMock);
        
        // Verify
        $this->assertNotNull($result);
    }
}
```

---

## 8. Testing in AlpineCommerce

### 8.1 Current state

| Module | Unit Tests | Integration Tests | API Tests |
|--------|-----------|-------------------|-----------|
| Blog | ❌ | ❌ | ❌ |
| Faq | ❌ | ❌ | ❌ |
| StorePickup | ❌ | ❌ | ❌ |
| LoyaltyProgram | ❌ | ❌ | ❌ |
| CustomerCare | ❌ | ❌ | ❌ |

**AlpineCommerce has no tests yet** (BACKLOG B-07).

### 8.2 Recommended test coverage

| Component | Unit | Integration | API |
|-----------|------|-------------|-----|
| VipLevelCalculator | ✅ | — | — |
| PostRepository | — | ✅ | ✅ |
| REST endpoints | — | — | ✅ |
| Plugins | ✅ | — | — |
| Observers | ✅ | — | — |
| Data Patches | — | ✅ | — |

---

## 9. Running Tests in AlpineCommerce

### 9.1 Prerequisites

```bash
# Install dev dependencies
composer install --with-dev

# Set up test database (separate from main)
php bin/magento setup:install \
    --db-host=localhost \
    --db-name=magento_test \
    --db-user=root \
    --db-password=root123 \
    --backend-frontname=admin \
    --admin-firstname=Admin \
    --admin-lastname=User \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=admin123
```

### 9.2 Run all tests

```bash
# Unit tests
vendor/bin/phpunit tests/Unit/

# Integration tests
vendor/bin/phpunit dev/tests/integration/tests/

# API tests
vendor/bin/phpunit dev/tests/api-functional/
```

### 9.3 Run tests with coverage

```bash
vendor/bin/phpunit --coverage-html coverage/
# Open coverage/index.html in browser
```

---

## 10. Test-Driven Development (TDD)

### 10.1 The TDD cycle

```
1. Write a failing test
2. Write minimal code to make it pass
3. Refactor
4. Repeat
```

### 10.2 Example: TDD for VipLevelCalculator

```php
// Step 1: Write failing test
public function testCalculateReturnsNoneForLowSpending(): void
{
    $this->assertEquals('none', $this->calculator->calculate(50));
}

// Step 2: Run test → FAILS (class doesn't exist yet)

// Step 3: Write minimal code
class VipLevelCalculator
{
    public function calculate(float $lifetimeSpent): string
    {
        return 'none'; // Minimal implementation
    }
}

// Step 4: Run test → PASSES

// Step 5: Add more tests, implement more logic, refactor
```

---

## 11. CI and Tests

### 11.1 Adding tests to GitHub Actions

```yaml
# .github/workflows/ci.yml
jobs:
  unit-tests:
    name: Unit Tests
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --with-dev
      - run: vendor/bin/phpunit tests/Unit/
```

### 11.2 Test coverage as CI gate

```yaml
      - name: Run unit tests with coverage
        run: vendor/bin/phpunit --coverage-clover coverage.xml
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

---

## 12. Common Testing Issues

### 12.1 "Class not found" in tests

**Cause**: autoloader not updated.

**Solution**:
```bash
composer dump-autoload
```

### 12.2 Tests fail with "Area code is not set"

**Cause**: integration tests need area code.

**Solution**:
```php
protected function setUp(): void
{
    parent::setUp();
    $this->getObjectManager()->get(\Magento\Framework\App\State::class)
        ->setAreaCode('frontend');
}
```

### 12.3 Database connection errors

**Cause**: test database not configured.

**Solution**:
```bash
# Create test database
mysql -u root -p -e "CREATE DATABASE magento_test;"

# Run integration tests with correct DB
vendor/bin/phpunit --bootstrap dev/tests/integration/bootstrap.php \
    dev/tests/integration/phpunit.xml
```

---

## 13. Summary

| Test Type | Scope | Speed | Database | When to use |
|-----------|-------|-------|----------|-------------|
| **Unit** | Single class | Fast | No | Business logic, calculators, formatters |
| **Integration** | Multiple classes | Medium | Yes | Repositories, data patches, plugins |
| **API Functional** | REST/GraphQL | Slow | Yes | Endpoints, authentication, contracts |

### Key takeaways

1. **Unit tests** are fast and test isolated logic
2. **Integration tests** verify classes work together with real DB
3. **API tests** verify the entire API stack
4. **Mock dependencies** in unit tests to keep them fast
5. **Run tests in CI** to catch bugs before merge
6. **AlpineCommerce needs tests** (BACKLOG B-07) — start with `VipLevelCalculator`

### Recommended AlpineCommerce test plan

| Priority | Component | Type | Effort |
|----------|-----------|------|--------|
| High | `VipLevelCalculator` | Unit | Low |
| High | `PostRepository` | Integration | Medium |
| High | REST endpoints | API Functional | Medium |
| Medium | Plugins (StorePickup, Loyalty, CustomerCare, StoreSetup) | Unit | Low |
| Low | Full checkout flow | API Functional | High |

---

*Last updated: 2026-08-11.*
