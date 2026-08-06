# Developer Cheatsheet

## Docker

### Containers
```bash
docker ps                          # Containers running
docker ps -a                       # All containers (running + stopped)
docker stop <container>            # Stop container
docker start <container>           # Start container
docker restart <container>         # Restart container
docker rm <container>              # Remove container
docker logs <container>            # View logs
docker logs -f <container>         # Follow logs (live)
docker exec -it <container> bash   # Enter container terminal
docker stats                       # CPU/RAM usage live
```

### Images
```bash
docker images                      # List images
docker rmi <image>                 # Remove image
docker system prune -a             # Clean all unused data
docker volume prune                # Clean unused volumes
```

### Docker Compose
```bash
docker compose up -d               # Start all services (detached)
docker compose down                # Stop all services
docker compose ps                  # List services
docker compose logs -f             # Follow all logs
docker compose up -d --build       # Rebuild and start
docker compose restart <service>   # Restart specific service
docker compose exec <service> bash # Enter service terminal
```

---

## Magento 2

### Setup
```bash
bin/magento setup:install                          # Install Magento
bin/magento setup:upgrade                          # Run upgrades
bin/magento setup:di:compile                       # Compile DI
bin/magento setup:static-content:deploy -f         # Deploy static files
bin/magento cache:flush                            # Clear all cache
bin/magento cache:clean                            # Clean cache
bin/magento indexer:reindex                        # Reindex all
```

### Admin & Config
```bash
bin/magento admin:user:create                      # Create admin user
bin/magento config:set web/unsecure/base_url http://localhost:8080/
bin/magento config:set web/secure/base_url http://localhost:8080/
bin/magento store:config:show                      # Show config values
```

### Debugging
```bash
bin/magento deploy:mode:show                       # Show mode (default/developer/production)
bin/magento deploy:mode:set developer              # Switch to developer mode
bin/magento deploy:mode:set production             # Switch to production mode
bin/magento module:status                          # List modules status
bin/magento module:enable <Vendor_Module>          # Enable module
bin/magento module:disable <Vendor_Module>         # Disable module
```

### Database
```bash
bin/magento db:dump                                # Backup database
bin/magento db:import <file.sql>                   # Import database
bin/magento db:status                              # Database status
```

### CLI Shortcuts
```bash
php bin/magento c:f                                # cache:flush
php bin/magento c:c                                # cache:clean
php bin/magento s:d:c                              # setup:di:compile
php bin/magento s:s:d -f                           # setup:static-content:deploy -f
php bin/magento i:ir                                # indexer:reindex
```

---

## Git

### Basics
```bash
git init                        # Init repo
git clone <url>                 # Clone repo
git status                      # Status
git add .                       # Stage all
git add <file>                  # Stage file
git commit -m "message"         # Commit
git push                        # Push
git pull                        # Pull
git log --oneline -10           # Last 10 commits
git diff                        # Unstaged changes
git diff --staged               # Staged changes
```

### Branches
```bash
git branch                      # List branches
git branch <name>               # Create branch
git checkout <branch>           # Switch branch
git checkout -b <branch>        # Create + switch
git merge <branch>              # Merge branch
git branch -d <branch>          # Delete branch
```

### Undo / Fix
```bash
git reset --soft HEAD~1         # Undo last commit (keep changes)
git reset --hard HEAD~1         # Undo last commit (discard changes)
git stash                       # Stash changes
git stash pop                   # Apply stash
git revert <commit>             # Revert specific commit
```

### Remote
```bash
git remote -v                   # List remotes
git remote add origin <url>     # Add remote
git push -u origin <branch>     # Push + set upstream
```

---

## Linux / Terminal

### Files
```bash
ls -la                          # List all files
cd <dir>                        # Navigate
pwd                             # Current path
mkdir -p <dir>                  # Create dir
rm -rf <dir>                    # Remove dir
cp -r <src> <dest>              # Copy
mv <src> <dest>                 # Move/Rename
chmod +x <file>                 # Make executable
```

### Search
```bash
find . -name "*.php"            # Find files
grep -r "text" .                # Search in files
which <command>                 # Find command location
```

### Process
```bash
top                             # Process monitor
htop                            # Better process monitor
kill <pid>                      # Kill process
killall <name>                  # Kill by name
ps aux | grep <name>            # Find process
```

### Network
```bash
curl http://localhost:8080      # Test endpoint
netstat -tlnp                   # Open ports
ss -tlnp                        # Open ports (modern)
ping <host>                     # Test connection
```

---

## PHP
```bash
php -v                          # Version
php -m                          # List modules
php -i | grep <word>            # Check config
php -l <file.php>               # Syntax check
```

## Composer
```bash
composer install                # Install dependencies
composer update                 # Update dependencies
composer require <package>      # Add package
composer remove <package>       # Remove package
composer dump-autoload          # Regenerate autoload
```

---

## Magento Project Specific
```bash
# Docker services
docker compose up -d
docker compose down
docker compose logs -f nginx
docker compose logs -f php

# Enter PHP container
docker exec -it magento2-php bash

# Run Magento commands inside container
docker exec -it magento2-php php bin/magento setup:upgrade
docker exec -it magento2-php php bin/magento cache:flush
docker exec -it magento2-php php bin/magento setup:di:compile
docker exec -it magento2-php php bin/magento setup:static-content:deploy -f

# Magento URL
http://localhost:8080

# Admin URL
http://localhost:8080/admin
```
