$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "Stopping HRM local stack..." -ForegroundColor Cyan
docker compose down

Write-Host "HRM stopped." -ForegroundColor Green
