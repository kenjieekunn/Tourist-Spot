# Superadmin Login Fix Script for PowerShell
# Run this script from the admin-system directory

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Superadmin Login/Logout Error Fix" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "ERROR: artisan file not found!" -ForegroundColor Red
    Write-Host "Please run this script from the admin-system directory" -ForegroundColor Red
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Step 1: Run diagnostic check
Write-Host "Step 1: Running diagnostic check..." -ForegroundColor Yellow
Write-Host ""
php check_superadmin.php
Write-Host ""

# Step 2: Run quick fix script
Write-Host "Step 2: Running quick fix script..." -ForegroundColor Yellow
Write-Host ""
php fix_superadmin.php
$exitCode = $LASTEXITCODE

Write-Host ""

if ($exitCode -eq 0) {
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Fix completed successfully!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now login with:" -ForegroundColor Green
    Write-Host "  Email/Username: superadmin@gmail.com" -ForegroundColor Green
    Write-Host "  Password: superadmin@123" -ForegroundColor Green
    Write-Host ""
    Write-Host "Navigate to: http://localhost/admin-system/login" -ForegroundColor Green
    Write-Host ""
} else {
    Write-Host "========================================" -ForegroundColor Red
    Write-Host "ERROR: Fix failed!" -ForegroundColor Red
    Write-Host "========================================" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please check the error messages above." -ForegroundColor Red
    Write-Host ""
}

Read-Host "Press Enter to exit"
