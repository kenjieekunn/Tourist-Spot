# Google & Facebook Authentication Setup Guide

## Overview
This guide walks you through setting up Google Sign-In and Facebook Login for your Flutter app.

---

## Part 1: Google Sign-In Setup

### Step 1: Get Your Android SHA-1 Fingerprint

Run this command in your terminal:

```bash
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

**On Windows (PowerShell):**
```powershell
keytool -list -v -keystore $env:USERPROFILE\.android\debug.keystore -alias androiddebugkey -storepass android -keypass android
```

Look for the line that says `SHA1:` - copy this value (without the colons).

Example output:
```
SHA1: AB:CD:EF:12:34:56:78:90:AB:CD:EF:12:34:56:78:90:AB:CD:EF:12
```

Copy as: `ABCDEF1234567890ABCDEF1234567890ABCDEF12`

### Step 2: Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project (name it "M-Tour" or similar)
3. Enable the **Google Sign-In API**:
   - Search for "Google+ API" or "Google Sign-In API"
   - Click "Enable"

### Step 3: Create OAuth 2.0 Credentials

1. Go to **Credentials** in the left menu
2. Click **Create Credentials** → **OAuth 2.0 Client ID**
3. Choose **Android**
4. Fill in:
   - **Package name**: `com.example.tourist_spot_app`
   - **SHA-1 certificate fingerprint**: Paste your SHA-1 from Step 1
5. Click **Create**
6. You'll see your **Client ID** - copy it

### Step 4: Update Android Configuration

Update `android/app/build.gradle.kts`:

```kotlin
defaultConfig {
    applicationId = "com.example.tourist_spot_app"
    minSdk = 21  // Important: minimum 21 for Google Sign-In
    targetSdk = flutter.targetSdkVersion
    versionCode = flutter.versionCode
    versionName = flutter.versionName
}
```

### Step 5: Configure google-services.json (Optional but Recommended)

1. In Google Cloud Console, go to **Credentials**
2. Find your OAuth 2.0 Client ID for Android
3. Download the `google-services.json` file
4. Place it in: `android/app/google-services.json`

---

## Part 2: Facebook Login Setup

### Step 1: Create Facebook App

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Click **My Apps** → **Create App**
3. Choose **Consumer** as the app type
4. Fill in:
   - **App Name**: M-Tour
   - **App Contact Email**: your-email@example.com
   - **App Purpose**: Choose appropriate category
5. Click **Create App**

### Step 2: Get Your App ID and Client Token

1. In your Facebook App Dashboard, go to **Settings** → **Basic**
2. Copy your **App ID** and **Client Token**
3. Keep these safe - you'll need them

### Step 3: Add Android Platform

1. In your Facebook App, click **+ Add Platform**
2. Choose **Android**
3. Fill in:
   - **Package Name**: `com.example.tourist_spot_app`
   - **Class Name**: `com.example.tourist_spot_app.MainActivity`
   - **Key Hashes**: Your SHA-1 fingerprint (convert to Base64)

### Step 4: Convert SHA-1 to Base64 Key Hash

Run this command:

```bash
keytool -exportcert -alias androiddebugkey -keystore ~/.android/debug.keystore | openssl sha1 -binary | openssl base64
```

**On Windows (PowerShell):**
```powershell
$cert = & keytool -exportcert -alias androiddebugkey -keystore $env:USERPROFILE\.android\debug.keystore -storepass android
$cert | openssl sha1 -binary | openssl base64
```

This will output something like: `abcDEF1234567890+/=`

Paste this into the **Key Hashes** field in Facebook App settings.

### Step 5: Update strings.xml

Edit `android/app/src/main/res/values/strings.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">M-Tour</string>
    <string name="facebook_app_id">YOUR_FACEBOOK_APP_ID</string>
    <string name="facebook_client_token">YOUR_FACEBOOK_CLIENT_TOKEN</string>
</resources>
```

Replace:
- `YOUR_FACEBOOK_APP_ID` with your App ID from Step 2
- `YOUR_FACEBOOK_CLIENT_TOKEN` with your Client Token from Step 2

---

## Part 3: Verify Setup

### Check AndroidManifest.xml

Your `android/app/src/main/AndroidManifest.xml` should have:

```xml
<!-- Facebook Configuration -->
<meta-data
    android:name="com.facebook.sdk.ApplicationId"
    android:value="@string/facebook_app_id" />
<meta-data
    android:name="com.facebook.sdk.ClientToken"
    android:value="@string/facebook_client_token" />

<!-- Facebook Login Activities -->
<activity
    android:name="com.facebook.FacebookActivity"
    android:configChanges="keyboard|keyboardHidden|screenLayout|screenSize|orientation"
    android:label="@string/app_name" />
<activity
    android:name="com.facebook.CustomTabActivity"
    android:exported="true">
    <intent-filter>
        <action android:name="android.intent.action.VIEW" />
        <category android:name="android.intent.category.DEFAULT" />
        <category android:name="android.intent.category.BROWSABLE" />
        <data android:scheme="fb@string/facebook_app_id" />
    </intent-filter>
</activity>
```

---

## Part 4: Test the Setup

### Run the App

```bash
cd flutter-app
flutter clean
flutter pub get
flutter run
```

### Test Google Sign-In

1. Navigate to a review screen
2. Tap "Add Review"
3. Tap "Sign in with Google"
4. You should see the Google sign-in dialog
5. Select your Google account
6. You should be logged in

### Test Facebook Login

1. Navigate to a review screen
2. Tap "Add Review"
3. Tap "Sign in with Facebook"
4. You should see the Facebook login dialog
5. Enter your Facebook credentials
6. You should be logged in

---

## Troubleshooting

### "Sign-in failed" Error

**Cause**: SHA-1 fingerprint doesn't match
**Solution**: 
1. Verify your SHA-1 fingerprint is correct
2. Update it in Google Cloud Console
3. Run `flutter clean` and rebuild

### Facebook Login Shows "App Not Set Up"

**Cause**: App ID or Client Token is incorrect
**Solution**:
1. Double-check your App ID and Client Token in `strings.xml`
2. Verify they match your Facebook App settings
3. Make sure Key Hash is correct

### "Package name mismatch"

**Cause**: Package name in Google/Facebook settings doesn't match your app
**Solution**:
1. Check `android/app/build.gradle.kts` for `applicationId`
2. Update Google Cloud Console and Facebook App settings to match
3. Rebuild the app

### Login Works but Name Doesn't Auto-Fill

**Cause**: Auth provider not properly initialized
**Solution**:
1. Check that `authUserProvider` is being watched in AddReviewScreen
2. Verify `_loadUserName()` is called after login
3. Check console logs for errors

---

## Security Notes

✅ **Good Practices**:
- Never commit real App IDs or tokens to version control
- Use environment variables for production
- Validate tokens on backend
- Use HTTPS for all API calls

⚠️ **For Production**:
- Create a release keystore (not debug keystore)
- Get SHA-1 from release keystore
- Update Google Cloud Console with release SHA-1
- Update Facebook App settings with release key hash
- Use ProGuard/R8 for code obfuscation

---

## Files Modified

- ✅ `android/app/src/main/res/values/strings.xml` (CREATED)
- ✅ `android/app/src/main/AndroidManifest.xml` (UPDATED)
- ✅ `android/app/build.gradle.kts` (No changes needed - already correct)

---

## Next Steps

1. Complete all steps above
2. Run `flutter clean && flutter pub get`
3. Test both Google and Facebook login
4. Verify user name auto-fills in review form
5. Test review submission with auth data

---

## Quick Reference

| Service | What You Need | Where to Get It |
|---------|---------------|-----------------|
| Google | Client ID | Google Cloud Console |
| Google | SHA-1 Fingerprint | `keytool` command |
| Facebook | App ID | Facebook Developers |
| Facebook | Client Token | Facebook Developers |
| Facebook | Key Hash | `keytool` + `openssl` |

---

## Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review the console logs: `flutter run -v`
3. Verify all credentials are correct
4. Try `flutter clean` and rebuild
5. Check that minSdk is at least 21
