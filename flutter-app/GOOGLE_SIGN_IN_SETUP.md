# Google Sign-In Setup

## Google Cloud Console

Create an Android OAuth client for package `com.example.tourist_spot_app` and
register the SHA-1 fingerprints for debug and release signing. Also create a
Web application OAuth client. The app uses that Web client ID to request an ID
token for Laravel to verify.

## Flutter

The supplied Web client ID is configured as the app default, so run normally
from the `flutter-app` directory:

```powershell
flutter run
```

For a release build, the same default is used:

```powershell
flutter build apk --release
```

The ID is an OAuth client identifier, not a client secret. The Android package
name `com.example.tourist_spot_app` is a separate value and must match the
Android OAuth client configuration in Google Cloud Console.

## Laravel

Set `GOOGLE_CLIENT_IDS` in the deployed Laravel `.env` to the same Web client
ID. If the API accepts multiple client IDs, separate them with commas. Clear
the Laravel configuration cache after changing the value.

## Troubleshooting

- If the app says no ID token was returned, verify the Web client ID was passed
  through `GOOGLE_SERVER_CLIENT_ID` and rebuild the app.
- If the API reports an audience mismatch, make sure the Web client ID is in
  Laravel's `GOOGLE_CLIENT_IDS`.
- If Google rejects the Android app, check that its package name and signing
  certificate SHA-1 match the Android OAuth client.
- If the user cancels the Google account picker, the app remains on the sign-in
  screen and does not treat the cancellation as a successful login.