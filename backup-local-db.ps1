# Quick Local MySQL Backup Script for LeanOn-Bot
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = "$PSScriptRoot\backups"

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir | Out-Null
}

$dumpFile = "$backupDir\leanon_bot_$timestamp.sql"
$mysqldump = "C:\Users\OKTOPOWER INC\Desktop\ira\xampp folder\mysql\bin\mysqldump.exe"

if (Test-Path $mysqldump) {
    Write-Host "Backing up local database 'leanon_bot' to $dumpFile..." -ForegroundColor Cyan
    & $mysqldump -u root leanon_bot > $dumpFile
    Write-Host "Backup completed successfully! ($dumpFile)" -ForegroundColor Green
} else {
    Write-Host "Error: mysqldump.exe not found at $mysqldump." -ForegroundColor Red
}
