# LeanOn-Bot Machine Learning Service Runner
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

$SystemPython = "C:\Users\OKTOPOWER INC\AppData\Local\Programs\Python\Python311\python.exe"
if (-not (Test-Path $SystemPython)) {
    $SystemPython = "python"
}

$VenvPython = Join-Path $ScriptDir "venv\Scripts\python.exe"

if (-not (Test-Path $VenvPython)) {
    Write-Host "[ML Setup] Creating virtual environment in ml_service/venv..." -ForegroundColor Cyan
    & $SystemPython -m venv venv
}

Write-Host "[ML Setup] Installing dependencies..." -ForegroundColor Cyan
& $VenvPython -m pip install --quiet --upgrade pip
& $VenvPython -m pip install --quiet -r requirements.txt

$ModelDir = Join-Path $ScriptDir "models"
if (-not (Test-Path (Join-Path $ModelDir "domain_model.joblib"))) {
    Write-Host "[ML Setup] Training initial ML models..." -ForegroundColor Cyan
    & $VenvPython train.py
}

Write-Host "[ML Service] Starting FastAPI server on http://127.0.0.1:8001..." -ForegroundColor Green
& $VenvPython -m uvicorn app:app --host 127.0.0.1 --port 8001
