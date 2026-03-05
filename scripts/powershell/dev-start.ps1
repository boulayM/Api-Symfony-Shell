param(
    [int]$Port = 8000
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..\")).Path
Set-Location $projectRoot

composer preflight:dev

if (Get-Command symfony -ErrorAction SilentlyContinue) {
    symfony server:start --no-tls -d --port=$Port
    Write-Host "Symfony server started on http://127.0.0.1:$Port"
} else {
    Write-Host "Symfony CLI not found, starting PHP built-in server on http://127.0.0.1:$Port"
    php -S "127.0.0.1:$Port" -t public
}
