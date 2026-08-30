function Invoke-SanitizedDocker {
    param(
        [Parameter(Mandatory = $true, Position = 0)]
        [string]$SubCommand,
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$RemainingArgs
    )
    $ErrorActionPreference = 'Stop'
    $output = & docker $SubCommand @RemainingArgs 2>&1
    $lines = @()
    $warnings = @()
    foreach ($o in $output) {
        $s = [string]$o
        if ($s -match '^\s*WARNING\s*:') {
            $warnings += $s
            continue
        }
        if ($s -match '^\s*WARN\s*\[') {
            $warnings += $s
            continue
        }
        $lines += $s
    }
    if ($warnings.Count -gt 0) {
        $warnings | ForEach-Object { Write-Host "[docker-warning-silenced] $_" -ForegroundColor DarkYellow }
    }
    return ($lines -join "`n").Trim()
}

function Invoke-SanitizedDockerCompose {
    param(
        [Parameter(Mandatory = $true, Position = 0)]
        [string]$SubCommand,
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]]$RemainingArgs
    )
    $ErrorActionPreference = 'Stop'
    $output = & docker compose $SubCommand @RemainingArgs 2>&1
    $lines = @()
    $warnings = @()
    foreach ($o in $output) {
        $s = [string]$o
        if ($s -match '^\s*WARNING\s*:') {
            $warnings += $s
            continue
        }
        if ($s -match '^\s*WARN\s*\[') {
            $warnings += $s
            continue
        }
        $lines += $s
    }
    if ($warnings.Count -gt 0) {
        $warnings | ForEach-Object { Write-Host "[compose-warning-silenced] $_" -ForegroundColor DarkYellow }
    }
    return ($lines -join "`n").Trim()
}

function Invoke-TraeSandboxSafe {
    param(
        [Parameter(Mandatory = $true)]
        [string]$StoragePath,
        [Parameter(Mandatory = $true)]
        [string]$ConfigName,
        [Parameter(Mandatory = $true)]
        [string]$ShellPath,
        [Parameter(Mandatory = $true)]
        [string]$CommandLine
    )
    $ErrorActionPreference = 'Stop'
    $cleanStorage = [string]$StoragePath
    $cleanConfig = [string]$ConfigName
    $cleanShell = [string]$ShellPath
    $cleanCmd = [string]$CommandLine
    if ($cleanCmd -match 'WARNING:') {
        Write-Host "[sandbox-guard] Stripped WARNING: tokens from command-line before passing to trae-sandbox.exe" -ForegroundColor DarkYellow
        $cleanCmd = $cleanCmd -replace '(?m)^\s*WARNING\s*:.*$', ''
        $cleanCmd = $cleanCmd.Trim()
    }
    & trae-sandbox.exe exec `
        --storage-path $cleanStorage `
        --config-name $cleanConfig `
        --shell-path $cleanShell `
        --command-line $cleanCmd
    return $LASTEXITCODE
}

Set-Alias -Name docker-safe -Value Invoke-SanitizedDocker -Scope Global
Set-Alias -Name compose-safe -Value Invoke-SanitizedDockerCompose -Scope Global
Set-Alias -Name sandbox-safe -Value Invoke-TraeSandboxSafe -Scope Global

Write-Host "[akaiv-sandbox-helpers] Loaded. Use: docker-safe, compose-safe, sandbox-safe" -ForegroundColor Green
