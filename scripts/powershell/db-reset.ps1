$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Join-Path $PSScriptRoot "..\..\")).Path
Set-Location $projectRoot

php bin/console doctrine:database:drop --if-exists --force
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:fixtures:load -n

Write-Host "Database reset complete"
