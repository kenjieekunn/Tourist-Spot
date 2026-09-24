@echo off
REM Superadmin Login Fix Script for Windows
REM Run this script from the admin-system directory

echo.
echo ========================================
echo Superadmin Login/Logout Error Fix
echo ========================================
echo.

REM Check if we're in the right directory
if not exist "artisan" (
    echo ERROR: artisan file not found!
    echo Please run this script from the admin-system directory
    echo.
    pause
    exit /b 1
)

echo Step 1: Running diagnostic check...
echo.
php check_superadmin.php
echo.

echo Step 2: Running quick fix script...
echo.
php fix_superadmin.php
echo.

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Fix completed successfully!
    echo ========================================
    echo.
    echo You can now login with:
    echo   Email/Username: superadmin@gmail.com
    echo   Password: superadmin@123
    echo.
    echo Navigate to: http://localhost/admin-system/login
    echo.
) else (
    echo.
    echo ========================================
    echo ERROR: Fix failed!
    echo ========================================
    echo.
    echo Please check the error messages above.
    echo.
)

pause
