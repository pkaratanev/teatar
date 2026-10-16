# Teatar on Windows: installs what's missing, sets up the backend on SQLite,
# and starts the API and the Expo dev server. No Docker or Postgres needed.
#
# Run in PowerShell:
#   irm https://raw.githubusercontent.com/pkaratanev/teatar/main/scripts/windows.ps1 | iex
#
# Safe to run again: it skips what is already done, pulls the latest code and
# starts both servers. The project lives in %USERPROFILE%\Projects\teatar.

$ErrorActionPreference = 'Stop'
$Repo = Join-Path $HOME 'Projects\teatar'
$HerdBin = Join-Path $HOME '.config\herd\bin'

function Step($message) { Write-Host "`n==> $message" -ForegroundColor Cyan }

function Refresh-Path {
    $env:Path = @(
        [Environment]::GetEnvironmentVariable('Path', 'Machine'),
        [Environment]::GetEnvironmentVariable('Path', 'User'),
        $HerdBin
    ) -join ';'
}

function Has($command) { [bool](Get-Command $command -ErrorAction SilentlyContinue) }

# Native commands don't throw in Windows PowerShell; check their exit code.
function Run([string]$what, [scriptblock]$block) {
    & $block
    if ($LASTEXITCODE -ne 0) { throw "$what failed (exit code $LASTEXITCODE). Scroll up for the error." }
}

function Need($command, $wingetId, $label, $hint) {
    Refresh-Path
    if (Has $command) { return }
    if (-not (Has 'winget')) { throw "winget is missing. Install 'App Installer' from the Microsoft Store, then run this again." }
    Step "Installing $label (Windows may ask for permission)"
    winget install --id $wingetId --exact --accept-source-agreements --accept-package-agreements
    Refresh-Path
    if (-not (Has $command)) { throw "$label is installed but '$command' isn't available yet. $hint Then open a new PowerShell window and run the same command again." }
}

function Write-Utf8($path, [string[]]$lines) {
    [IO.File]::WriteAllLines($path, $lines, (New-Object Text.UTF8Encoding $false))
}

# --- 1. Tools ---------------------------------------------------------------
Step 'Checking tools'
Need 'git' 'Git.Git' 'Git' ''
Need 'node' 'OpenJS.NodeJS.LTS' 'Node.js LTS' ''
Need 'php' 'BeyondCode.Herd' 'Laravel Herd (PHP + Composer)' 'Open the Herd app once from the Start menu so it finishes setting up PHP.'
Need 'composer' 'BeyondCode.Herd' 'Laravel Herd (PHP + Composer)' 'Open the Herd app once from the Start menu.'

$phpVersion = (& php -r 'echo PHP_VERSION;')
if ([version]$phpVersion -lt [version]'8.3') { throw "PHP $phpVersion found; Teatar needs 8.3 or newer. In Herd, switch the global PHP version to 8.3+." }
if (-not ((& php -m) -contains 'pdo_sqlite')) { throw "PHP $phpVersion has no pdo_sqlite extension. Enable it in php.ini (or use Herd's PHP)." }
Write-Host "git, node $(node -v), PHP $phpVersion, composer: OK"

# --- 2. Code ----------------------------------------------------------------
if (Test-Path (Join-Path $Repo '.git')) {
    Step 'Pulling the latest code'
    git -C $Repo pull --ff-only
    if ($LASTEXITCODE -ne 0) { Write-Warning 'Could not pull (local changes?). Continuing with the code as it is.' }
} else {
    Step "Downloading the code to $Repo"
    New-Item -ItemType Directory -Force (Split-Path $Repo) | Out-Null
    Run 'git clone' { git clone https://github.com/pkaratanev/teatar.git $Repo }
}

# The phone reaches this PC over Wi-Fi, so the app needs the LAN address, not localhost.
$net = Get-NetIPConfiguration | Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } | Select-Object -First 1
$ip = if ($net) { @($net.IPv4Address)[0].IPAddress } else { $null }
if (-not $ip) { throw 'No network connection found. Connect this PC to the same Wi-Fi/router as your phone and run again.' }

# --- 3. Backend -------------------------------------------------------------
$backend = Join-Path $Repo 'backend'
Set-Location $backend
$setupDone = Join-Path $backend 'storage\app\.windows-setup-done'

if (-not (Test-Path '.env')) {
    # Same settings as .env.example, but SQLite instead of Postgres.
    $lines = @(Get-Content '.env.example' -Encoding UTF8 | Where-Object { $_ -notmatch '^\s*#?\s*DB_' }) + 'DB_CONNECTION=sqlite'
    Write-Utf8 (Join-Path $backend '.env') $lines
}
# Image URLs must point at this PC's address so the phone can load them.
$envLines = @(Get-Content '.env' -Encoding UTF8 | ForEach-Object { if ($_ -match '^APP_URL=') { "APP_URL=http://${ip}:8000" } else { $_ } })
Write-Utf8 (Join-Path $backend '.env') $envLines

if (-not (Test-Path 'database\database.sqlite')) { New-Item -ItemType File 'database\database.sqlite' | Out-Null }

if (-not (Test-Path $setupDone)) {
    Step 'First-time backend setup (takes a few minutes)'
    Run 'composer setup' { composer setup --no-interaction }
    Step 'Loading the real programme from the theatre sites and demo users'
    Run 'teatar:demo' { php artisan teatar:demo --no-interaction }
    New-Item -ItemType File $setupDone -Force | Out-Null
} else {
    Step 'Updating backend dependencies and database'
    Run 'composer install' { composer install --no-interaction }
    Run 'migrate' { php artisan migrate --force --no-interaction }
}

$listening = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($listening) {
    Write-Host 'The API is already running on port 8000; leaving it as it is.'
} else {
    Step 'Starting the API in a new window (keep it open)'
    Start-Process powershell -ArgumentList '-NoExit', '-Command', "Set-Location '$backend'; php artisan serve --host=0.0.0.0 --port=8000"
}

# --- 4. App -----------------------------------------------------------------
$mobile = Join-Path $Repo 'mobile'
Set-Location $mobile
Write-Utf8 (Join-Path $mobile '.env') @("EXPO_PUBLIC_API_URL=http://${ip}:8000/api/v1")

Step 'Installing app dependencies'
# npm.cmd / npx.cmd, not npm / npx: those resolve to npm.ps1, which Windows' default
# execution policy refuses to run.
Run 'npm install' { npm.cmd install --no-audit --no-fund }

Write-Host ''
Write-Host '------------------------------------------------------------------' -ForegroundColor Green
Write-Host " Admin:  http://localhost:8000/admin   (admin@teatar.test / password)"
Write-Host " API for the phone: http://${ip}:8000/api/v1"
Write-Host ''
Write-Host ' If Windows asks whether PHP or Node may use the network, allow PRIVATE networks.'
Write-Host ' Phone: install Expo Go, join the same Wi-Fi, scan the QR code below'
Write-Host ' (Android: in Expo Go; iPhone: with the Camera app).'
Write-Host ' Sign in as admin@teatar.test / password.'
Write-Host ' Stop: Ctrl+C here, and close the API window.'
Write-Host '------------------------------------------------------------------' -ForegroundColor Green
Write-Host ''

npx.cmd expo start
