# PowerShell script to get SHA-1 fingerprint and Facebook key hash
# Run this in PowerShell as Administrator

Write-Host ""
Write-Host "========================================"
Write-Host "Android Authentication Setup Helper"
Write-Host "========================================"
Write-Host ""

# Check if keytool is available
try {
    $keytoolPath = (Get-Command keytool -ErrorAction Stop).Source
    Write-Host "✓ keytool found at: $keytoolPath" -ForegroundColor Green
} catch {
    Write-Host "✗ keytool not found. Make sure Java is installed." -ForegroundColor Red
    Write-Host ""
    Write-Host "To fix this:"
    Write-Host "1. Install Java Development Kit (JDK) from https://www.oracle.com/java/technologies/downloads/"
    Write-Host "2. Add Java bin folder to PATH environment variable"
    Write-Host "3. Restart PowerShell and run this script again"
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "Step 1: Getting SHA-1 Fingerprint"
Write-Host "=================================="
Write-Host ""

$keystorePath = "$env:USERPROFILE\.android\debug.keystore"

if (-not (Test-Path $keystorePath)) {
    Write-Host "✗ Debug keystore not found at: $keystorePath" -ForegroundColor Red
    Write-Host ""
    Write-Host "This usually means you haven't run a Flutter app yet."
    Write-Host "Run: flutter run"
    Write-Host "Then run this script again."
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ Debug keystore found" -ForegroundColor Green
Write-Host ""

# Get SHA-1 fingerprint
$sha1Output = & keytool -list -v -keystore $keystorePath -alias androiddebugkey -storepass android -keypass android 2>&1

# Extract SHA1 line
$sha1Line = $sha1Output | Select-String "SHA1:"
if ($sha1Line) {
    Write-Host "SHA-1 Fingerprint:" -ForegroundColor Cyan
    Write-Host $sha1Line -ForegroundColor Yellow
    Write-Host ""
    
    # Extract just the fingerprint without colons
    $sha1Value = ($sha1Line -split "SHA1: ")[1]
    $sha1Clean = $sha1Value -replace ":", ""
    
    Write-Host "SHA-1 (without colons) - Copy this for Google Cloud Console:" -ForegroundColor Cyan
    Write-Host $sha1Clean -ForegroundColor Yellow
    Write-Host ""
} else {
    Write-Host "✗ Could not extract SHA-1 fingerprint" -ForegroundColor Red
    Write-Host ""
    Write-Host "Full keytool output:"
    Write-Host $sha1Output
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "Step 2: Getting Facebook Key Hash"
Write-Host "=================================="
Write-Host ""

# Check if openssl is available
try {
    $opensslPath = (Get-Command openssl -ErrorAction Stop).Source
    Write-Host "✓ openssl found at: $opensslPath" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "Facebook Key Hash:" -ForegroundColor Cyan
    $cert = & keytool -exportcert -alias androiddebugkey -keystore $keystorePath -storepass android
    $keyHash = $cert | openssl sha1 -binary | openssl base64
    Write-Host $keyHash -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Copy this for Facebook App settings" -ForegroundColor Green
} catch {
    Write-Host "⚠ openssl not found - skipping Facebook key hash generation" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "To get Facebook key hash manually:"
    Write-Host "1. Install OpenSSL from https://slproweb.com/products/Win32OpenSSL.html"
    Write-Host "2. Run this script again"
    Write-Host ""
}

Write-Host ""
Write-Host "========================================"
Write-Host "Setup Instructions"
Write-Host "========================================"
Write-Host ""
Write-Host "For Google Sign-In:"
Write-Host "1. Go to https://console.cloud.google.com/"
Write-Host "2. Create a new project"
Write-Host "3. Enable Google Sign-In API"
Write-Host "4. Create OAuth 2.0 credentials for Android"
Write-Host "5. Use the SHA-1 fingerprint above"
Write-Host ""
Write-Host "For Facebook Login:"
Write-Host "1. Go to https://developers.facebook.com/"
Write-Host "2. Create a new app"
Write-Host "3. Add Android platform"
Write-Host "4. Use the Facebook Key Hash above"
Write-Host ""
Write-Host "========================================"
Write-Host ""

Read-Host "Press Enter to exit"
