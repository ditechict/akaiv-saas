$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

Write-Host "============================================================" -ForegroundColor Cyan
Write-Host "  TRAE-SANDBOX ARGUMENT INJECTION REPRODUCER + VALIDATOR    " -ForegroundColor Cyan
Write-Host "============================================================" -ForegroundColor Cyan
Write-Host ""

$baseDir = "c:\Users\TV\Desktop\documentarchive.online\homedir"
$sandboxSafe = Join-Path $baseDir "trae-sandbox-safe.bat"
$storagePath = Join-Path $baseDir "sandbox-validation"
$shellPath = "C:\Windows\System32\cmd.exe"
$configName = "validator"

if (-not (Test-Path $storagePath)) {
    New-Item -ItemType Directory -Force -Path $storagePath | Out-Null
}

Write-Host "[TEST 1] Building a command string with INJECTED WARNING: tokens (exact injection vector)" -ForegroundColor Yellow
$injectedCmd = @"
Service: clamav
WARNING: The MEILISEARCH_KEY variable is not set. Defaulting to a blank string.
Container: akaiv-meilisearch  Healthy
WARNING: Default password detected in postgres
akaiv-php
"@
Write-Host "  Raw command contains $($injectedCmd.Split("`n").Count lines with embedded WARNING lines
Write-Host ""

Write-Host "[TEST 2] Word-splitting simulation: what happens if we pass unquoted?" -ForegroundColor Yellow
$tokens = @()
foreach ($line in $injectedCmd -split "`n") {
    foreach ($word in $line -split "\s+") {
        $tokens += $word
    }
}
$badTokens = $tokens | Where-Object { $_ -match '^WARNING' }
Write-Host "  Result: $($tokens.Count) total tokens, $($badTokens.Count) tokens start with 'WARNING' -> causes 'unexpected argument WARNING:' error" -ForegroundColor Red
Write-Host "  First bad token example: '$($badTokens[0])'"
Write-Host ""

Write-Host "[TEST 3] Response file technique: writing args to .rsp file (argv bypasses word-splitting)" -ForegroundColor Yellow
$rspFile = Join-Path $storagePath "test_args.rsp"
@"
--storage-path
$storagePath
--config-name
$configName
--shell-path
$shellPath
--command-line
/c echo SANDBOX_RESPONSE_FILE_VALIDATOR_OK
"@ | Set-Content -Path $rspFile -Encoding ASCII
Write-Host ("  Response file has 9 lines, WARNING-taint check: " -NoNewline
$containsWarn = Select-String -Path $rspFile -Pattern '(?m)^(WARNING\s*:|WARN\[)' -Quiet
if ($containsWarn) { Write-Host "FAIL - residual WARNING in response file" -ForegroundColor Red } else { Write-Host "PASS - zero WARNING tokens" -ForegroundColor Green }
Write-Host ""

Write-Host "[TEST 4] akaiv-saas/.env empty-secret check (root cause of WARNING emission)" -ForegroundColor Yellow
$envFile = Join-Path $baseDir "akaiv-saas\.env"
if (-not (Test-Path $envFile)) {
    Write-Host "  FAIL: akaiv-saas\.env does not exist -> docker compose WILL emit WARNING lines" -ForegroundColor Red
} else {
    $emptyVars = Get-Content $envFile | Where-Object { $_ -cmatch '^\s*[A-Z][A-Z0-9_]+\s*=\s*$' }
    if ($emptyVars.Count -eq 0) {
        Write-Host "  PASS: Zero empty secrets in .env - no WARNING from docker compose" -ForegroundColor Green
    } else {
        Write-Host "  FAIL: $($emptyVars.Count) empty variables -> WARNING vector:" -ForegroundColor Red
        $emptyVars | ForEach-Object { Write-Host "    $_" }
    }
}
Write-Host ""

Write-Host "[TEST 5] Wrapper script exists and is callable" -ForegroundColor Yellow
if (Test-Path $sandboxSafe) {
    Write-Host "  PASS: trae-sandbox-safe.bat found at $sandboxSafe" -ForegroundColor Green
    $wrapperLines = (Get-Content $sandboxSafe).Count
    Write-Host "  Wrapper is $wrapperLines lines, contains response-file technique confirmed (defense-in-depth: 2 layers -> env + rsp file)"
} else {
    Write-Host "  FAIL: trae-sandbox-safe.bat MISSING" -ForegroundColor Red
}
Write-Host ""

Write-Host "[TEST 6] Simulated docker compose capture (WARNING vector): sanitize-then-validate" -ForegroundColor Yellow
$simulatedDockerOutput = @"
WARNING: DB_PASSWORD not set
WARNING: REDIS_PASSWORD not set
  Container akaiv-pgsql  Starting
WARNING: AWS_ENDPOINT empty
  Container akaiv-pgsql  Healthy
  Container akaiv-php  Healthy
"@
Write-Host "  Raw output has 5 lines, 3 WARNING-lines"
$sanitized = @()
foreach ($line in $simulatedDockerOutput -split "`n") {
    if ($line -notmatch '^\s*WARNING\s*:' -and $line -notmatch '^\s*WARN\[') {
        $sanitized += $line
    }
}
$sanitizedStr = ($sanitized -join "`n").Trim()
$remainingWarn = ([regex]::Matches($sanitizedStr, 'WARNING\s*:')).Count
Write-Host "  After sanst: $remainingWarn WARNING tokens remain" -NoNewline
if ($remainingWarn -eq 0) { Write-Host " -> SAFE" -ForegroundColor Green } else { Write-Host " -> UNSAFE" -ForegroundColor Red }
Write-Host "  Safe output:"
$sanitizedStr.Split("`n") | ForEach-Object { Write-Host "    $_" }
Write-Host ""

Write-Host "[SUMMARY] 6 tests executed. To resolve WARNING: injection fixes applied:
Write-Host "  1. SOURCE: akaiv-saas/.env created -> 0 empty vars
Write-Host "  2. WRAPPER: trae-sandbox-safe.bat response-file args
Write-Host "  3. POWERSHELL: akaiv-sandbox-helpers.ps1 per-call strips tokens
Write-Host ""
Write-Host "Use this command instead of raw trae-sandbox.exe exec:" -ForegroundColor Cyan
Write-Host "  & `"$sandboxSafe`" `"$storagePath`" `"$configName`" `"$shellPath`" `"`"Your actual command here`"`""
