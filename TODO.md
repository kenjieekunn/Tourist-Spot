# Social Auth + Login-Required Review Implementation

## Backend Tasks (API-only, no web system changes)
- [x] Create TODO.md
- [x] Migration: add `auth_provider`, `provider_id`, `profile_image_url`, `api_token` to users table
- [x] Migration: add `user_id` foreign key to reviews table
- [x] Update `User` model: add fillable fields, add `reviews()` relation
- [x] Update `Review` model: add `user_id` fillable, add `user()` relation
- [x] Create `SocialAuthController` (Api namespace) — Google + Facebook login, custom token creation
- [x] Update `routes/api.php` — add auth endpoints, protect review POST route
- [x] Update `TouristSpotApiController::addReview` — require Bearer token auth, store `user_id`, auto-fill name
- [x] Update `TouristSpotApiController::transformReview` — include user profile image from relation
- [x] Run migrations

## Flutter Tasks
- [ ] Update `pubspec.yaml` — add `google_sign_in`, `flutter_facebook_auth`
- [ ] Create `AuthUser` model (`lib/models/auth_user_model.dart`)
- [ ] Create `AuthService` (`lib/services/auth_service.dart`) — Google/Facebook sign-in, local session
- [ ] Create auth Riverpod providers (`lib/controllers/auth_providers.dart`)
- [ ] Create `LoginScreen` (`lib/views/screens/login_screen.dart`)
- [ ] Update `app_routes.dart` — add `/login` route
- [ ] Update `ApiService` — attach Bearer token, remove manual `userName` from `submitReview`
- [ ] Update `AddReviewScreen` — login gate, auto-fill display name from auth profile
- [ ] Update `TouristSpotDetailScreen` — show profile image in reviews if available
- [ ] Run `flutter pub get`

## Testing
- [ ] Test Google Sign-In flow
- [ ] Test review submission as authenticated user
- [ ] Verify `user_id` stored in reviews table
- [ ] Verify unauthenticated review POST returns 401


