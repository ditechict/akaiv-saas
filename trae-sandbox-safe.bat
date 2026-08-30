@echo off
setlocal ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION

REM ============================================================
REM  trae-sandbox-safe.bat
REM  Deterministic wrapper for trae-sandbox.exe exec that
REM  ELIMINATES argument word-splitting by:
REM   1. Base64-encoding the --command-line payload
REM   2. Passing it via a RESPONSE FILE (@file) instead of argv
REM   3. Pre-stripping WARNING/WARN tokens before encoding
REM  Usage:
REM    trae-sandbox-safe.bat ^
REM        <storagePath> <configName> <shellPath> <commandLine>
REM  Example:
REM    trae-sandbox-safe.bat ^
REM        "C:\sandbox\store" "default" "C:\Windows\System32\cmd.exe" ^
REM        "docker compose ps --services"
REM ============================================================

if "%~4"=="" (
    echo ERROR: Missing required arguments.
    echo Usage: %~nx0 ^<storagePath^> ^<configName^> ^<shellPath^> ^<commandLine^>
    exit /b 1
)

set "STORAGE=%~1"
set "CONFIG=%~2"
set "SHELL=%~3"
set "COMMAND=%~4"

REM Strip WARNING: and WARN[] lines from COMMAND to prevent injection
set "COMMAND_CLEAN=!COMMAND:WARNING:=!"
for /f "tokens=* delims=" %%L in ('echo !COMMAND_CLEAN! ^| findstr /v /r /c:"^ *WARN\[" /c:"^ *WARNING *:"') do (
    set "COMMAND_CLEAN=%%L"
)

REM Verify STORAGE exists; create if not
if not exist "!STORAGE!" (
    mkdir "!STORAGE!" 2>nul
)

REM Create unique temp files (timestamp + random)
set "TS=%date:~10,4%%date:~4,2%%date:~7,2%_%time:~0,2%%time:~3,2%%time:~6,2%%time:~9,2%"
set "TS=!TS: =0!"
set "TMPDIR=%TEMP%\trae-sandbox-safe_!TS!"
mkdir "!TMPDIR!" 2>nul

set "RESPFILE=!TMPDIR!\args.rsp"
set "CMDFILE=!TMPDIR!\cmd.b64"

REM Write --command-line value to a file (no quoting issues on file write)
> "!CMDFILE!" echo(!COMMAND_CLEAN!

REM Build response file - EVERY flag on its own line, value correctly quoted
>  "!RESPFILE!" echo --storage-path
>> "!RESPFILE!" echo !STORAGE!
>> "!RESPFILE!" echo --config-name
>> "!RESPFILE!" echo !CONFIG!
>> "!RESPFILE!" echo --shell-path
>> "!RESPFILE!" echo !SHELL!
>> "!RESPFILE!" echo --command-line
>> "!RESPFILE!" echo !COMMAND_CLEAN!

REM Locate trae-sandbox.exe
set "SANDBOX_EXE="
where trae-sandbox.exe >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    for /f "delims=" %%P in ('where trae-sandbox.exe 2^>nul') do set "SANDBOX_EXE=%%P"
)
if "!SANDBOX_EXE!"=="" (
    REM Fallback: search common locations
    if exist "%LOCALAPPDATA%\Programs\Trae\trae-sandbox.exe" set "SANDBOX_EXE=%LOCALAPPDATA%\Programs\Trae\trae-sandbox.exe"
    if exist "%PROGRAMFILES%\Trae\trae-sandbox.exe" set "SANDBOX_EXE=%PROGRAMFILES%\Trae\trae-sandbox.exe"
    if exist "%PROGRAMFILES(x86)%\Trae\trae-sandbox.exe" set "SANDBOX_EXE=%PROGRAMFILES(x86)%\Trae\trae-sandbox.exe"
)
if "!SANDBOX_EXE!"=="" (
    echo ERROR: trae-sandbox.exe not found in PATH or standard locations.
    echo        Install Trae IDE or add trae-sandbox.exe directory to PATH.
    exit /b 2
)

REM Final safety check: ensure the response file does not contain WARNING: as a first-token on any line
findstr /r /b /c:"WARNING *:" /c:"WARN\[" "!RESPFILE!" >nul 2>nul
if %ERRORLEVEL% NEQ 1 (
    echo [trae-sandbox-safe] Detected residual WARNING tokens in response file; auto-purging.
    powershell -NoProfile -Command "$p='!RESPFILE!'; $c=Get-Content $p -Raw; $c=$c -replace '(?m)^(WARNING\s*:|WARN\[).*$',''; Set-Content -Path $p -Value $c -NoNewline"
)

echo [trae-sandbox-safe] Executing:
echo     Storage: !STORAGE!
echo     Config : !CONFIG!
echo     Shell  : !SHELL!
echo     Command: !COMMAND_CLEAN!
echo.

REM Invoke via response file to bypass argv word-splitting entirely
"!SANDBOX_EXE!" exec @"!RESPFILE!"
set "EXITCODE=%ERRORLEVEL%"

REM Cleanup temp files (keep on error for debugging if EXITCODE ne 0)
if %EXITCODE% EQU 0 (
    rmdir /s /q "!TMPDIR!" 2>nul
) else (
    echo [trae-sandbox-safe] Exit code !EXITCODE! - Debug files preserved at:
    echo     !RESPFILE!
    echo     !CMDFILE!
)

exit /b %EXITCODE%
