$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$saasDir = "c:\Users\TV\Desktop\documentarchive.online\homedir\akaiv-saas"
Set-Location $saasDir
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  ACTION-A00: T0.9 — Container Boot + Artisan Verify        " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[GATE-A00-05 CHECK 1/3] Bringing up php + pgsql + redis (minimal infra)..." -ForegroundColor Yellow
docker compose up -d --build php pgsql redis
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: compose up exit $LASTEXITCODE" -ForegroundColor Red; exit 1 }
Write-Host "  Waiting 55s for containers + PostgreSQL healthcheck warmup" -ForegroundColor Yellow
Start-Sleep -Seconds 55
Write-Host "[GATE-A00-05 CHECK 2/3] php artisan --version" -ForegroundColor Yellow
docker exec akaiv-php php artisan --version
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: artisan --version exit $LASTEXITCODE" -ForegroundColor Red; exit 2 }
Write-Host "[GATE-A00-05 CHECK 3/3] php artisan about" -ForegroundColor Yellow
docker exec akaiv-php php artisan about
if ($LASTEXITCODE -ne 0) { Write-Host "FAIL: artisan about exit $LASTEXITCODE — check logs for PHP Fatal" -ForegroundColor Red
docker logs akaiv-php --tail 50; exit 3 }
Write-Host "[EXTRA CHECK] Migrate status (no actual migration run, just connectivity):" -ForegroundColor Yellow
docker exec akaiv-php php artisan migrate:status
Write-Host ""
Write-Host "GATE-A00-05 = PASS.  Next: migrations (ACTION-A01) then Filament resources." -ForegroundColor Green
Write-Host "  Tear down minimal infra after checks with: docker compose stop"
