# MagentoCLI.ps1 - Run Magento CLI commands (Windows PowerShell)
# Usage: .\scripts\MagentoCLI.ps1 cache:flush

$ErrorActionPreference = "Stop"
$PROJECT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $PROJECT_DIR

Write-Host "Running Magento CLI commands..."
docker compose exec php bin/magento @args
