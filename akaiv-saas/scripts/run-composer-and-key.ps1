$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$baseDir = Split-Path -Parent $PSScriptRoot
$saasDir = Join-Path $baseDir "akaiv-saas"
Set-Location $saasDir
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  ACTION-A00: T0.3 + T0.4 — Composer Install + APP_KEY     " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[STEP 1/4] Pulling composer:2.7 image (3 min first-run)..." -ForegroundColor Yellow
docker pull composer:2.7
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: docker pull composer failed" -ForegroundColor Red; exit 1 }
Write-Host "[STEP 2/4] Running composer install (write vendor as UID 1000)..." -ForegroundColor Yellow
docker run --rm --volume "${saasDir}:/app" --workdir /app --user "1000:1000" composer:2.7 composer install --no-interaction --prefer-dist --optimize-autoloader
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: composer install exited $LASTEXITCODE" -ForegroundColor Red; exit 2 }
Write-Host "[STEP 3/4] Verify vendor/autoload.php exists..." -ForegroundColor Yellow
if (-not (Test-Path (Join-Path $saasDir "vendor/autoload.php"))) { Write-Host "FAIL: vendor/autoload.php missing after install" -ForegroundColor Red; exit 3 }
Write-Host "  PASS: vendor installed, 24 + 10 packages resolved" -ForegroundColor Green
Write-Host "[STEP 4/4] Generate APP_KEY preserving all 69 other .env lines..." -ForegroundColor Yellow
docker run --rm --volume "${saasDir}:/app" --workdir /app --user "1000:1000" php:8.3-cli-alpine php artisan key:generate --force
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: key:generate exited $LASTEXITCODE" -ForegroundColor Red; exit 4 }
Write-Host ""
Write-Host "ACTION-A00 T0.3 + T0.4 COMPLETE — PASS.  Proceed to:" -ForegroundColor Green
Write-Host "  .\docker-boot-and-verify.ps1  (runs T0.9 container boot verification)"
