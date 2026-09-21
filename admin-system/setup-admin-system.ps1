# Super Admin System Setup Script
# This script automates the setup of the new admin system
# Run this from the admin-system directory

Write-Host "======================================" -ForegroundColor Cyan
Write-Host "Super Admin System Setup" -ForegroundColor Cyan
Write-Host "======================================" -ForegroundColor Cyan
Write-Host ""

# Check if we're in the right directory
if (-not (Test-Path "artisan")) {
    Write-Host "ERROR: artisan not found. Please run this script from the admin-system directory." -ForegroundColor Red
    exit 1
}

Write-Host "Step 1: Clearing cache..." -ForegroundColor Yellow
php artisan cache:clear
php artisan config:clear
Write-Host "✓ Cache cleared" -ForegroundColor Green
Write-Host ""

Write-Host "Step 2: Running database migration..." -ForegroundColor Yellow
php artisan migrate
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Migration failed" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Database schema updated" -ForegroundColor Green
Write-Host ""

Write-Host "Step 3: Seeding admin users..." -ForegroundColor Yellow
php artisan db:seed --class=AdminSeeder
if ($LASTEXITCODE -ne 0) {
    Write-Host "✗ Seeding failed" -ForegroundColor Red
    exit 1
}
Write-Host "✓ Admin users created" -ForegroundColor Green
Write-Host ""

Write-Host "======================================" -ForegroundColor Green
Write-Host "Setup Complete!" -ForegroundColor Green
Write-Host "======================================" -ForegroundColor Green
Write-Host ""
Write-Host "Default Admin Credentials:" -ForegroundColor Cyan
Write-Host ""
Write-Host "Super Admin:" -ForegroundColor White
Write-Host "  Email: superadmin@tourist-spots.com" -ForegroundColor White
Write-Host "  Password: SuperAdmin@123" -ForegroundColor White
Write-Host ""
Write-Host "Municipality Admins:" -ForegroundColor White
Write-Host "  Email Pattern: [municipality]_admin@tourist-spots.com" -ForegroundColor White
Write-Host "  Password: MuniAdmin@123" -ForegroundColor White
Write-Host ""
Write-Host "Examples:" -ForegroundColor Gray
Write-Host "  - lingayen_admin@tourist-spots.com" -ForegroundColor Gray
Write-Host "  - binmaley_admin@tourist-spots.com" -ForegroundColor Gray
Write-Host "  - urbiztondo_admin@tourist-spots.com" -ForegroundColor Gray
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Cyan
Write-Host "1. Start your Laravel server: php artisan serve" -ForegroundColor White
Write-Host "2. Open browser to: http://localhost:8000/login" -ForegroundColor White
Write-Host "3. Log in with one of the credentials above" -ForegroundColor White
Write-Host ""
Write-Host "Documentation: See ADMIN_SYSTEM_SETUP.md for detailed information" -ForegroundColor Cyan
Write-Host ""
