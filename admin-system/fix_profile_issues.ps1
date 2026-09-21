# Profile Editing Fix Script for PowerShell
# Run this script from the admin-system directory

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Profile Editing Error Fix" -ForegroundColor Cyan
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
php check_profile_issues.php
Write-Host ""

# Step 2: Run fix script
Write-Host "Step 2: Running fix script..." -ForegroundColor Yellow
Write-Host ""
php fix_profile_issues.php
$exitCode = $LASTEXITCODE

Write-Host ""

if ($exitCode -eq 0) {
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "Fix completed successfully!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Profile editing should now work!" -ForegroundColor Green
    Write-Host ""
    Write-Host "You can now:" -ForegroundColor Green
    Write-Host "  1. Login as superadmin" -ForegroundColor Green
    Write-Host "  2. Click profile icon in top right" -ForegroundColor Green
    Write-Host "  3. Upload a profile photo" -ForegroundColor Green
    Write-Host "  4. Change your password" -ForegroundColor Green
    Write-Host "  5. Click Save Changes" -ForegroundColor Green
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
