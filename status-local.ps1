$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "HRM container status:" -ForegroundColor Cyan
docker compose ps

Write-Host ""
Write-Host "Quick links:" -ForegroundColor Green
Write-Host "FE: http://127.0.0.1:8088/"
Write-Host "BE health: http://127.0.0.1:8088/api/v1/health"
