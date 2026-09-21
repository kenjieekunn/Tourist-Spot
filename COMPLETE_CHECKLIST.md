# Complete Setup Checklist

## Phase 1: Preparation (5 minutes)

### Get Your Fingerprints
- [ ] Open terminal/command prompt
- [ ] Navigate to flutter-app directory
- [ ] Run `get_sha1_fingerprint.bat` (Windows) or `get_sha1_fingerprint.ps1` (PowerShell)
- [ ] Copy SHA-1 fingerprint (without colons)
- [ ] Copy Facebook Key Hash (if using openssl)
- [ ] Save both values in a text file

**Example values:**
```
SHA-1: ABCDEF1234567890ABCDEF1234567890ABCDEF12
Facebook Key Hash: abcDEF1234567890+/=
```

---

## Phase 2: Google Setup (10 minutes)

### Create Google Cloud Project
- [ ] Go to https://console.cloud.google.com/
- [ ] Click "Select a Project" → "New Project"
- [ ] Name: "M-Tour" (or your app name)
- [ ] Click "Create"
- [ ] Wait for project to be created

### Enable Google Sign-In API
- [ ] In Google Cloud Console, search for "Google Sign-In API"
- [ ] Click on it
- [ ] Click "Enable"
- [ ] Wait for it to enable

### Create OAuth 2.0 Credentials
- [ ] Go to "Credentials" in left menu
- [ ] Click "Create Credentials" → "OAuth 2.0 Client ID"
- [ ] Choose "Android"
- [ ] Fill in:
  - [ ] Package name: `com.example.tourist_spot_app`
  - [ ] SHA-1 certificate fingerprint: Paste your SHA-1 from Phase 1
- [ ] Click "Create"
- [ ] You'll see your Client ID (you can close this)

**Status:** ✓ Google is ready

---

## Phase 3: Facebook Setup (10 minutes)

### Create Facebook App
- [ ] Go to https://developers.facebook.com/
- [ ] Click "My Apps" → "Create App"
- [ ] Choose "Consumer" as app type
- [ ] Fill in:
  - [ ] App Name: "M-Tour"
  - [ ] App Contact Email: your-email@example.com
  - [ ] App Purpose: Choose appropriate category
- [ ] Click "Create App"
- [ ] Complete security check if prompted

### Add Android Platform
- [ ] In your Facebook App Dashboard
- [ ] Click "+ Add Platform"
- [ ] Choose "Android"
- [ ] Fill in:
  - [ ] Package Name: `com.example.tourist_spot_app`
  - [ ] Class Name: `com.example.tourist_spot_app.MainActivity`
  - [ ] Key Hashes: Paste your Facebook Key Hash from Phase 1
- [ ] Click "Save Changes"

### Get Your Credentials
- [ ] Go to "Settings" → "Basic"
- [ ] Copy your **App ID**
- [ ] Copy your **Client Token**
- [ ] Save both values

**Example:**
```
App ID: 1234567890123456
Client Token: abcdef1234567890abcdef1234567890
```

**Status:** ✓ Facebook is ready

---

## Phase 4: Update Flutter App (5 minutes)

### Update strings.xml
- [ ] Open `android/app/src/main/res/values/strings.xml`
- [ ] Replace `YOUR_FACEBOOK_APP_ID` with your App ID
- [ ] Replace `YOUR_FACEBOOK_CLIENT_TOKEN` with your Client Token
- [ ] Save file

**Example:**
```xml
<string name="facebook_app_id">1234567890123456</string>
<string name="facebook_client_token">abcdef1234567890abcdef1234567890</string>
```

### Verify AndroidManifest.xml
- [ ] Open `android/app/src/main/AndroidManifest.xml`
- [ ] Check that Facebook meta-data is present:
  ```xml
  <meta-data
      android:name="com.facebook.sdk.ApplicationId"
      android:value="@string/facebook_app_id" />
  ```
- [ ] Check that Facebook activities are present
- [ ] If missing, they should already be added (check SETUP_SUMMARY.md)

### Verify build.gradle.kts
- [ ] Open `android/app/build.gradle.kts`
- [ ] Check that `minSdk = 21` (or higher)
- [ ] If not, update it

**Status:** ✓ App is configured

---

## Phase 5: Build & Test (10 minutes)

### Clean and Rebuild
- [ ] Open terminal in flutter-app directory
- [ ] Run: `flutter clean`
- [ ] Wait for completion
- [ ] Run: `flutter pub get`
- [ ] Wait for dependencies to download

### Run the App
- [ ] Connect Android device or start emulator
- [ ] Run: `flutter run`
- [ ] Wait for app to build and launch
- [ ] App should start without errors

**Status:** ✓ App is running

---

## Phase 6: Test Login (5 minutes)

### Test Google Login
- [ ] In the app, navigate to any tourist spot
- [ ] Tap "Add Review"
- [ ] You should see login screen with two buttons
- [ ] Tap "Sign in with Google"
- [ ] Google sign-in dialog should appear
- [ ] Select your Google account
- [ ] You should be redirected to review form
- [ ] Your name should be auto-filled
- [ ] Name field should be read-only
- [ ] Green checkmark should appear next to name

**Status:** ✓ Google login works

### Test Facebook Login (Optional)
- [ ] Tap back to go to login screen
- [ ] Tap "Sign in with Facebook"
- [ ] Facebook login dialog should appear
- [ ] Enter your Facebook credentials
- [ ] You should be redirected to review form
- [ ] Your name should be auto-filled

**Status:** ✓ Facebook login works

### Test Session Persistence
- [ ] Close the app completely
- [ ] Reopen the app
- [ ] Navigate to "Add Review"
- [ ] You should NOT see login screen
- [ ] Review form should appear with name auto-filled
- [ ] This means session persisted

**Status:** ✓ Session persistence works

---

## Phase 7: Test Review Submission (5 minutes)

### Submit a Test Review
- [ ] Fill in the review form:
  - [ ] Rating: Select any rating
  - [ ] Comment: Type at least 10 characters
  - [ ] Photos: Optional
- [ ] Tap "Submit Review"
- [ ] You should see success message
- [ ] You should be redirected back

**Status:** ✓ Review submission works

### Verify Backend Received Data
- [ ] Check your Laravel backend logs
- [ ] Look for the review submission
- [ ] Verify `user_id` is present in the data
- [ ] Verify review is stored in database

**Status:** ✓ Backend integration works

---

## Phase 8: Backend Integration (30 minutes)

### Update Database
- [ ] Add `user_id` column to reviews table
- [ ] Create users table if not exists
- [ ] Run migrations

### Implement Auth Endpoints
- [ ] Create `POST /api/auth/google` endpoint
- [ ] Create `POST /api/auth/facebook` endpoint
- [ ] Implement token verification
- [ ] Implement user creation/update

### Update Review Endpoint
- [ ] Update `POST /api/reviews` to accept `user_id`
- [ ] Validate auth token
- [ ] Link review to user

### Add Configuration
- [ ] Add Google credentials to `.env`
- [ ] Add Facebook credentials to `.env`
- [ ] Update `config/services.php`

### Test Backend Endpoints
- [ ] Test Google auth endpoint
- [ ] Test Facebook auth endpoint
- [ ] Test review submission with user_id
- [ ] Verify data is stored correctly

**Status:** ✓ Backend is integrated

---

## Phase 9: Final Verification (5 minutes)

### Complete End-to-End Test
- [ ] Close app completely
- [ ] Clear app data (optional)
- [ ] Reopen app
- [ ] Navigate to review screen
- [ ] See login screen
- [ ] Sign in with Google
- [ ] Fill review form
- [ ] Submit review
- [ ] Check backend database
- [ ] Verify user_id is stored

**Status:** ✓ Complete flow works

### Check All Features
- [ ] [ ] Login screen appears when not logged in
- [ ] [ ] Google sign-in works
- [ ] [ ] Facebook login works
- [ ] [ ] Name auto-fills after login
- [ ] [ ] Name field is read-only
- [ ] [ ] Verified badge appears
- [ ] [ ] Review form shows when logged in
- [ ] [ ] Can submit review
- [ ] [ ] Session persists after app restart
- [ ] [ ] Backend receives user_id
- [ ] [ ] Reviews are linked to users

**Status:** ✓ All features working

---

## Phase 10: Production Preparation (Optional)

### Create Release Keystore
- [ ] Generate release keystore
- [ ] Get SHA-1 from release keystore
- [ ] Update Google Cloud Console with release SHA-1
- [ ] Update Facebook App with release key hash

### Update Credentials
- [ ] Create production Google credentials
- [ ] Create production Facebook credentials
- [ ] Add to `.env.production`
- [ ] Update app configuration

### Security Review
- [ ] Verify no credentials in git
- [ ] Verify HTTPS is used
- [ ] Verify tokens are validated
- [ ] Verify input is sanitized
- [ ] Verify rate limiting is implemented

**Status:** ✓ Ready for production

---

## Troubleshooting

### If Google Login Fails
- [ ] Check SHA-1 fingerprint is correct
- [ ] Verify it matches Google Cloud Console
- [ ] Run `flutter clean` and rebuild
- [ ] Check console logs: `flutter run -v`

### If Facebook Login Fails
- [ ] Check App ID and Client Token in strings.xml
- [ ] Verify they match Facebook app settings
- [ ] Check Key Hash is correct
- [ ] Run `flutter clean` and rebuild

### If Name Doesn't Auto-Fill
- [ ] Verify login was successful
- [ ] Check auth provider is initialized
- [ ] Check console logs for errors
- [ ] Try logging out and back in

### If Review Submission Fails
- [ ] Check backend is running
- [ ] Check API endpoint is correct
- [ ] Check user_id is being sent
- [ ] Check backend logs for errors

### If Session Doesn't Persist
- [ ] Check SharedPreferences is working
- [ ] Verify user data is being saved
- [ ] Check for storage permission issues
- [ ] Try clearing app data and relogging in

---

## Quick Reference

| Step | Time | Status |
|------|------|--------|
| Phase 1: Preparation | 5 min | ⏳ |
| Phase 2: Google Setup | 10 min | ⏳ |
| Phase 3: Facebook Setup | 10 min | ⏳ |
| Phase 4: Update App | 5 min | ⏳ |
| Phase 5: Build & Test | 10 min | ⏳ |
| Phase 6: Test Login | 5 min | ⏳ |
| Phase 7: Test Review | 5 min | ⏳ |
| Phase 8: Backend | 30 min | ⏳ |
| Phase 9: Final Test | 5 min | ⏳ |
| Phase 10: Production | Optional | ⏳ |
| **TOTAL** | **~85 min** | ⏳ |

---

## Success Criteria

✅ **You're done when:**
- [ ] App runs without errors
- [ ] Login screen appears
- [ ] Google login works
- [ ] Facebook login works
- [ ] Name auto-fills
- [ ] Review can be submitted
- [ ] Backend receives user_id
- [ ] Session persists
- [ ] All tests pass

---

## Next Steps

1. Start with Phase 1
2. Follow each phase in order
3. Check off items as you complete them
4. If stuck, check troubleshooting section
5. Refer to detailed guides for more info

**Good luck! 🚀**
