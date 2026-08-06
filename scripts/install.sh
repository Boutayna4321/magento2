#!/bin/bash
set -e

echo "================================================"
echo "  Magento 2.4.8 Installation Script"
echo "================================================"

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

# ── 1. Build containers ─────────────────────────────────
echo ""
echo "[1/7] Building Docker containers..."
docker compose build --no-cache

# ── 2. Start services ───────────────────────────────────
echo ""
echo "[2/7] Starting Docker services..."
docker compose up -d

# ── 3. Wait for services to be healthy ──────────────────
echo ""
echo "[3/7] Waiting for services to be ready..."
echo "  - Waiting for MySQL..."
until docker compose exec mysql mysqladmin ping -h localhost -u root -proot123 --silent 2>/dev/null; do
    sleep 3
done
echo "  - MySQL is ready!"

echo "  - Waiting for Elasticsearch..."
until curl -s http://localhost:9200/_cluster/health 2>/dev/null | grep -q '"status"'; do
    sleep 5
done
echo "  - Elasticsearch is ready!"

echo "  - Waiting for Redis..."
until docker compose exec redis redis-cli ping 2>/dev/null | grep -q PONG; do
    sleep 3
done
echo "  - Redis is ready!"

# ── 4. Configure Composer authentication ────────────────
echo ""
echo "[4/7] Configuring Composer authentication..."
echo ""
echo "!! IMPORTANT !!"
echo "You need Magento Marketplace auth keys to continue."
echo "Get them at: https://developer.adobe.com/commerce/marketplace/"
echo ""
read -p "Enter your Magento Public Key: " MAGENTO_PUBLIC_KEY
read -p "Enter your Magento Private Key: " MAGENTO_PRIVATE_KEY

docker compose run --rm composer config repo.magento.com composer https://repo.magento.com/
docker compose run --rm composer config http-basic.repo.magento.com "$MAGENTO_PUBLIC_KEY" "$MAGENTO_PRIVATE_KEY"

# ── 5. Create Magento project via Composer ──────────────
echo ""
echo "[5/7] Creating Magento project (this may take several minutes)..."
docker compose run --rm composer create-project --repository-url=https://repo.magento.com/ magento/project-community-edition=2.4.8 .

# ── 6. Set permissions ─────────────────────────────────
echo ""
echo "[6/7] Setting file permissions..."
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

# ── 7. Install Magento ─────────────────────────────────
echo ""
echo "[7/7] Installing Magento 2.4.8..."
docker compose exec php php bin/magento setup:install \
    --base-url=http://localhost:8080 \
    --db-host=mysql \
    --db-name=magento2 \
    --db-user=magento \
    --db-password=magento123 \
    --admin-firstname=Admin \
    --admin-lastname=Admin \
    --admin-email=admin@example.com \
    --admin-user=admin \
    --admin-password=admin123 \
    --backend-frontname=admin \
    --search-engine=elasticsearch8 \
    --elasticsearch-host=elasticsearch \
    --elasticsearch-port=9200 \
    --cache-backend=redis \
    --cache-backend-redis-server=redis \
    --cache-backend-redis-port=6379 \
    --page-cache=redis \
    --page-cache-redis-server=redis \
    --page-cache-redis-port=6379 \
    --session-save=redis \
    --session-save-redis-host=redis \
    --session-save-redis-port=6379

# ── Post-install configuration ──────────────────────────
echo ""
echo "Configuring Magento..."
docker compose exec php bin/magento deploy:mode:set developer
docker compose exec php bin/magento indexer:reindex
docker compose exec php bin/magento cache:flush

echo ""
echo "================================================"
echo "  Magento 2.4.8 Installation Complete!"
echo "================================================"
echo ""
echo "  Storefront : http://localhost:8080"
echo "  Admin      : http://localhost:8080/admin"
echo "  Username   : admin"
echo "  Password   : admin123"
echo ""
echo "  MySQL      : localhost:3306"
echo "  Elasticsearch: localhost:9200"
echo "  Redis      : localhost:6379"
echo ""
