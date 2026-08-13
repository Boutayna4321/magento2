# Magento 2.4.8 — Permissions & var/ Troubleshooting Guide

Practical diagnosis and fixes for the most frequent Magento Admin permission and filesystem issues.

---

## 1. Symptom: "Sorry, you need permissions to view this content."

### Diagnosis

Run these queries to identify the exact cause:

```sql
-- 1. Check if authorization_rule is empty (most common cause)
SELECT COUNT(*) AS total_rules FROM authorization_rule;

-- 2. Check if Magento_Backend::all exists
SELECT rule_id, role_id, resource_id, permission
FROM authorization_rule
WHERE resource_id = 'Magento_Backend::all';

-- 3. Check your admin user's role inheritance
SELECT au.username, ar.role_id, ar.role_name, ar.parent_id, parent.role_name AS parent_role
FROM admin_user au
JOIN authorization_role ar ON ar.user_id = au.user_id
LEFT JOIN authorization_role parent ON parent.role_id = ar.parent_id
WHERE au.username = 'admin';
```

### Situations and fixes

| Situation | Indicator | Fix |
|---|---|---|
| `authorization_rule` is **empty** | `total_rules = 0` | The bootstrap ACL rule is missing. Re-save the **Administrators** role in Admin, or insert the missing rule manually if Admin is inaccessible. |
| `Magento_Backend::all` **missing** for role 1 | No row with `resource_id = 'Magento_Backend::all'` | Same as above. This single rule grants full Admin access. |
| Admin user role has **wrong parent** | `parent_id` is NULL or points to unexpected role | Fix the role hierarchy: `UPDATE authorization_role SET parent_id = 1 WHERE user_id = 1 AND role_name = 'Admin';` |
| `authorization_rule` contains **old module names** | Rows with `Cartware_*` or other stale prefixes | These are orphaned rules from a module rename. They don't cause denial, but clean them up: `DELETE FROM authorization_rule WHERE resource_id LIKE 'Cartware_%';` |
| ACL cache is **stale** | Rules exist but access still denied | Clean caches: `bin/magento cache:clean` and `bin/magento cache:flush` |

### Quick fix when Admin is completely locked

```sql
-- Emergency restore: grants full access to Administrators role
INSERT INTO authorization_rule (role_id, resource_id, privileges, permission)
VALUES (1, 'Magento_Backend::all', NULL, 'allow');
```

Then log in, go to **System → Permissions → User Roles → Administrators → Save Role** with **Resource Access = All**.

---

## 2. Symptom: 404 on Admin pages that should exist

### Diagnosis

```bash
# Check if the route is registered
grep -r "frontName=\"<expected>\"" src/app/code/AlpineCommerce/*/etc/adminhtml/routes.xml

# Check if the controller file exists
ls src/app/code/AlpineCommerce/*/Controller/Adminhtml/*/
```

### Common causes

| Cause | Example | Fix |
|---|---|---|
| **Missing `routes.xml`** | Module has controllers but no route registration | Create `etc/adminhtml/routes.xml` |
| **Stale frontName in code** | Controller redirects use `old_frontName` but route uses `new_frontName` | Update all URLs in controllers, UI components, and menu.xml |
| **Menu points to wrong route** | `action="nonexistent/route/index"` | Fix the `action` attribute in `menu.xml` |
| **Layout handle mismatch** | Layout file `old_controller_action.xml` but route expects `new_controller_action.xml` | Rename layout files to match actual controller/action |

---

## 3. Symptom: "Access denied" on specific Admin page only

### Diagnosis

```sql
-- Check if the specific ACL resource exists
SELECT * FROM authorization_rule
WHERE resource_id = 'AlpineCommerce_Module::expected_resource';

-- Check if it's defined in code
grep -r "AlpineCommerce_Module::expected_resource" src/app/code/AlpineCommerce/*/etc/acl.xml
```

### Situations

| Situation | Fix |
|---|---|
| ACL resource exists in `acl.xml` but not in `authorization_rule` | Re-save the Administrator role, or ensure the role has permission to the parent resource |
| ACL resource typo in controller | Fix `ADMIN_RESOURCE` or `_isAllowed()` to match `acl.xml` exactly |
| ACL resource missing from `acl.xml` | Add the resource definition to `etc/acl.xml` |
| Menu resource mismatch | Ensure `menu.xml` `resource` attribute matches a resource in `acl.xml` |

---

## 4. Symptom: Admin menu items missing

### Diagnosis

```bash
# Check if menu.xml exists
ls src/app/code/AlpineCommerce/*/etc/adminhtml/menu.xml

# Check if menu item has an action attribute
grep -A5 "<add" src/app/code/AlpineCommerce/*/etc/adminhtml/menu.xml
```

### Situations

| Situation | Fix |
|---|---|
| `menu.xml` missing entirely | Create it or navigate via direct URL |
| Menu item has no `action` attribute | Add `action="route/controller/index"` |
| Menu parent is invalid | Use valid parent like `Magento_Backend::content` or `Magento_Backend::marketing` |
| ACL resource missing | Add resource to `acl.xml` or fix the reference |

---

## 5. Symptom: File permission errors in `var/` or `pub/`

### Diagnosis

```bash
# Check ownership
ls -la var/ pub/static/ pub/media/ generated/

# Typical correct ownership: www-data:www-data (or your web server user)
```

### Fix

```bash
# Set correct ownership
sudo chown -R www-data:www-data var/ pub/static/ pub/media/ generated/ app/etc/

# Set correct permissions
find var/ pub/static/ pub/media/ generated/ -type f -exec chmod 644 {} \;
find var/ pub/static/ pub/media/ generated/ -type d -exec chmod 755 {} \;
chmod -R 777 var/ pub/static/ pub/media/ generated/  # if needed for development
```

---

## 6. Symptom: Cache issues after code changes

### Diagnosis

Changes to `routes.xml`, `menu.xml`, `acl.xml`, `di.xml`, and `system.xml` require cache refresh.

### Fix

```bash
# Clean all caches
bin/magento cache:clean

# Flush all caches (including ACL)
bin/magento cache:flush

# If routes changed, clean browser cookies/session too
# Magento may redirect to old admin path
```

---

## 7. Symptom: "Invalid form key" or session issues

### Diagnosis

```bash
# Check session storage
# Redis issues?
docker exec magento2-mysql redis-cli ping

# Check var/session
ls -la var/session/
```

### Fix

```bash
# Clean session cache
rm -rf var/session/*

# If using Redis, flush it
docker exec magento2-redis redis-cli FLUSHALL

# Verify session config in app/etc/env.php
```

---

## 8. Quick Decision Tree

```
Admin permission problem?
│
├─ "Sorry, you need permissions..."
│   ├─ authorization_rule empty?
│   │   ├─ YES → Insert Magento_Backend::all or re-save Administrator role
│   │   └─ NO → Check specific resource in authorization_rule
│   │       ├─ Missing → Re-save role or check ACL inheritance
│   │       └─ Present → Check ACL cache: bin/magento cache:flush
│   │
│   └─ Old module rules present?
│       └─ YES → Clean up stale rules (DELETE FROM authorization_rule WHERE resource_id LIKE 'OldModule_%')
│
├─ 404 on Admin page
│   ├─ routes.xml missing?
│   │   └─ YES → Create it
│   ├─ Controller file missing?
│   │   └─ YES → Create controller
│   ├─ Menu action wrong?
│   │   └─ YES → Fix action attribute
│   └─ URL uses old frontName?
│       └─ YES → Update to current frontName
│
├─ Menu item missing
│   ├─ menu.xml missing?
│   │   └─ YES → Create it
│   ├─ No action attribute?
│   │   └─ YES → Add action
│   └─ ACL resource missing?
│       └─ YES → Add to acl.xml
│
└─ File permission errors
    ├─ Wrong ownership?
    │   └─ Fix: chown -R www-data:www-data var/ pub/ generated/
    └─ Wrong permissions?
        └─ Fix: find . -type d -exec chmod 755 {} \;
```

---

## 9. Essential Commands Reference

```bash
# Database backup (always do this first)
docker exec magento2-mysql mysqldump -u magento -pmagento123 magento2 > backup.sql

# Check ACL rules
docker exec magento2-mysql mysql -u magento -pmagento123 magento2 -e "SELECT * FROM authorization_rule WHERE role_id = 1;"

# Check admin user role
docker exec magento2-mysql mysql -u magento -pmagento123 magento2 -e "SELECT * FROM authorization_role WHERE user_id = 1;"

# Clean caches
bin/magento cache:clean
bin/magento cache:flush

# Check enabled modules
cat app/etc/config.php | grep AlpineCommerce

# Verify route registration
grep -r "frontName=" src/app/code/AlpineCommerce/*/etc/adminhtml/routes.xml

# Check ACL definitions
grep -r "resource id=" src/app/code/AlpineCommerce/*/etc/acl.xml
```

---

## 10. Common Pitfalls

| Pitfall | Why It Happens | Prevention |
|---|---|---|
| Forgetting to clear cache after route changes | Magento caches route configuration | Always run `cache:clean` after `routes.xml` changes |
| Using old frontName in redirects | Migration renamed modules but missed URL updates | Search entire module for old frontName strings |
| Empty `authorization_rule` after DB restore | Database restore doesn't include ACL rules | Re-save Administrator role after any DB restore |
| ACL resource typo | `AlpineCommerce_Module::resouce` vs `resource` | Copy-paste resource IDs from `acl.xml` to controllers |
| Missing `before="Magento_Backend"` in route | Controller not found despite correct path | Always include `before="Magento_Backend"` in admin routes |

---

## 11. When to Ask for Help

Ask for help if:
- Admin is completely locked AND inserting `Magento_Backend::all` doesn't fix it
- Routes exist but controllers are never called (possible router override)
- Cache clean doesn't resolve permission issues
- You see ACL errors in `var/log/debug.log` but can't identify the resource

**Before asking, always provide:**
1. Output of `SELECT COUNT(*) FROM authorization_rule;`
2. Output of `SELECT * FROM authorization_role WHERE user_id = 1;`
3. The exact error message and URL
4. Recent changes made before the issue appeared
 extension missing` | `ext-hash` not installed | Install PHP hash extension |
| `iconv extension missing` | `ext-iconv` not installed | Install PHP iconv extension |
| `simplexml missing` | `ext-simplexml` not installed | Install PHP simplexml extension |
| `dom extension missing` | `ext-dom` not installed | Install PHP dom extension |
| `xml extension missing` | `ext-xml` not installed | Install PHP xml extension |
| `xmlreader missing` | `ext-xmlreader` not installed | Install PHP xmlreader extension |
| `xmlwriter missing` | `ext-xmlwriter` not installed | Install PHP xmlwriter extension |
| `tokenizer missing` | `ext-tokenizer` not installed | Install PHP tokenizer extension |
| `curl missing` | `ext-curl` not installed | Install PHP curl extension |
| `bcmath missing` | `ext-bcmath` not installed | Install PHP bcmath extension |
| `gd missing` | `ext-gd` not installed | Install PHP gd extension |
| `imagick missing` | `ext-imagick` not installed | Install PHP imagick extension |
| `redis missing` | `ext-redis` not installed | Install PHP redis extension |
| `amqp missing` | `ext-amqp` not installed | Install PHP amqp extension |
| `rabbitmq missing` | RabbitMQ not running | Start RabbitMQ service |
| `elasticsearch missing` | ES not running | Start Elasticsearch service |
| `opensearch missing` | OpenSearch not running | Start OpenSearch service |
| `mysql missing` | MySQL not running | Start MySQL service |
| `nginx/apache misconfigured` | Web server not serving correctly | Check vhost config |
| `ssl certificate expired` | HTTPS not working | Renew SSL certificate |
| `dns resolution failed` | Domain not resolving | Check DNS settings |
| `firewall blocking` | Port not open | Open port 80/443 |
| `selinux enforcing` | Permission denied | Set `setenforce 0` or configure policies |
| `apparmor blocking` | Permission denied | Adjust AppArmor profile |
| `docker volume permissions` | Files owned by root | `chown` inside container or adjust compose user |
| `docker compose up fails` | Service dependency issue | Check `depends_on` and health checks |
| `docker logs not showing` | Logging driver issue | Check `docker-compose.yml` logging config |
| `container keeps restarting` | Application crash | Check `docker logs <container>` |
| `container out of memory` | OOM killed | Increase memory limit in Docker |
| `network timeout` | Service unreachable | Check Docker network and port mapping |
| `volume mount not working` | Path typo or permission | Verify `volumes` in `docker-compose.yml` |
| `env file not loaded` | `.env` missing or wrong path | Check `.env` location and syntax |
| `composer install fails` | Memory or network issue | Run with `--no-interaction --prefer-dist` |
| `npm install fails` | Node version mismatch | Use correct Node.js version |
| `grunt/gulp fails` | Legacy build tools | Use `setup:static-content:deploy` instead |
| `webpack fails` | Build error | Check JS source files |
| `sass/less compilation fails` | Preprocessor error | Check `.less` or `.scss` files |
| `source map missing` | Debugging hard | Enable source maps in developer mode |
| `source map showing in production` | Security risk | Disable in production |
| `xdebug enabled in production` | Performance hit | Disable Xdebug in production |
| `opcache not configured` | Performance issue | Configure `opcache` in `php.ini` |
| `php-fpm not tuned` | Performance issue | Adjust `pm.max_children` etc. |
| `mysql not tuned` | Performance issue | Adjust `innodb_buffer_pool_size` etc. |
| `redis not tuned` | Performance issue | Adjust `maxmemory` and eviction policy |
| `elasticsearch not tuned` | Performance issue | Adjust heap size and thread pool |
| `varnish not configured` | Cache not working | Configure VCL and backend |
| `cdn not configured` | Static files slow | Configure CDN base URLs |
| `backup not running` | Data loss risk | Configure automated database backups |
| `log rotation missing` | Disk full | Configure `logrotate` |
| `monitoring missing` | Issues not detected | Set up monitoring for DB, Redis, ES |
| `alerting missing` | Outages not caught | Configure alerts for critical services |
