# Magento Security

Magento 2 has built-in security features that every developer must understand.

## Key security concepts

### Form Keys

CSRF protection for POST requests:

```php
<?php echo $block->getFormKey(); ?>
```

### ACL (Access Control List)

Role-based access control for admin users.

### Input Validation

Always validate and sanitize user input:

```php
$value = $this->string->cleanString($input);
```

### Output Escaping

Escape output in templates:

```php
<?= $block->escapeHtml($value) ?>
<?= $block->escapeUrl($url) ?>
```

### XSS Prevention

Never output raw user input. Use:
- `escapeHtml()` for HTML
- `escapeJs()` for JavaScript
- `escapeUrl()` for URLs

### CSRF Prevention

All POST forms must include form keys. AJAX requests should use headers:

```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```

### Password Hashing

Magento uses bcrypt (or Argon2) — never store plain passwords.

### Encryption

Sensitive data is encrypted using the store's encryption key.

## Security best practices

1. Never trust user input
2. Always escape output
3. Use ACL for admin routes
4. Validate form keys on POST
5. Keep Magento updated
6. Use HTTPS in production
7. Restrict admin URL (`admin/url/custom_path`)

## Where to go next

- **Full reference**: [`../magento2/magento-security.md`](../magento2/magento-security.md)
- **Engineering guide**: [`../ENGINEERING_GUIDE.md`](../ENGINEERING_GUIDE.md)

---

*Prerequisite for: secure module development, production deployment*
