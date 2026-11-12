#Requires -Version 5.1
# WibuStore dev orchestrator: backend + storefront + admin trong CUNG 1 terminal.
# Chay duoc tren Windows / Linux / macOS (can pwsh 7).
# Makefile target "dev" goi script nay: scripts/dev.ps1 -ApiPort 8000 -StorePort 4321 -AdminPort 4322
# Nhan Ctrl+C de dung ca 3 (khoi finally se kill sach process con).
param(
  [int]$ApiPort = 8000,
  [int]$StorePort = 4321,
  [int]$AdminPort = 4322
)

$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $PSScriptRoot
$BackendDir = Join-Path $Root 'backend'
$FrontDir = Join-Path $Root 'frontend'
$AdminDir = Join-Path $Root 'frontend-admin'

function Kill-Port {
  param([int]$Port)
  if (Get-Command Get-NetTCPConnection -ErrorAction SilentlyContinue) {
    Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue | ForEach-Object {
      try {
        Stop-Process -Id $_.OwningProcess -Force -ErrorAction Stop
        Write-Host ("[dev] Killed port {0} (PID {1})" -f $Port, $_.OwningProcess)
      } catch { }
    }
    Start-Sleep -Seconds 1
  } else {
    $found = @(lsof -ti ":$Port" 2>$null)
    if ($found.Count -gt 0 -and $found[0]) { Stop-Process -Id $found -Force -ErrorAction SilentlyContinue }
  }
}

function Find-Bin {
  param([string[]]$Names, [string[]]$FallbackPaths = @())
  foreach ($n in $Names) {
    $c = Get-Command $n -ErrorAction SilentlyContinue
    if ($c -and $c.Source) { return $c.Source }
  }
  # PATH bi cu (terminal mo tu truoc khi cai dat): quet them vi tri mac dinh
  # cua winget (PHP) va npm global (pnpm/npx). Get-Item ho tro wildcard.
  foreach ($f in $FallbackPaths) {
    if (-not $f) { continue }
    $hit = Get-Item $f -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($hit) { return $hit.FullName }
  }
  return $null
}

# --- kiem tra moi truong (fail nhanh, message ro rang) ---
$PhpBin = Find-Bin @('php') @(
  (Join-Path $env:LOCALAPPDATA 'Microsoft\WinGet\Packages\PHP.PHP.8.3*\php.exe'),
  'C:\php\php.exe'
)
if (-not $PhpBin) { Write-Error 'Thieu PHP 8.3+ (cai tu https://windows.php.net/download/ va them vao PATH)'; exit 1 }
# Goi truc tiep astro binary (pnpm exec / npx): tranh khac biet truyen args
# giua `pnpm run` va `npm run`, va tranh lap --port o admin (package.json
# cua admin da pin --port 4322 trong script dev).
$UsePnpm = [bool](Get-Command pnpm -ErrorAction SilentlyContinue)
if (-not $UsePnpm) { $UsePnpm = [bool](Get-Item (Join-Path $env:APPDATA 'npm\pnpm.cmd') -ErrorAction SilentlyContinue) }
if ($UsePnpm) {
  $JsBin = Find-Bin @('pnpm.cmd', 'pnpm') @(
    (Join-Path $env:APPDATA 'npm\pnpm.cmd'),
    (Join-Path $env:ProgramFiles 'nodejs\pnpm.cmd')
  )
  if (-not $JsBin) { Write-Error 'Thieu pnpm (npm install -g pnpm)'; exit 1 }
  $AstroPrefix = 'exec astro dev'
} else {
  $JsBin = Find-Bin @('npx.cmd', 'npx') @(
    (Join-Path $env:APPDATA 'npm\npx.cmd'),
    'C:\Program Files\nodejs\npx.cmd',
    (Join-Path $env:ProgramFiles 'nodejs\npx.cmd')
  )
  if (-not $JsBin) { Write-Error 'Thieu pnpm/npx (cai Node 20+ tu https://nodejs.org/)'; exit 1 }
  $AstroPrefix = 'astro dev'
}
if (-not (Test-Path (Join-Path $BackendDir 'vendor/autoload.php'))) { Write-Error 'Chua cai vendor backend (chay make install truoc)'; exit 1 }
foreach ($d in @($FrontDir, $AdminDir)) {
  if (-not (Test-Path (Join-Path $d 'node_modules'))) { Write-Error ("Chua cai node_modules: $d (chay make install truoc)"); exit 1 }
}
# Dam bao shim pnpm.cmd/npx.cmd thay duoc `node` ke ca khi PATH cua terminal
# bi cu: chen thu muc chua node vao PATH rieng cua process con.
$NodeBin = Find-Bin @('node') @(
  'C:\Program Files\nodejs\node.exe',
  (Join-Path $env:ProgramFiles 'nodejs\node.exe')
)
$NodeDir = if ($NodeBin) { Split-Path -Parent $NodeBin } else { $null }

function Start-Svc {
  param([string]$File, [string]$Arguments, [string]$Dir, [hashtable]$ExtraEnv)
  $psi = New-Object System.Diagnostics.ProcessStartInfo
  $psi.FileName = $File
  $psi.Arguments = $Arguments
  $psi.WorkingDirectory = $Dir
  $psi.UseShellExecute = $false
  if ($ExtraEnv) { foreach ($k in $ExtraEnv.Keys) { $psi.EnvironmentVariables[$k] = $ExtraEnv[$k] } }
  $p = New-Object System.Diagnostics.Process
  $p.StartInfo = $psi
  if (-not $p.Start()) { throw "Khong start duoc: $File $Args" }
  return $p
}

$procs = @()
try {
  Kill-Port $ApiPort
  Kill-Port $StorePort
  Kill-Port $AdminPort

  $PublicApi = "http://localhost:$ApiPort"
  Write-Host ''
  Write-Host ("[dev] backend    http://localhost:{0} (php artisan serve)" -f $ApiPort) -ForegroundColor Cyan
  Write-Host ("[dev] storefront http://localhost:{0} -> API {1}" -f $StorePort, $PublicApi) -ForegroundColor Green
  Write-Host ("[dev] admin      http://localhost:{0} -> API {1}" -f $AdminPort, $PublicApi) -ForegroundColor Yellow
  Write-Host '[dev] Ca 3 services chay trong terminal nay. Nhan Ctrl+C de dung.' -ForegroundColor Gray
  Write-Host ''

  $procs += Start-Svc -File $PhpBin -Arguments ("artisan serve --host=0.0.0.0 --port={0}" -f $ApiPort) -Dir $BackendDir
  $fe = @{ 'PUBLIC_API_URL' = $PublicApi }
  if ($NodeDir) { $fe['PATH'] = "$NodeDir;$env:PATH" }
  $procs += Start-Svc -File $JsBin -Arguments ("{0} --port {1} --host 0.0.0.0" -f $AstroPrefix, $StorePort) -Dir $FrontDir -ExtraEnv $fe
  $procs += Start-Svc -File $JsBin -Arguments ("{0} --port {1} --host 0.0.0.0" -f $AstroPrefix, $AdminPort) -Dir $AdminDir -ExtraEnv $fe

  while ($true) {
    Start-Sleep -Milliseconds 500
    $dead = @($procs | Where-Object { $_.HasExited })
    if ($dead.Count -gt 0) {
      foreach ($d in $dead) {
        Write-Host ("[dev] process PID {0} da thoat (exit {1}) -> dung ca 3." -f $d.Id, $d.ExitCode) -ForegroundColor Red
      }
      exit 1
    }
  }
}
finally {
  foreach ($p in $procs) {
    try {
      if (-not $p.HasExited) {
        try { $p.Kill($true) } catch { $p.Kill() }
      }
    } catch { }
  }
}
