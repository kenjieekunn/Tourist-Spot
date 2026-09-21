@echo off
REM Profile Editing Fix Script for Windows
REM Run this script from the admin-system directory

echo.
echo ========================================
echo Profile Editing Error Fix
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
php check_profile_issues.php
echo.

echo Step 2: Running fix script...
echo.
php fix_profile_issues.php
echo.

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Fix completed successfully!
    echo ========================================
    echo.
    echo Profile editing should now work!
    echo.
    echo You can now:
    echo   1. Login as superadmin
    echo   2. Click profile icon in top right
    echo   3. Upload a profile photo
    echo   4. Change your password
    echo   5. Click Save Changes
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
