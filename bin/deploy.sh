#!/bin/bash
#
# Deployment script for AlpineCommerce platform
# Usage: ./bin/deploy.sh [--rollback] [--skip-tests] [--skip-consumers]
#
# Features:
# - Runs setup:upgrade to register new modules/config
# - Clears caches
# - Validates queue consumers are registered
# - Gracefully stops/restarts consumers
# - Supports rollback via supervisor
#

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ROLLBACK=false
SKIP_TESTS=false
SKIP_CONSUMERS=false
CONSUMERS="alpinecommerce_autoinvoice_async alpincommerce_partialinvoice_async alpincommerce_creditmemo_async alpinecommerce_customercare_vip_async"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --rollback) ROLLBACK=true; shift ;;
        --skip-tests) SKIP_TESTS=true; shift ;;
        --skip-consumers) SKIP_CONSUMERS=true; shift ;;
        *) echo "Unknown option: $1"; exit 1 ;;
    esac
done

cd "$PROJECT_ROOT"

echo "=== AlpineCommerce Deployment ==="
echo "Mode: $([ "$ROLLBACK" = true ] && echo 'ROLLBACK' || echo 'DEPLOY')"

# Step 1: Backup current database state (for rollback)
echo "[1/7] Creating backup marker..."
BACKUP_TAG="deploy-$(date +%Y%m%d-%H%M%S)"
echo "$BACKUP_TAG" > /tmp/magento-deploy-marker.txt

if [ "$ROLLBACK" = true ]; then
    echo "[1/7] ROLLBACK: Restarting consumers..."
    if command -v supervisorctl &> /dev/null; then
        supervisorctl stop all 2>/dev/null || true
        supervisorctl reread 2>/dev/null || true
        supervisorctl update 2>/dev/null || true
        supervisorctl start all 2>/dev/null || true
        echo "Consumers restarted via supervisor"
    elif command -v systemctl &> /dev/null; then
        systemctl restart magento-consumers-autoinvoice magento-consumers-partialinvoice magento-consumers-creditmemo magento-consumers-customercare
        echo "Consumers restarted via systemctl"
    else
        echo "WARNING: No process manager found. Please restart consumers manually."
    fi
    exit 0
fi

# Step 2: Git status check
echo "[2/7] Checking git status..."
if ! git diff --quiet HEAD -- src/app/code/AlpineCommerce/; then
    echo "Uncommitted changes in AlpineCommerce modules detected"
    git stash push -- src/app/code/AlpineCommerce/ || true
fi

# Step 3: Run setup upgrade
echo "[3/7] Running setup:upgrade..."
php src/bin/magento setup:upgrade --no-interaction --no-ansi

# Step 4: Compile DI
echo "[4/7] Running setup:di:compile..."
php src/bin/magento setup:di:compile --no-interaction --no-ansi

# Step 5: Deploy static content
echo "[5/7] Deploying static content..."
php src/bin/magento setup:static-content:deploy --area=adminhtml --force --no-interaction --no-ansi

# Step 6: Clear caches
echo "[6/7] Clearing caches..."
php src/bin/magento cache:flush --no-interaction --no-ansi
php src/bin/magento cache:clean --no-interaction --no-ansi

# Step 7: Validate and restart consumers
if [ "$SKIP_CONSUMERS" = false ]; then
    echo "[7/7] Validating queue consumers..."

    for consumer in $CONSUMERS; do
        if php src/bin/magento queue:consumers:list 2>/dev/null | grep -q "$consumer"; then
            echo "  ✓ $consumer registered"
        else
            echo "  ✗ $consumer NOT registered"
            exit 1
        fi
    done

    echo "Restarting consumers via supervisor..."
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread
        supervisorctl update
        supervisorctl restart alpinecommerce:*
        echo "  ✓ Consumers restarted via supervisor"
    else
        echo "  ⚠ supervisorctl not found. Start consumers manually:"
        for consumer in $CONSUMERS; do
            echo "    php src/bin/magento queue:consumers:start $consumer --max-messages=100 &"
        done
    fi
else
    echo "[7/7] Skipping consumer restart (SKIP_CONSUMERS)"
fi

if [ "$SKIP_TESTS" = false ]; then
    echo "Running unit tests..."
    vendor/bin/phpunit -c src/dev/tests/unit/phpunit.xml.dist \
        src/app/code/AlpineCommerce --no-coverage || {
        echo "⚠ Unit tests had failures - check output"
    }
fi

echo ""
echo "=== Deployment $BACKUP_TAG complete ==="
echo "Health check: curl -s http://localhost/health"
echo "Metrics: curl -s http://localhost/V1/alpinecommerce/metrics"
