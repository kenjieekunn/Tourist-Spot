@echo off
REM Quick fix script for municipality images not displaying
REM Run this in the project root directory

echo.
echo ========================================
echo Municipality Images - Quick Fix
echo ========================================
echo.

cd admin-system

echo Step 1: Creating storage symlink...
php artisan storage:link
if %errorlevel% neq 0 (
    echo ERROR: Failed to create symlink
    pause
    exit /b 1
)
echo ✓ Storage symlink created

echo.
echo Step 2: Clearing cache...
php artisan cache:clear
php artisan config:clear
echo ✓ Cache cleared

echo.
echo Step 3: Checking storage directory...
if exist "storage\app\public\municipality-images" (
    echo ✓ Municipality images directory exists
    dir storage\app\public\municipality-images
) else (
    echo ! Municipality images directory not found
    echo   Creating directory...
    mkdir storage\app\public\municipality-images
    echo ✓ Directory created
)

echo.
echo Step 4: Checking symlink...
if exist "public\storage" (
    echo ✓ Symlink exists
) else (
    echo ! Symlink not found
    echo   Recreating...
    php artisan storage:link
)

echo.
echo ========================================
echo ✓ Fix Complete!
echo ========================================
echo.
echo Next steps:
echo 1. Go to Super Admin Dashboard
echo 2. Click Municipalities
echo 3. Click Edit on any municipality
echo 4. Upload an image
echo 5. Click Save
echo 6. Run Flutter app to see images
echo.
pause
