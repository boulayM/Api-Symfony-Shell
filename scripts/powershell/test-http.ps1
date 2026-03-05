param(
    [string]$BaseUrl = "http://127.0.0.1:8000"
)

$ErrorActionPreference = 'Stop'

$health = Invoke-WebRequest -Uri "$BaseUrl/api/health" -UseBasicParsing
if ($health.StatusCode -ne 200) {
    throw "Health endpoint failed with status $($health.StatusCode)"
}

$publicHealth = Invoke-WebRequest -Uri "$BaseUrl/api/public/health" -UseBasicParsing
if ($publicHealth.StatusCode -ne 200) {
    throw "Public health endpoint failed with status $($publicHealth.StatusCode)"
}

Write-Host "HTTP smoke tests passed against $BaseUrl"
