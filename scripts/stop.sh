#!/bin/bash
set -e

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

echo "Stopping Magento 2 Docker environment..."
docker compose down

echo "Environment stopped."
