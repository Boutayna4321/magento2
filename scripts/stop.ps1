# StopMagento.ps1 - Stop Magento 2 Docker Environment (Windows PowerShell)

$ErrorActionPreference = "Stop"
$PROJECT_DIR = Split-Path -Parent $PSScriptRoot
Set-Location $PROJECT_DIR

Write-Host "Stopping Magento 2 Docker environment..."
docker compose down

Write-Host "Environment stopped."
