# Magento 2 — Security Basics

> **Objective**: learn the essential security mechanisms in Magento 2:
> form keys, ACL, validation, XSS prevention, CSRF protection, and safe
> coding practices. Security is not optional — it protects the store,
> customers, and developers.

---

## 1. Security Principles in Magento

Magentento follows these principles:
- **Never trust user input** — validate and sanitize everything
- **Escape output** — prevent XSS in templates
- **CSRF protection** — form keys on all POST forms
- **ACL enforcement** — restrict access by role
- **Prepared statements** — prevent SQL injection
- **No secrets in code** — use `env.php` and `.env`

---

## 2. Form Keys (CSRF Protection)

### 2.1 What is CSRF?

**CSRF** = Cross-Site Request Forgery. An attacker tricks a logged-in user
into submitting a form without their knowledge.

**Example attack**:
```html
<!-- Attacker's website -->
<form action="https://magento.com/admin/blog/post/delete/id/1" method="POST">
    <input type="submit" value="Click for free iPhone!"/>
</form>
<script>document.forms[0].submit();</script>
```

If the admin is logged in, the form is submitted with their session.

### 2.2 Magento's solution: Form Key

Magento adds a hidden field with a unique token to every form:

```php
<!-- In a .phtml template -->
<form action="<?= $block->getUrl('blog/post/save') ?>" method="POST">
    <input type="hidden" name="form_key" value="<?= $block->getFormKey() ?>">
    <input type="text" name="title" value=""/>
    <button type="submit">Save</button>
</form>
```

Magento validates the `form_key` on every POST request. If it's missing or
invalid, the request is rejected with a 403.

### 2.3 Form key in AJAX

```js
// Get form key from global scope
var formKey = window.FORM_KEY;

$.ajax({
    url: '/rest/V1/alphacommerce/product-reviews',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({...}),
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});
```

### 2.4 Form key in GraphQL

GraphQL uses **bearer tokens** instead of form keys. The token is obtained
via customer login or admin authentication.

---

## 3. ACL (Access Control List)

### 3.1 Purpose

ACL restricts what each admin user can see and do.

### 3.2 Defining ACL

```xml
<!-- etc/acl.xml -->
<acl xmlns:xsi="..." xsi:noNamespaceSchemaLocation="...">
    <resources>
        <resource id="Magento_Backend::admin">
            <resource id="AlpineCommerce_Blog::main" title="Blog" sortOrder="10">
                <resource id="AlpineCommerce_Blog::post" title="Posts" sortOrder="10"/>
                <resource id="AlpineCommerce_Blog::category" title="Categories" sortOrder="20"/>
            </resource>
        </resource>
    </resources>
</acl>
```

### 3.3 Using ACL in Controllers

```php
// Controller/Adminhtml/Post/Index.php
class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute(): void
    {
        // Magento automatically checks if the user has this permission
        // If not, a 403 Access Denied page is shown
    }
}
```

### 3.4 Using ACL in system.xml

```xml
<field id="enabled" ...>
    <label>Enabled</label>
    <resource>AlpineCommerce_Blog::config</resource>
</field>
```

Only users with `AlpineCommerce_Blog::config` permission can see/change this field.

---

## 4. XSS Prevention

### 4.1 What is XSS?

**XSS** = Cross-Site Scripting. An attacker injects malicious JavaScript
into a page viewed by other users.

**Example**:
```html
<!-- User inputs: <script>stealCookies()</script> -->
<p><?= $post->getTitle() ?></p>
<!-- Browser executes the script -->
```

### 4.2 Magento's solution: escapeHtml()

```php
<!-- ✅ SAFE: escaped -->
<p><?= $block->escapeHtml($post->getTitle()) ?></p>
<!-- Output: &lt;script&gt;stealCookies()&lt;/script&gt; -->

<!-- ❌ DANGEROUS: not escaped -->
<p><?= $post->getTitle() ?></p>
<!-- Output: <script>stealCookies()</script> -->
```

### 4.3 Escape methods

| Method | Use case | Example |
|--------|----------|---------|
| `escapeHtml()` | HTML content | `$block->escapeHtml($title)` |
| `escapeUrl()` | URLs | `$block->escapeUrl($url)` |
| `escapeJs()` | JavaScript strings | `$block->escapeJs($string)`` |
| `escapeAttr()` | HTML attributes | `$block->escapeAttr($value)` |

### 4.4 When NOT to escape

```php
<!-- When the content is intentionally HTML (e.g., CMS content) -->
<?= $block->getLayout()->createBlock(\Magento\Cms\Block\Block::class)
    ->setBlockId('my_block')
    ->toHtml(); ?>
```

**Only escape when the content comes from user input or the database.**

---

## 5. Input Validation

### 5.1 In Controllers

```php
public function execute()
{
    $title = $this->getRequest()->getParam('title');
    
    if (empty($title)) {
        throw new \Magento\Framework\Exception\InputException(
            __('Title is required.')
        );
    }
    
    if (strlen($title) > 255) {
        throw new \Magento\Framework\Exception\InputException(
            __('Title must not exceed 255 characters.')
        );
    }
}
```

### 5.2 In Data Objects

```php
// Api/Data/PostInterface.php
interface PostInterface
{
    public function getTitle(): string;
    public function setTitle(string $title): PostInterface;
}
```

PHP type hints (`string $title`) provide basic validation.

### 5.3 In Repositories

```php
public function save(PostInterface $post): PostInterface
{
    $title = $post->getTitle();
    
    if (empty($title)) {
        throw new InputException(__('Title cannot be empty.'));
    }
    
    if (strlen($title) > 255) {
        throw new InputException(__('Title must not exceed 255 characters.'));
    }
    
    $this->resource->save($post);
    return $post;
}
```

---

## 6. SQL Injection Prevention

### 6.1 Never concatenate SQL

```php
// ❌ DANGEROUS: SQL injection
$sql = "SELECT * FROM blog_post WHERE title = '" . $title . "'";
$connection->query($sql);
```

### 6.2 Use parameter binding

```php
// ✅ SAFE: parameter binding
$sql = "SELECT * FROM blog_post WHERE title = :title";
$connection->fetchAll($sql, ['title' => $title]);
```

### 6.3 Use ResourceModel

Magento's ResourceModel automatically uses parameter binding:

```php
// Model/ResourceModel/Post.php
protected function _init($table, $idFieldName)
{
    $this->_init($table, $idFieldName);
}

// Usage (safe):
$post = $this->postFactory->create();
$this->resource->load($post, $id); // Automatically safe
```

---

## 7. Secrets Management

### 7.1 Never commit secrets

```php
// ❌ NEVER do this
$apiKey = 'sk_live_1234567890abcdef';

// ✅ Use environment variables or env.php
$apiKey = getenv('API_KEY');
// Or in Magento:
$apiKey = $this->scopeConfig->getValue('my_module/api/key');
```

### 7.2 Magento's env.php

```php
// app/etc/env.php
return [
    'backend' => [
        'frontName' => 'admin'
    ],
    'db' => [
        'connection' => [
            'default' => [
                'host' => 'localhost',
                'dbname' => 'magento',
                'username' => 'magento',
                'password' => 'secret_password' // ← This is OK, it's not in Git
            ]
        ]
    ],
    'cache' => [
        'frontend' => [
            'default' => [
                'backend' => 'Cm_Cache_Backend_Redis',
                'backend_options' => [
                    'server' => 'localhost',
                    'port' => '6379',
                    'database' => '0'
                ]
            ]
        ]
    ]
];
```

### 7.3 .env files

```bash
# .env (NOT committed to Git)
MYSQL_ROOT_PASSWORD=root123
MAGENTO_PUBLIC_KEY=abc123
MAGENTO_PRIVATE_KEY=def456
```

---

## 8. Safe Coding Checklist

| Check | How |
|-------|-----|
| **Escape all output** | Use `escapeHtml()`, `escapeUrl()`, `escapeJs()` in PHTML |
| **Validate all input** | Check required fields, lengths, formats in controllers/repositories |
| **Use form keys** | Add `form_key` to all POST forms |
| **Use parameter binding** | Never concatenate SQL strings |
| **No secrets in code** | Use `env.php`, `.env`, or `core_config_data` |
| **Check ACL** | Add `ADMIN_RESOURCE` in admin controllers |
| **Use HTTPS** | Always use HTTPS in production |
| **Sanitize file uploads** | Check file type, size, use random filenames |

---

## 9. Common Vulnerabilities

### 9.1 XSS in templates

```php
<!-- ❌ Vulnerable -->
<p><?= $comment->getText() ?></p>

<!-- ✅ Safe -->
<p><?= $block->escapeHtml($comment->getText()) ?></p>
```

### 9.2 SQL injection in custom queries

```php
// ❌ Vulnerable
$sql = "SELECT * FROM posts WHERE id = " . $_GET['id'];

// ✅ Safe
$sql = "SELECT * FROM posts WHERE id = :id";
$connection->fetchAll($sql, ['id' => (int) $_GET['id']]);
```

### 9.3 Missing form key

```php
<!-- ❌ Vulnerable: no form key -->
<form method="POST">
    <input type="text" name="title"/>
    <button type="submit">Save</button>
</form>

<!-- ✅ Safe: form key included -->
<form method="POST">
    <input type="hidden" name="form_key" value="<?= $block->getFormKey() ?>"/>
    <input type="text" name="title"/>
    <button type="submit">Save</button>
</form>
```

### 9.4 Missing ACL check

```php
// ❌ Vulnerable: no permission check
class Delete extends \Magento\Backend\App\Action
{
    public function execute()
    {
        // Anyone can delete!
    }
}

// ✅ Safe: ACL check
class Delete extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'AlpineCommerce_Blog::post';
    
    public function execute()
    {
        // Magento checks ACL automatically
    }
}
```

---

## 10. Security in AlpineCommerce

### 10.1 What AlpineCommerce does right

| Practice | Example |
|----------|---------|
| Form keys | All admin forms include `form_key` |
| ACL | All admin controllers have `ADMIN_RESOURCE` |
| Escape HTML | All PHTML templates use `escapeHtml()` |
| REST API auth | All endpoints require authentication (`customer` or admin token) |
| Input validation | Repositories validate data before saving |
| No secrets in code | Credentials in `env.php`, not in PHP files |

### 10.2 What to watch for

| Risk | Mitigation |
|------|------------|
| XSS in user-generated content | Always `escapeHtml()` before displaying |
| SQL injection in custom queries | Use ResourceModel, never concatenate SQL |
| Missing ACL on new controllers | Add `ADMIN_RESOURCE` constant |
| Exposed API endpoints | Use proper ACL resources in `webapi.xml` |
| Weak passwords | Enforce strong passwords in admin |

---

## 11. Summary

| Threat | Magento Protection | Developer Responsibility |
|--------|-------------------|-------------------------|
| **CSRF** | Form keys | Include `form_key` in all POST forms |
| **XSS** | `escapeHtml()` | Always escape user-generated content |
| **SQL Injection** | Prepared statements | Use ResourceModel, never concatenate SQL |
| **Unauthorized access** | ACL | Add `ADMIN_RESOURCE` to admin controllers |
| **Data leaks** | `env.php` encryption | Never commit secrets |
| **Input tampering** | Validation | Validate all input in controllers/repositories |

---

*Last updated: 2026-08-11.*
