# StartMagento.ps1 - Start Magento 2 Docker Environment (Windows PowerShell)

$ErrorActionPreference = "Stop"
$PROJECT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $PROJECT_DIR

Write-Host "Starting Magento 2 Docker environment..."
docker compose up -d

Write-Host ""
Write-Host "Services started:"
Write-Host "  - PHP-FPM     : localhost:9000"
Write-Host "  - Nginx       : http://localhost:8080"
Write-Host "  - MySQL       : localhost:3306"
Write-Host "  - Elasticsearch: http://localhost:9200"
Write-Host "  - Redis       : localhost:6379"
