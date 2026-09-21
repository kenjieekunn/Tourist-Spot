# Quick Start: Google & Facebook Authentication Setup

## 🚀 TL;DR - Do This First

### 1. Get Your Fingerprints (5 minutes)

**On Windows:**
- Double-click: `get_sha1_fingerprint.bat` (or `.ps1` for PowerShell)
- Copy the SHA-1 and Facebook Key Hash values

**On Mac/Linux:**
```bash
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android
```

### 2. Set Up Google (10 minutes)

1. Go to https://console.cloud.google.com/
2. Create new project → "M-Tour"
3. Search for "Google Sign-In API" → Enable it
4. Go to Credentials → Create OAuth 2.0 Client ID → Android
5. Enter:
   - Package: `com.example.tourist_spot_app`
   - SHA-1: Your fingerprint from step 1
6. Done! Google is ready

### 3. Set Up Facebook (10 minutes)

1. Go to https://developers.facebook.com/
2. My Apps → Create App → Consumer
3. Name: "M-Tour"
4. Add Platform → Android
5. Enter:
   - Package: `com.example.tourist_spot_app`
   - Class: `com.example.tourist_spot_app.MainActivity`
   - Key Hash: Your Facebook key hash from step 1
6. Go to Settings → Basic
7. Copy your **App ID** and **Client Token**

### 4. Update Your App (2 minutes)

Edit `android/app/src/main/res/values/strings.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">M-Tour</string>
    <string name="facebook_app_id">PASTE_YOUR_APP_ID_HERE</string>
    <string name="facebook_client_token">PASTE_YOUR_CLIENT_TOKEN_HERE</string>
</resources>
```

### 5. Run the App (5 minutes)

```bash
cd flutter-app
flutter clean
flutter pub get
flutter run
```

---

## ✅ Verification Checklist

- [ ] SHA-1 fingerprint obtained
- [ ] Google Cloud project created
- [ ] Google Sign-In API enabled
- [ ] OAuth 2.0 credentials created with correct SHA-1
- [ ] Facebook app created
- [ ] Android platform added to Facebook app
- [ ] Facebook App ID and Client Token copied
- [ ] `strings.xml` updated with Facebook credentials
- [ ] `flutter clean` run
- [ ] App runs without errors
- [ ] Can tap "Add Review" without crashes
- [ ] Login screen appears with Google and Facebook buttons

---

## 🧪 Testing

1. **Open the app**
2. **Tap "Add Review"** on any tourist spot
3. **Should see login screen** with two buttons
4. **Tap "Sign in with Google"**
   - Should open Google sign-in dialog
   - Select your Google account
   - Should return to review form with name auto-filled
5. **Tap "Sign in with Facebook"** (if you want to test)
   - Should open Facebook login dialog
   - Enter Facebook credentials
   - Should return to review form with name auto-filled

---

## 🐛 Common Issues

### "Sign-in failed" or "Configuration problem"
- **Fix**: SHA-1 fingerprint doesn't match
- **Solution**: 
  1. Run `get_sha1_fingerprint.bat` again
  2. Update Google Cloud Console with new SHA-1
  3. Run `flutter clean` and rebuild

### "App not set up" (Facebook)
- **Fix**: App ID or Client Token is wrong
- **Solution**:
  1. Double-check `strings.xml` values
  2. Verify they match Facebook app settings
  3. Rebuild app

### App crashes on login
- **Fix**: Missing permissions or configuration
- **Solution**:
  1. Check console: `flutter run -v`
  2. Verify `AndroidManifest.xml` has Facebook activities
  3. Ensure `minSdk = 21` in `build.gradle.kts`

### Name doesn't auto-fill after login
- **Fix**: Auth provider not initialized
- **Solution**:
  1. Check that you're logged in (should see verified badge)
  2. Check console logs for errors
  3. Try logging out and back in

---

## 📚 Full Documentation

For detailed setup instructions, see: `GOOGLE_FACEBOOK_SETUP.md`

---

## 🔐 Security Reminders

⚠️ **Important for Production:**
- Never commit real App IDs or tokens to git
- Use environment variables for production builds
- Create a release keystore (not debug keystore)
- Get SHA-1 from release keystore
- Update Google/Facebook settings with release credentials

---

## 📞 Need Help?

1. Check `GOOGLE_FACEBOOK_SETUP.md` for detailed troubleshooting
2. Review console logs: `flutter run -v`
3. Verify all credentials are correct
4. Try `flutter clean && flutter pub get && flutter run`

---

## 🎯 What's Next?

After setup is complete:
1. Test the full review flow
2. Submit a review and check backend logs
3. Verify `user_id` is being stored in database
4. Add logout functionality (optional)
5. Deploy to production with release credentials
