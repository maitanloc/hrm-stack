param(
    [string]$BaseUrl = "http://127.0.0.1:8080/api/v1",
    [string]$Email = "hai.do@company.com",
    [string]$Password = "NV0009",
    [switch]$SkipApi = $false
)

$ErrorActionPreference = "Stop"
$script:Failed = 0

function Write-Step {
    param([string]$Message)
    Write-Host ""
    Write-Host "=== $Message ==="
}

function Assert-True {
    param(
        [bool]$Condition,
        [string]$Name,
        [string]$Detail = ""
    )
    if ($Condition) {
        Write-Host "[PASS] $Name"
        return
    }

    $script:Failed += 1
    Write-Host "[FAIL] $Name"
    if ($Detail -ne "") {
        Write-Host "       $Detail"
    }
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Path,
        [object]$Body = $null,
        [string]$Token = ""
    )

    $uri = "$BaseUrl$Path"
    $headers = @{ "Content-Type" = "application/json" }
    if ($Token -ne "") {
        $headers["Authorization"] = "Bearer $Token"
    }

    if ($null -eq $Body) {
        return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -TimeoutSec 20
    }

    $json = $Body | ConvertTo-Json -Depth 8
    return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -Body $json -TimeoutSec 20
}

Write-Step "Offline workflow smoke"
php .\scripts\be_first_gate_smoke.php
Assert-True ($LASTEXITCODE -eq 0) "PHP smoke script returns success" "ExitCode=$LASTEXITCODE"

if ($SkipApi) {
    Write-Host ""
    Write-Host "Skipped API scenarios by -SkipApi."
    Write-Host "Total failed: $script:Failed"
    exit $script:Failed
}

Write-Step "API health and auth"
try {
    $health = Invoke-Api -Method "GET" -Path "/health"
    Assert-True ($health.success -eq $true) "GET /health returns success"
} catch {
    Assert-True $false "GET /health returns success" $_.Exception.Message
}

$token = ""
try {
    $login = Invoke-Api -Method "POST" -Path "/auth/login" -Body @{
        company_email = $Email
        password = $Password
    }
    $token = [string]($login.data.access_token)
    Assert-True ($token -ne "") "POST /auth/login returns access token"
} catch {
    Assert-True $false "POST /auth/login returns access token" $_.Exception.Message
}

if ($token -eq "") {
    Write-Host ""
    Write-Host "Cannot continue API scenarios without token."
    Write-Host "Total failed: $script:Failed"
    exit $script:Failed
}

Write-Step "Governance read endpoints"
try {
    $overview = Invoke-Api -Method "GET" -Path "/workflow-governance/overview" -Token $token
    Assert-True ($overview.success -eq $true) "GET /workflow-governance/overview"
} catch {
    Assert-True $false "GET /workflow-governance/overview" $_.Exception.Message
}

try {
    $catalogDomain = Invoke-Api -Method "GET" -Path "/workflow-governance/catalog?section=domain" -Token $token
    $hasItems = ($catalogDomain.success -eq $true) -and (($catalogDomain.data | Measure-Object).Count -gt 0)
    Assert-True $hasItems "GET /workflow-governance/catalog?section=domain"
} catch {
    Assert-True $false "GET /workflow-governance/catalog?section=domain" $_.Exception.Message
}

try {
    $catalogMapping = Invoke-Api -Method "GET" -Path "/workflow-governance/catalog?section=mapping" -Token $token
    $hasRequest = $catalogMapping.success -eq $true -and $null -ne $catalogMapping.data.request
    Assert-True $hasRequest "GET /workflow-governance/catalog?section=mapping"
} catch {
    Assert-True $false "GET /workflow-governance/catalog?section=mapping" $_.Exception.Message
}

Write-Step "Transition validation"
try {
    $validTransition = Invoke-Api -Method "POST" -Path "/workflow-governance/validate-transition" -Token $token -Body @{
        entity = "request"
        current_state = "SUBMITTED"
        target_state = "PENDING_APPROVAL"
    }
    Assert-True (($validTransition.success -eq $true) -and ($validTransition.data.allowed -eq $true)) "Valid transition SUBMITTED -> PENDING_APPROVAL"
} catch {
    Assert-True $false "Valid transition SUBMITTED -> PENDING_APPROVAL" $_.Exception.Message
}

try {
    $invalidTransition = Invoke-Api -Method "POST" -Path "/workflow-governance/validate-transition" -Token $token -Body @{
        entity = "request"
        current_state = "SUBMITTED"
        target_state = "APPROVED"
    }
    $isBlocked = ($invalidTransition.success -eq $true) -and ($invalidTransition.data.allowed -eq $false)
    Assert-True $isBlocked "Invalid transition SUBMITTED -> APPROVED is blocked"
} catch {
    Assert-True $false "Invalid transition SUBMITTED -> APPROVED is blocked" $_.Exception.Message
}

Write-Step "Audit log endpoint"
try {
    $auditLogs = Invoke-Api -Method "GET" -Path "/workflow-governance/audit-logs?page=1&per_page=5" -Token $token
    Assert-True ($auditLogs.success -eq $true) "GET /workflow-governance/audit-logs"
} catch {
    Assert-True $false "GET /workflow-governance/audit-logs" $_.Exception.Message
}

Write-Host ""
Write-Host "Total failed: $script:Failed"
exit $script:Failed

