# Magento 2.4.8 Installation Script (PowerShell - Windows)
# Requires: Docker Desktop for Windows

$ErrorActionPreference = "Stop"

Write-Host "================================================"
Write-Host "  Magento 2.4.8 Installation Script"
Write-Host "================================================"

# Navigate to project root
$PROJECT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $PROJECT_DIR

# ── 1. Build containers ──
Write-Host ""
Write-Host "[1/7] Building Docker containers..."
docker compose build --no-cache

# ── 2. Start services ──
Write-Host ""
Write-Host "[2/7] Starting Docker services..."
docker compose up -d

# ── 3. Wait for services to be healthy ──
Write-Host ""
Write-Host "[3/7] Waiting for services to be ready..."

Write-Host "  - Waiting for MySQL..."
do {
    Start-Sleep -Seconds 3
    $mysqlPing = docker compose exec mysql mysqladmin ping -h localhost -u root -proot123 --silent 2>$null
} while (-not $mysqlPing)
Write-Host "  - MySQL is ready!"

Write-Host "  - Waiting for Elasticsearch..."
do {
    Start-Sleep -Seconds 5
    $esResponse = curl http://localhost:9200/_cluster/health 2>$null
    $esReady = $esResponse | Select-String '"status"'
} while (-not $esReady)
Write-Host "  - Elasticsearch is ready!"

Write-Host "  - Waiting for Redis..."
do {
    Start-Sleep -Seconds 3
    $redisPing = docker compose exec redis redis-cli ping 2>$null
} while ($redisPing -notmatch "PONG")
Write-Host "  - Redis is ready!"

# ── 4. Configure Composer authentication ──
Write-Host ""
Write-Host "[4/7] Configuring Composer authentication..."
Write-Host ""
Write-Host "!! IMPORTANT !!"
Write-Host "You need Magento Marketplace auth keys to continue."
Write-Host "Get them at: https://developer.adobe.com/commerce/marketplace/"
Write-Host ""
$MagentoPublicKey = Read-Host "Enter your Magento Public Key"
$MagentoPrivateKey = Read-Host "Enter your Magento Private Key"

docker compose run --rm composer config repo.magento.com composer https://repo.magento.com/
docker compose run --rm composer config http-basic.repo.magento.com "$MagentoPublicKey" "$MagentoPrivateKey"

# ── 5. Create Magento project via Composer ──
Write-Host ""
Write-Host "[5/7] Creating Magento project (this may take several minutes)..."
docker compose run --rm composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.8 .

# ── 6. Set permissions ──
Write-Host ""
Write-Host "[6/7] Setting file permissions..."
docker compose exec php bash -c '
    chown -R www-data:www-data /var/www/html
    find /var/www/html -type f -exec chmod 644 {} \;
    find /var/www/html -type d -exec chmod 755 {} \;
    chmod -R 777 /var/www/html/pub/media
    chmod -R 777 /var/www/html/pub/static
    chmod -R 777 /var/www/html/var
    chmod -R 777 /var/www/html/generated
    chmod -R 777 /var/www/html/app/etc
    chmod 777 /var/www/html/app/etc/env.php
    chmod 777 /var/www/html/app/etc/config.php
'

# ── 7. Install Magento ──
Write-Host ""
Write-Host "[7/7] Installing Magento 2.4.8..."
docker compose exec php php bin/magento setup:install `
    --base-url=http://localhost:8080 `
    --db-host=mysql `
    --db-name=magento2 `
    --db-user=magento `
    --db-password=magento123 `
    --admin-firstname=Admin `
    --admin-lastname=Admin `
    --admin-email=admin@example.com `
    --admin-user=admin `
    --admin-password=admin123 `
    --backend-frontname=admin `
    --search-engine=elasticsearch8 `
    --elasticsearch-host=elasticsearch `
    --elasticsearch-port=9200 `
    --cache-backend=redis `
    --cache-backend-redis-server=redis `
    --cache-backend-redis-port=6379 `
    --page-cache=redis `
    --page-cache-redis-server=redis `
    --page-cache-redis-port=6379 `
    --session-save=redis `
    --session-save-redis-host=redis `
    --session-save-redis-port=6379

# ── Post-install ──
Write-Host ""
Write-Host "Configuring Magento..."
docker compose exec php bin/magento deploy:mode:set developer
docker compose exec php bin/magento indexer:reindex
docker compose exec php bin/magento cache:flush

Write-Host ""
Write-Host "================================================"
Write-Host "  Magento 2.4.8 Installation Complete!"
Write-Host "================================================"
Write-Host ""
Write-Host "  Storefront : http://localhost:8080"
Write-Host "  Admin      : http://localhost:8080/admin"
Write-Host "  Username   : admin"
Write-Host "  Password   : admin123"
Write-Host ""
Write-Host "  MySQL      : localhost:3306"
Write-Host "  Elasticsearch: localhost:9200"
Write-Host "  Redis      : localhost:6379"
Write-Host ""
