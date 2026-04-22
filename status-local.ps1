$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

Write-Host "HRM container status:" -ForegroundColor Cyan
docker compose ps

Write-Host ""
Write-Host "Quick links:" -ForegroundColor Green
Write-Host "FE: http://localhost/"
Write-Host "BE health: http://localhost/api/v1/health"
