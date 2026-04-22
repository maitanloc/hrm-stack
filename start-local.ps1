$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "Starting HRM local stack..." -ForegroundColor Cyan
docker compose up -d

Write-Host ""
Write-Host "HRM is starting." -ForegroundColor Green
Write-Host "FE: http://127.0.0.1:8088/" -ForegroundColor Yellow
Write-Host "BE health: http://127.0.0.1:8088/api/v1/health" -ForegroundColor Yellow
