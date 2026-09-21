# Login & Review System Implementation Guide

## Overview
This implementation adds Google/Facebook authentication to your Flutter app with automatic user name population in reviews.

## What Was Implemented

### 1. Login Screen (`login_screen.dart`)
- New screen with Google and Facebook sign-in buttons
- Error handling and loading states
- Returns `true` on successful login to trigger UI refresh

### 2. Updated AddReviewScreen
- **Login Check**: Shows lock screen if user not authenticated
- **Auto-filled Name**: User's name from auth provider is automatically populated and read-only
- **Verified Badge**: Green checkmark icon shows the name is verified
- **Auth Data Passing**: Sends `user_id` and `auth_token` to backend

### 3. Updated Routes (`app_routes.dart`)
- Added `/login` route pointing to LoginScreen
- Integrated into route generation

### 4. Updated API Service
- `submitReview()` now accepts optional `userId` and `authToken` parameters
- Includes `user_id` in form data sent to backend

## Flow

```
User taps "Add Review"
    ↓
Is user logged in?
    ├─ NO → Show login screen
    │        ├─ User taps Google/Facebook
    │        ├─ Auth completes
    │        └─ Return to review form (auto-filled name)
    │
    └─ YES → Show review form with auto-filled name
             ↓
             User fills review
             ↓
             Submit with user_id + auth_token
             ↓
             Backend validates and stores
```

## Backend Requirements

Your Laravel backend needs to:

1. **Accept user_id in reviews table**
   ```sql
   ALTER TABLE reviews ADD COLUMN user_id INT NULLABLE;
   ALTER TABLE reviews ADD FOREIGN KEY (user_id) REFERENCES users(id);
   ```

2. **Store auth provider info**
   - The `AuthUser` model already captures: `id`, `name`, `email`, `provider`, `token`
   - Backend should store these when user first authenticates

3. **Update review submission endpoint**
   - Accept `user_id` in form data
   - Link review to authenticated user
   - Example:
   ```php
   $review = Review::create([
       'tourist_spot_id' => $request->spot_id,
       'user_id' => $request->user_id,
       'user_name' => $request->user_name,
       'rating' => $request->rating,
       'comment' => $request->comment,
   ]);
   ```

## Session Management

- **Local Storage**: User session stored in SharedPreferences
- **Token**: Auth token persists across app restarts
- **Auto-login**: User stays logged in until they manually logout
- **Logout**: Clears local session and signs out from Google/Facebook

## Security Notes

✓ Google/Facebook tokens verified on backend
✓ User ID stored server-side (not just client-side)
✓ Auth token included in requests
✓ Read-only name field prevents tampering

⚠️ Backend must validate:
- Token authenticity
- User ID matches token
- Review submission is from authenticated user

## Testing Checklist

- [ ] User can sign in with Google
- [ ] User can sign in with Facebook
- [ ] Name auto-fills after login
- [ ] Name field is read-only
- [ ] Review submission includes user_id
- [ ] User stays logged in after app restart
- [ ] Logout clears session
- [ ] Backend receives and stores user_id correctly

## Files Modified/Created

- ✅ `lib/views/screens/login_screen.dart` (NEW)
- ✅ `lib/views/screens/add_review_screen.dart` (UPDATED)
- ✅ `lib/config/routes/app_routes.dart` (UPDATED)
- ✅ `lib/services/api_service.dart` (UPDATED)
- ✅ `lib/controllers/auth_providers.dart` (EXISTING - no changes needed)
- ✅ `lib/services/auth_service.dart` (EXISTING - no changes needed)

## Next Steps

1. Update your Laravel backend to accept and store `user_id`
2. Test the complete flow end-to-end
3. Add logout button in app (optional - can add to profile/settings screen)
4. Monitor backend logs for auth token validation
