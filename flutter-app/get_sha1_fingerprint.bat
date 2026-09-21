@echo off
REM Script to get SHA-1 fingerprint for Google Sign-In setup
REM Run this in Command Prompt or PowerShell

echo.
echo ========================================
echo Getting SHA-1 Fingerprint for Android
echo ========================================
echo.

REM Check if keytool is available
where keytool >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: keytool not found. Make sure Java is installed and in PATH.
    echo.
    echo To fix this:
    echo 1. Install Java Development Kit (JDK)
    echo 2. Add Java bin folder to PATH environment variable
    echo 3. Restart this script
    pause
    exit /b 1
)

echo Running keytool command...
echo.

keytool -list -v -keystore %USERPROFILE%\.android\debug.keystore -alias androiddebugkey -storepass android -keypass android

echo.
echo ========================================
echo Look for the line starting with "SHA1:"
echo Copy the fingerprint (without colons)
echo Example: ABCDEF1234567890ABCDEF1234567890ABCDEF12
echo ========================================
echo.
pause
