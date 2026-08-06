#!/bin/bash
set -e

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

echo "Starting Magento 2 Docker environment..."
docker compose up -d

echo ""
echo "Services started:"
echo "  - PHP-FPM     : localhost:9000"
echo "  - Nginx       : http://localhost:8080"
echo "  - MySQL       : localhost:3306"
echo "  - Elasticsearch: http://localhost:9200"
echo "  - Redis       : localhost:6379"
