#!/bin/bash
# Load testing script for AlpineCommerce REST endpoints
# Usage: ./etc/performance/load-test.sh [concurrent_users] [duration_seconds]

CONCURRENT=${1:-10}
DURATION=${2:-60}
BASE_URL="http://localhost:8080"

echo "=== AlpineCommerce Load Test ==="
echo "Concurrent users: $CONCURRENT"
echo "Duration: ${DURATION}s"
echo ""

# Test 1: Health check endpoint
echo "[1/3] GET /V1/alpinecommerce/health"
for i in $(seq 1 $CONCURRENT); do
    (
        for j in $(seq 1 $((DURATION * CONCURRENT / 1000))); do
            curl -s -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE_URL/V1/alpinecommerce/health"
            sleep 0.1
        done
    ) &
done
wait

# Test 2: REST API endpoint (RMA creation - requires auth)
echo ""
echo "[2/3] POST /V1/alpinecommerce/rmas (simulated)"
echo "Skipped - requires authentication"

# Test 3: Queue publish performance (via observer)
echo ""
echo "[3/3] Order placement trigger"
echo "Skipped - requires Magento event system"

echo ""
echo "=== Load test complete ==="
