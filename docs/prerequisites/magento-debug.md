# Magento Debugging

Debugging Magento 2 requires knowing where to look and which tools to use.

## Log files

| Log | Location | Purpose |
|---|---|---|
| **System log** | `var/log/system.log` | General system messages |
| **Exception log** | `var/log/exception.log` | Uncaught exceptions |
| **Debug log** | `var/log/debug.log` | Custom debug messages |
| **Cron log** | `var/log/cron.log` | Cron job output |

## Enable debug logging

```php
// In etc/env.php or via CLI
'debug' => [
    'debug_logging' => 1
]
```

Or programmatically:

```php
$this->logger->debug('Debug message', ['key' => 'value']);
```

## Developer mode

```bash
php bin/magento deploy:mode:set developer
```

Developer mode shows:
- Error messages directly in browser
- Static files generated on-the-fly
- No static file signing

## Xdebug

For step debugging:
1. Install Xdebug extension
2. Configure `php.ini`:
   ```
   xdebug.mode = debug
   xdebug.start_with_request = yes
   ```
3. Set breakpoints in IDE
4. Trigger debug via browser or CLI

## Common issues

| Issue | Check |
|---|---|
| White screen | `var/log/exception.log`, enable developer mode |
| 404 on frontend | Rewrite rules, base URL config |
| Permission denied | `var`, `pub/static`, `generated` permissions |
| Module not loading | `app/etc/config.php`, module status |

## Where to go next

- **Full reference**: [`../magento2/magento-debug.md`](../magento2/magento-debug.md)
- **CLI commands**: [`magento-cli.md`](magento-cli.md)

---

*Prerequisite for: daily development, troubleshooting*
