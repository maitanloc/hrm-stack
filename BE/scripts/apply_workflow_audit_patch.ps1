param(
    [string]$HostName = "127.0.0.1",
    [int]$Port = 3306,
    [string]$Database = "HRM_SYSTEM",
    [string]$Username = "root",
    [string]$Password = "",
    [string]$SqlFile = ".\scripts\20260417_workflow_audit_logs.sql"
)

$ErrorActionPreference = "Stop"

if (!(Test-Path $SqlFile)) {
    throw "SQL file not found: $SqlFile"
}

$mysqlCmd = @(
    "-h", $HostName,
    "-P", "$Port",
    "-u", $Username,
    "-D", $Database
)

if ($Password -ne "") {
    $mysqlCmd += "-p$Password"
}

Write-Host "Applying SQL patch: $SqlFile"
Get-Content $SqlFile | & mysql @mysqlCmd

if ($LASTEXITCODE -ne 0) {
    throw "Failed to apply workflow audit patch."
}

Write-Host "Patch applied successfully."

