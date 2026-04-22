$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "Starting HRM local stack..." -ForegroundColor Cyan
docker compose up -d

Write-Host ""
Write-Host "HRM is starting." -ForegroundColor Green
Write-Host "FE: http://localhost/" -ForegroundColor Yellow
Write-Host "BE health: http://localhost/api/v1/health" -ForegroundColor Yellow
