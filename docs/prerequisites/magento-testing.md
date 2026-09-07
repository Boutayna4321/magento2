# Magento Testing

Magento 2 provides multiple testing frameworks.

## Test types

| Type | Framework | Purpose |
|---|---|---|
| **Unit** | PHPUnit | Test single classes in isolation |
| **Integration** | PHPUnit + Magento test framework | Test with database, modules loaded |
| **Functional** | Magento Functional Testing Framework (MFTF) | Test UI flows |
| **API** | PHPUnit + Web API | Test REST/GraphQL endpoints |

## Running tests

```bash
# Unit tests
vendor/bin/phpunit tests/unit/

# Integration tests
vendor/bin/phpunit tests/integration/

# With specific database
vendor/bin/phpunit --db-connection="default" --db-name="magento_test"
```

## Test structure

```
tests/
├── unit/          # Unit tests (no Magento bootstrap)
│   └── Vendor/Module/
└── integration/   # Integration tests (Magento bootstrap)
    └── Vendor/Module/
```

## Where to go next

- **Full reference**: [`../magento2/magento-testing.md`](../magento2/magento-testing.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: quality assurance, CI/CD, module validation*
