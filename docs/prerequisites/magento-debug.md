# Magento 2 — Debug & Workflow

> **Objective**: know how to find and fix errors in
> Magento 2. This guide covers logs, developer mode, Xdebug, and the
> most common errors.

---

## 1. Magento logs

### 1.1 Where logs are located

```
src/var/
├── log/                          ← System logs
│   ├── system.log                ← General logs
│   ├── exception.log             ← PHP exceptions
│   ├── debug.log                 ← Debug logs (if enabled)
│   └── {module_name}.log         ← Module-specific logs
├── report/                       ← PHP error reports
│   └── 20260811120000_error_id   ← Error file with date
├── cache/                        ← Cache
├── page_cache/                   ← Page cache
└── session/                      ← User sessions
```

### 1.2 Enable logs

```bash
# Check if logs are enabled
php bin/magento config:show dev/log/active

# Enable logs
php bin/magento config:set dev/log/active 1

# Disable logs (production)
php bin/magento config:set dev/log/active 0
```

### 1.3 Read logs

```bash
# View the latest lines of system.log
tail -f src/var/log/system.log

# View the latest lines of exception.log
tail -f src/var/log/exception.log

# Search for a keyword
grep -i "customer" src/var/log/system.log

# View all errors of the day
ls -la src/var/report/
cat src/var/report/20260811120000_error_id
```

### 1.4 Write to logs from code

```php
// In a Block, Model, Controller, Observer, Plugin...
use Psr\Log\LoggerInterface;

class MyClass
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    public function doSomething(): void
    {
        // Info (low level)
        $this->logger->info('Processing customer ID: ' . $customerId);
        
        // Warning (medium level)
        $this->logger->warning('Customer not found, using default');
        
        // Error (high level)
        $this->logger->error('Failed to save order: ' . $e->getMessage());
        
        // Debug (only if debug is enabled)
        $this->logger->debug('SQL query: ' . $sql);
    }
}
```

### 1.5 Logs in AlpineCommerce

```php
// StoreSetup Observer
$this->logger->error('Training CustomerLogin: ' . $e->getMessage());
$this->logger->info("Training DataPatch: Created store '$code' (ID: {$store->getId()})");
```

---

## 2. Developer mode

### 2.1 Enable developer mode

```bash
# Check current mode
php bin/magento deploy:mode:show

# Switch to developer mode
php bin/magento deploy:mode:set developer

# Switch to production mode
php bin/magento deploy:mode:set production
```

### 2.2 Differences between modes

| Element | Developer | Production |
|---------|-----------|------------|
| PHP errors | Displayed on screen | Hidden (white page) |
| Logs | Maximum detail | Minimal |
| Cache | Simplified | Full |
| Static content | Generated on the fly | Pre-generated |
| DI compilation | On demand | Pre-compiled |
| Templates | Source files | Compiled files |

### 2.3 Developer mode is mandatory for

- Developing new features
- Debugging errors
- Working on templates and layouts
- Testing modules

---

## 3. Xdebug — Step-by-step debugging

### 3.1 What is Xdebug?

Xdebug is a PHP extension that allows you to:
- Set **breakpoints** (stop points)
- Execute code **line by line**
- Inspect **variables** at each step
- See the **call stack** (who calls who)

### 3.2 Docker configuration

```yaml
# docker-compose.yml (excerpt)
services:
  php:
    build:
      context: ./php
      dockerfile: Dockerfile
    volumes:
      - ./src:/var/www/html
    environment:
      - XDEBUG_MODE=develop,debug
      - XDEBUG_CONFIG=client_host=host.docker.internal client_port=9003
```

### 3.3 VS Code configuration

`.vscode/launch.json`:
```json
{
    "version": "0.2.0",
    "configurations": [
        {
            "name": "Listen for Xdebug (Docker)",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "pathMappings": {
                "/var/www/html": "${workspaceFolder}/src"
            }
        }
    ]
}
```

### 3.4 Usage

1. In VS Code, click **Run** → **Start Debugging** (F5)
2. In the PHP code, add a breakpoint (click to the left of the line number)
3. In the browser, trigger the action (click a button, load a page)
4. VS Code stops at the breakpoint
5. Examine variables, step through (F10 = step over, F11 = step into)

---

## 4. Common errors and solutions

### 4.1 White page (HTTP 500)

**Cause**: fatal PHP error.

**Solution**:
```bash
# 1. Check logs
tail -f src/var/log/exception.log
tail -f src/var/log/system.log

# 2. Enable error display
php bin/magento config:set dev/debug/error_hints 1
php bin/magento cache:flush

# 3. Check PHP syntax
php -l src/app/code/AlpineCommerce/Blog/Model/PostRepository.php
```

### 4.2 "Class not found"

**Cause**: class not compiled or wrong namespace.

**Solution**:
```bash
# 1. Check namespace in the file
#    namespace AlpineCommerce\Blog\Model;

# 2. Check path
#    src/app/code/AlpineCommerce/Blog/Model/PostRepository.php

# 3. Compile
php bin/magento setup:di:compile

# 4. Flush caches
php bin/magento cache:flush
```

### 4.3 "No such entity"

**Cause**: entity not found in DB (wrong ID, deleted entity).

**Solution**:
```php
// Check directly in DB
mysql -u root -p magento2 -e "SELECT * FROM alphacommerce_blog_post WHERE entity_id = 1;"

// Or in code, check before using
try {
    $post = $postRepository->getById($id);
} catch (NoSuchEntityException $e) {
    $this->logger->error('Post not found: ' . $id);
    // Handle the case: display a message, redirect, etc.
}
```

### 4.4 Layout XML ignored

**Cause**: wrong filename, wrong block name, cache.

**Solution**:
```bash
# 1. Check the filename
#    URL: /blog → file: blog_index_index.xml ✓

# 2. Enable template hints (see section 6)

# 3. Flush cache
php bin/magento cache:flush

# 4. Check logs for XML errors
grep -i "xml" src/var/log/system.log
```

### 4.5 "Access denied" (admin)

**Cause**: missing ACL or admin role without permission.

**Solution**:
```bash
# 1. Check ACL in etc/acl.xml
# 2. Assign the role in Stores > Settings > Admin Users > User Roles
# 3. Log out/log back in (ACLs are loaded at login)
```

### 4.6 "The request is not valid"

**Cause**: form with missing or expired security key (form key).

**Solution**:
```php
// In the .phtml template, add the form key
<input type="hidden" name="form_key" value="<?= $block->getFormKey() ?>">
```

---

## 5. Debug tools

### 5.1 Template Hints (frontend)

Displays the names of blocks and templates used on each area of the page:

```bash
# Enable via CLI
php bin/magento config:set dev/template/allow_symlink 1
php bin/magento cache:flush
```

Then in the admin: **Stores > Configuration > Advanced > Developer > Debug >
Enabled Template Paths for Storefront = Yes**

### 5.2 Block Hints (admin)

Displays block names in the admin:

```bash
php bin/magento config:set dev/debug/template_hints_admin 1
php bin/magento cache:flush
```

### 5.3 Profiler

```bash
# Enable the profiler
php bin/magento config:set dev/profiler/enabled 1

# Each block's load time appears at the bottom of the page
```

### 5.4 Developer Mode in .htaccess

```apache
# .htaccess at Magento root
SetEnv MAGE_MODE developer
```

---

## 6. Debug JavaScript

### 6.1 Chrome DevTools

```
F12 → Console
```

**View RequireJS modules**:
```js
require.s.contexts._.defined
// Displays all loaded modules
```

**Test a module**:
```js
require(['AlpineCommerce_StorePickup/js/view/store-pickup'], function (Module) {
    console.log(Module);
});
```

**Inspect a KO observable**:
```js
// If you have access to the component in the console:
$t('Pickup store saved.');
```

### 6.2 Common JS errors

| Error | Cause | Solution |
|--------|-------|----------|
| `Uncaught Error: Module name ... has not been loaded yet` | Misspelled dependency | Check the name in `define([...])` |
| `$ is not a function` | jQuery badly injected | Check parameter order |
| `ko is not defined` | Knockout not declared | Add `'ko'` in `define([...])` |
| `define is not defined` | File not loaded via RequireJS | Use `define()`, no inline `<script>` |

---

## 7. Debug PHP

### 7.1 Check what is loaded

```bash
# View active modules
php bin/magento module:status

# View a module's config
php bin/magento config:show AlpineCommerce_Blog

# View a module's routes
php bin/magento route:list | grep blog
```

### 7.2 Test a REST request

```bash
# GET
curl -H "Authorization: Bearer <token>" \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts

# POST
curl -X POST \
     -H "Content-Type: application/json" \
     -H "Authorization: Bearer <token>" \
     -d '{"title":"Test","content":"Hello"}' \
     https://localhost:8080/rest/V1/alphacommerce/blog/posts
```

### 7.3 Check the database

```bash
# Connect to MySQL
docker compose exec mysql mysql -u root -proot123 magento2

# View a module's tables
SHOW TABLES LIKE 'alphacommerce_%';

# View data
SELECT * FROM alphacommerce_blog_post LIMIT 10;

# View configuration
SELECT * FROM core_config_data WHERE path LIKE 'blog/%';
```

### 7.4 Test a Data Patch

```bash
# View applied patches
php bin/magento setup:db-data:status

# Apply a specific patch
php bin/magento setup:upgrade --keep-generated
```

---

## 8. Recommended debug workflow

### 8.1 Facing a 500 error

```
1. Read the displayed error (if developer mode)
   or check src/var/log/exception.log

2. Identify the faulty file and line

3. If it is a PHP error:
    - Check syntax: php -l file.php
    - Check dependencies (use statements)
    - Check DI injections (constructor)

4. If it is an XML error:
    - Validate the file: xmllint --noout file.xml
    - Check attribute names (case sensitive)

5. Fix → recompile if necessary → flush cache
```

### 8.2 Facing an incorrect display

```
1. Enable template hints
2. Identify which template is used
3. Identify which block provides the data
4. Check the layout XML that creates this block
5. Check the PHP Block (getData methods)
6. Check the .phtml template (loops, conditions)
```

### 8.3 Facing an AJAX/JS error

```
1. Open browser console (F12)
2. View errors in the "Console" tab
3. View the AJAX request in the "Network" tab
4. Check the response (status, body)
5. Check the JS code (RequireJS, KO, jQuery error)
6. Use require([...], function(...){ console.log(...); }) to test
```

---

## 9. Debug checklist

| Problem | Check |
|----------|----------|
| White page | `exception.log`, developer mode, `php -l` |
| 500 error | `exception.log`, `report/` |
| Template not found | Filename, `template` in layout XML |
| Block invisible | `referenceContainer` correct, `before`/`after`, cache |
| Empty data | PHP Block (`getData`), DataProvider, Repository |
| AJAX fails | Network console, URL, headers, token |
| Module not enabled | `module:status`, `config.php` |
| "Class not found" error | Namespace, path, `setup:di:compile` |
| Cache not updated | `cache:flush` |
| Layout ignored | Filename, valid XML, cache |
| Permission denied | `var/`, `pub/`, `generated/` ownership |

---

## 10. Summary

| Tool | Usage |
|-------|-------|
| `src/var/log/system.log` | General logs |
| `src/var/log/exception.log` | PHP exceptions |
| `src/var/report/` | Detailed error reports |
| `php bin/magento deploy:mode:set developer` | Enable debug mode |
| Template Hints | See which template/block is used |
| Xdebug | Step-by-step PHP debugging |
| Chrome DevTools | JS debugging (console, network, RequireJS) |
| `php -l` | Check PHP syntax |
| `grep` | Search in logs |
| `tail -f` | Follow logs in real time |

---

*Last updated: 2026-08-11.*
