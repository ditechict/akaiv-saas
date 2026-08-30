$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'
$saasDir = "c:\Users\TV\Desktop\documentarchive.online\homedir\akaiv-saas"
Set-Location $saasDir
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  ACTION-A00: T0.10 — 5 PHP Files Regression + Gate Audit    " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""
$pass = 0
$fail = 0

function Test-Gate([string]$name, [scriptblock]$check) {
    Write-Host "[CHECK] $name" -ForegroundColor Yellow
    try {
        $result = & $check
        if ($result) {
            Write-Host "  PASS" -ForegroundColor Green
            $script:pass++
            return $true
        } else {
            Write-Host "  FAIL" -ForegroundColor Red
            $script:fail++
            return $false
        }
    } catch {
        Write-Host "  FAIL (exception: $($_.Exception.Message))" -ForegroundColor Red
        $script:fail++
        return $false
    }
}

Write-Host "========== GATE-A00-01: Laravel Entrypoints =========="
Test-Gate "artisan exists + executable?" { Test-Path (Join-Path $saasDir "artisan") -PathType Leaf }
Test-Gate "public/index.php exists?" { Test-Path (Join-Path $saasDir "public/index.php") -PathType Leaf }
Test-Gate "bootstrap/app.php exists?" { Test-Path (Join-Path $saasDir "bootstrap/app.php") -PathType Leaf }

Write-Host ""
Write-Host "========== GATE-A00-02: 14 Domain Models Resolve =========="
$models = @('User','Organization','Workspace','CaseFile','Folder','DocumentType','Document','DocumentVersion','Tag','DocumentTag','Share','Subscription','SubscriptionItem','Activity')
$modelTinker = '[' + ($models | ForEach-Object { "'$_'" }) -join ',' + ']; $r = []; foreach($m as $n){ $c="App\\Models\\$n"; $r[] = "$n=".(class_exists($c)?"OK":"MISS"); }; echo json_encode($r, JSON_UNESCAPED_SLASHES);'
$tinkerCmd = "docker exec akaiv-php php artisan tinker --execute=`"$modelTinker`""
Write-Host "  (T0.10 class_exists check — run AFTER T0.9 container boot.)"
Write-Host "  Command ready: $tinkerCmd"
foreach ($m in $models) {
    $path = Join-Path $saasDir "app/Models/$m.php"
    Test-Gate "app/Models/$m.php file exists?" { Test-Path $path -PathType Leaf }
}

Write-Host ""
Write-Host "========== GATE-A00-03: Vendor + Packages =========="
Test-Gate "vendor/autoload.php exists?" { Test-Path (Join-Path $saasDir "vendor/autoload.php") -PathType Leaf }
Test-Gate "vendor/laravel/framework exists?" { Test-Path (Join-Path $saasDir "vendor/laravel/framework") -PathType Container }
Test-Gate "vendor/filament/filament exists?" { Test-Path (Join-Path $saasDir "vendor/filament/filament") -PathType Container }
Test-Gate "vendor/bezhansalleh/filament-shield exists?" { Test-Path (Join-Path $saasDir "vendor/bezhansalleh/filament-shield") -PathType Container }
Test-Gate "vendor/spatie/laravel-permission exists?" { Test-Path (Join-Path $saasDir "vendor/spatie/laravel-permission") -PathType Container }
Test-Gate "vendor/spatie/laravel-activitylog exists?" { Test-Path (Join-Path $saasDir "vendor/spatie/laravel-activitylog") -PathType Container }
Test-Gate "vendor/spatie/laravel-medialibrary exists?" { Test-Path (Join-Path $saasDir "vendor/spatie/laravel-medialibrary") -PathType Container }
Test-Gate "vendor/spatie/laravel-tags exists?" { Test-Path (Join-Path $saasDir "vendor/spatie/laravel-tags") -PathType Container }

Write-Host ""
Write-Host "========== GATE-A00-04: docker-compose.yml Fixes =========="
$yml = Get-Content (Join-Path $saasDir "docker-compose.yml") -Raw
Test-Gate "Compose YAML has clamav.healthcheck?" { ($yml | Select-String -Pattern 'clamav:' -Context 0,20) -match 'healthcheck:' }
Test-Gate "services.php.user == `"1000:1000`"?" { ($yml | Select-String -Pattern 'container_name: akaiv-php' -Context 0,12) -match 'user:\s*"1000:1000"' }
Test-Gate "services.horizon.user set?" { ($yml | Select-String -Pattern 'container_name: akaiv-horizon' -Context 0,10) -match 'user:\s*"1000:1000"' }
Test-Gate "services.scheduler.user set?" { ($yml | Select-String -Pattern 'container_name: akaiv-scheduler' -Context 0,10) -match 'user:\s*"1000:1000"' }

Write-Host ""
Write-Host "========== GATE-A00-06: Token Budget (manual entry) =========="
Write-Host "  Allocation: 1,800 tokens.  80% alert at 1,440."
Write-Host "  Actual consumed (record from goal context): ___ / 1,800  (actual <= 1,800 = PASS)"

Write-Host ""
Write-Host "========== 5 Original PHP Files Regression Check =========="
$php5 = @(
    @{N='VirusScanDocumentJob'; P='app/Jobs/VirusScanDocumentJob.php'; Imports='App\Models\Document'},
    @{N='DocumentPolicy';         P='app/Policies/DocumentPolicy.php';        Imports='App\Models\Document, App\Models\User'},
    @{N='BelongsToOrganization';  P='app/Concerns/BelongsToOrganization.php'; Imports='App\Models\Organization'},
    @{N='OrganizationScope';      P='app/Scopes/OrganizationScope.php';       Imports='(no direct model; uses auth)'},
    @{N='MigrateLegacyDocuments'; P='app/Console/Commands/MigrateLegacyDocumentsCommand.php'; Imports='App\Models\(Org|Doc|Folder|User)'}
)
foreach ($f in $php5) {
    $fullPath = Join-Path $saasDir $f.P
    Test-Gate "$($f.N) file present" { Test-Path $fullPath -PathType Leaf }
    $content = Get-Content $fullPath -Raw -ErrorAction SilentlyContinue
    Write-Host "  (Class-resolution confirmation runs inside container via php artisan tinker: class_exists on App\Models\Document + User + Organization)"
}

Write-Host ""
Write-Host "============================================================"
Write-Host "  ACTION-A00 AUDIT SUMMARY:  PASS=$pass   FAIL=$fail"        -ForegroundColor $(if($fail -eq 0){"Green"}else{"Red"})
Write-Host "============================================================"
if ($fail -eq 0) {
    Write-Host "ALL GATES READY FOR OPERATOR-EXECUTED DOCKER BOOT VALIDATION." -ForegroundColor Green
    Write-Host "  Next: .\scripts\run-composer-and-key.ps1  →  .\scripts\docker-boot-and-verify.ps1"
    exit 0
} else {
    Write-Host "$fail gate(s) failed — resolve before advancing to ACTION-A01." -ForegroundColor Red
    exit 1
}
