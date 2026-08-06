#!/bin/bash
set -e

PROJECT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$PROJECT_DIR"

echo "Running Magento CLI commands..."
docker compose exec php bin/magento "$@"
