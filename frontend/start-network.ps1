$ip = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue |
    Where-Object {
        $_.IPAddress -and $_.IPAddress -notmatch '^127\.0\.0\.1$' -and $_.IPAddress -notmatch '^169\.254\.'
    } |
    Select-Object -First 1 -ExpandProperty IPAddress

if ([string]::IsNullOrWhiteSpace([string]$ip)) {
    $ip = '127.0.0.1'
}

$vitePort = Get-NetTCPConnection -LocalPort 5173 -State Listen -ErrorAction SilentlyContinue
$nodeCommand = Get-Command node -ErrorAction SilentlyContinue
$viteEntry = Join-Path $PSScriptRoot 'node_modules/vite/bin/vite.js'
if (-not $vitePort -and $nodeCommand -and (Test-Path $viteEntry)) {
    $env:VITE_DEV_SERVER_ORIGIN = "http://${ip}:5173"
    $env:VITE_APP_ORIGIN = "http://${ip}:8000"
    Start-Process -FilePath $nodeCommand.Source -ArgumentList @($viteEntry, '--host', '0.0.0.0', '--port', '5173', '--strictPort') -WorkingDirectory $PSScriptRoot
    Write-Host "Vite iniciado en http://${ip}:5173" -ForegroundColor Green
}

$repoRoot = Split-Path -Parent $PSScriptRoot
$apiDir = Join-Path $repoRoot 'api'
$pythonOnPath = Get-Command python -ErrorAction SilentlyContinue
$pythonCandidates = @(
    (Join-Path $apiDir '.venv\Scripts\python.exe'),
    (Join-Path $apiDir 'venv\Scripts\python.exe'),
    $(if ($pythonOnPath) { $pythonOnPath.Source })
) | Where-Object { $_ -and (Test-Path $_) }
$pythonPath = $null

foreach ($candidate in $pythonCandidates) {
    & $candidate -c "import flask, flask_sqlalchemy, flask_cors, geopandas, serial, requests, pymysql" *> $null
    if ($LASTEXITCODE -eq 0) {
        $pythonPath = $candidate
        break
    }
}

if ($pythonPath) {
    $apiListening = Get-NetTCPConnection -LocalPort 5000 -State Listen -ErrorAction SilentlyContinue
    if (-not $apiListening) {
        Start-Process -FilePath $pythonPath -ArgumentList @('-u', 'app.py') -WorkingDirectory $apiDir
        Write-Host 'API Flask iniciada en http://localhost:5000' -ForegroundColor Green
    }

    $serialRunning = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -and $_.CommandLine -like '*serial_sender.py*' }
    if (-not $serialRunning) {
        Start-Process -FilePath $pythonPath -ArgumentList @('-u', 'serial_sender.py') -WorkingDirectory $apiDir
        Write-Host 'Lector LoRa iniciado; detectará automáticamente el puerto USB.' -ForegroundColor Green
    }
} else {
    Write-Warning "No encontré un Python con las dependencias de la API. Instálalas con: python -m pip install -r `"$apiDir\requirements.txt`""
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host " CanSat Ground Station" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Accede desde cualquier computadora en la misma red con:" -ForegroundColor Yellow
Write-Host "http://${ip}:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Iniciando servidor Laravel en 0.0.0.0:8000..." -ForegroundColor Yellow
Write-Host ""

$env:APP_URL = "http://${ip}:8000"
$webListening = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
if ($webListening) {
    Write-Host "Laravel ya está activo en http://${ip}:8000" -ForegroundColor Green
} else {
    php artisan serve --host 0.0.0.0 --port 8000
}
