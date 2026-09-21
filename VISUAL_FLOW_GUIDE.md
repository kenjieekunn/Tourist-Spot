# Authentication & Review Flow - Visual Guide

## 1. Login Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER OPENS REVIEW SCREEN                     │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ Is User Logged   │
                    │ In?              │
                    └──────────────────┘
                      │              │
                   NO │              │ YES
                      ▼              ▼
            ┌──────────────────┐  ┌──────────────────┐
            │ SHOW LOGIN       │  │ SHOW REVIEW FORM │
            │ SCREEN           │  │ (Name Auto-Fill) │
            └──────────────────┘  └──────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
        ▼                           ▼
    ┌────────────┐         ┌────────────────┐
    │ GOOGLE     │         │ FACEBOOK       │
    │ SIGN-IN    │         │ LOGIN          │
    └────────────┘         └────────────────┘
        │                           │
        └─────────────┬─────────────┘
                      ▼
        ┌──────────────────────────┐
        │ SEND TOKEN TO BACKEND    │
        └──────────────────────────┘
                      │
                      ▼
        ┌──────────────────────────┐
        │ BACKEND VERIFIES TOKEN   │
        │ & CREATES/UPDATES USER   │
        └──────────────────────────┘
                      │
                      ▼
        ┌──────────────────────────┐
        │ RETURN USER DATA & TOKEN │
        └──────────────────────────┘
                      │
                      ▼
        ┌──────────────────────────┐
        │ SAVE SESSION LOCALLY     │
        │ (SharedPreferences)      │
        └──────────────────────────┘
                      │
                      ▼
        ┌──────────────────────────┐
        │ RETURN TO REVIEW FORM    │
        │ (Name Auto-Filled)       │
        └──────────────────────────┘
```

---

## 2. Review Submission Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    USER FILLS REVIEW FORM                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ Rating: ★★★★★                                          │   │
│  │ Name: John Doe (Auto-filled, Read-only) ✓              │   │
│  │ Comment: "Amazing place to visit!"                     │   │
│  │ Photos: [photo1.jpg] [photo2.jpg]                      │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌──────────────────┐
                    │ USER TAPS SUBMIT │
                    └──────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ VALIDATE FORM                       │
        │ ✓ Rating: 1-5                       │
        │ ✓ Name: Not empty                   │
        │ ✓ Comment: Min 10 chars             │
        │ ✓ Photos: Max 5                     │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ PREPARE REQUEST DATA                │
        │ ┌─────────────────────────────────┐ │
        │ │ user_id: 123                    │ │
        │ │ user_name: "John Doe"           │ │
        │ │ rating: 5                       │ │
        │ │ comment: "Amazing place..."     │ │
        │ │ images: [file1, file2]          │ │
        │ │ auth_token: "google_token_xxx"  │ │
        │ └─────────────────────────────────┘ │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ SEND TO BACKEND                     │
        │ POST /api/reviews                   │
        │ Content-Type: multipart/form-data   │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ BACKEND RECEIVES REQUEST            │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ VALIDATE AUTH TOKEN                 │
        │ ✓ Token is valid                    │
        │ ✓ User exists                       │
        │ ✓ user_id matches token             │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ STORE REVIEW IN DATABASE            │
        │ ┌─────────────────────────────────┐ │
        │ │ id: 456                         │ │
        │ │ user_id: 123 ← LINKED TO USER   │ │
        │ │ spot_id: 789                    │ │
        │ │ user_name: "John Doe"           │ │
        │ │ rating: 5                       │ │
        │ │ comment: "Amazing place..."     │ │
        │ │ created_at: 2024-01-15          │ │
        │ └─────────────────────────────────┘ │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ STORE IMAGES (if any)               │
        │ ┌─────────────────────────────────┐ │
        │ │ review_id: 456                  │ │
        │ │ image_path: /reviews/img1.jpg   │ │
        │ └─────────────────────────────────┘ │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ RETURN SUCCESS RESPONSE             │
        │ {                                   │
        │   "success": true,                  │
        │   "message": "Review submitted",    │
        │   "data": { review object }         │
        │ }                                   │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ FLUTTER APP RECEIVES RESPONSE       │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ SHOW SUCCESS MESSAGE                │
        │ "Review submitted successfully!"    │
        └─────────────────────────────────────┘
                              │
                              ▼
        ┌─────────────────────────────────────┐
        │ NAVIGATE BACK TO SPOT DETAIL        │
        └─────────────────────────────────────┘
```

---

## 3. Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                         FLUTTER APP                              │
│                                                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Login Screen                                               │ │
│  │ ┌──────────────────┐  ┌──────────────────┐               │ │
│  │ │ Google Sign-In   │  │ Facebook Login   │               │ │
│  │ └──────────────────┘  └──────────────────┘               │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Auth Service                                               │ │
│  │ - Handles Google/Facebook login                            │ │
│  │ - Sends tokens to backend                                  │ │
│  │ - Stores session locally                                   │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Auth Provider (Riverpod)                                   │ │
│  │ - Manages current user state                               │ │
│  │ - Provides user data to screens                            │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Review Screen                                              │ │
│  │ - Checks if user is logged in                              │ │
│  │ - Auto-fills user name                                     │ │
│  │ - Collects review data                                     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
│                              ▼                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ API Service                                                │ │
│  │ - Sends review + user_id + auth_token                      │ │
│  │ - Handles response                                         │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              │                                   │
└──────────────────────────────┼───────────────────────────────────┘
                               │
                    ┌──────────┴──────────┐
                    │                     │
                    ▼                     ▼
        ┌─────────────────────┐  ┌──────────────────┐
        │ Google OAuth Server │  │ Facebook OAuth   │
        │ - Verifies token    │  │ - Verifies token │
        └─────────────────────┘  └──────────────────┘
                    │                     │
                    └──────────┬──────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │      BACKEND (Laravel API)           │
        │                                      │
        │  ┌────────────────────────────────┐ │
        │  │ Auth Endpoints                 │ │
        │  │ POST /api/auth/google          │ │
        │  │ POST /api/auth/facebook        │ │
        │  │ - Verify tokens                │ │
        │  │ - Create/update users          │ │
        │  │ - Return user data             │ │
        │  └────────────────────────────────┘ │
        │                                      │
        │  ┌────────────────────────────────┐ │
        │  │ Review Endpoints               │ │
        │  │ POST /api/reviews              │ │
        │  │ - Validate auth token          │ │
        │  │ - Store review with user_id    │ │
        │  │ - Store images                 │ │
        │  └────────────────────────────────┘ │
        │                                      │
        └──────────────────────────────────────┘
                               │
                               ▼
        ┌──────────────────────────────────────┐
        │      DATABASE (MySQL)                │
        │                                      │
        │  ┌────────────────────────────────┐ │
        │  │ users table                    │ │
        │  │ - id, name, email              │ │
        │  │ - provider, provider_id        │ │
        │  │ - token                        │ │
        │  └────────────────────────────────┘ │
        │                                      │
        │  ┌────────────────────────────────┐ │
        │  │ reviews table                  │ │
        │  │ - id, user_id (FK)             │ │
        │  │ - spot_id, rating, comment     │ │
        │  │ - created_at                   │ │
        │  └────────────────────────────────┘ │
        │                                      │
        │  ┌────────────────────────────────┐ │
        │  │ review_images table            │ │
        │  │ - id, review_id (FK)           │ │
        │  │ - image_path                   │ │
        │  └────────────────────────────────┘ │
        │                                      │
        └──────────────────────────────────────┘
```

---

## 4. Session Management

```
┌─────────────────────────────────────────────────────────────┐
│                    APP LIFECYCLE                            │
└─────────────────────────────────────────────────────────────┘

APP STARTS
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Check SharedPreferences for saved session                   │
└─────────────────────────────────────────────────────────────┘
    │
    ├─ Session found ──────────────────┐
    │                                  │
    │                                  ▼
    │                    ┌──────────────────────────┐
    │                    │ Load user from storage   │
    │                    │ Set authUserProvider     │
    │                    │ User is logged in ✓      │
    │                    └──────────────────────────┘
    │
    └─ No session ──────────────────┐
                                    │
                                    ▼
                    ┌──────────────────────────┐
                    │ authUserProvider = null  │
                    │ User is not logged in    │
                    └──────────────────────────┘

USER LOGS IN
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Auth Service calls backend                                  │
│ Backend returns user data + token                           │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Save to SharedPreferences:                                  │
│ - User data (JSON)                                          │
│ - Auth token                                                │
└─────────────────────────────────────────────────────────────┘
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Update authUserProvider                                     │
│ All screens can now access user data                        │
└─────────────────────────────────────────────────────────────┘

USER LOGS OUT (if implemented)
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Sign out from Google/Facebook                               │
│ Clear SharedPreferences                                     │
│ Set authUserProvider = null                                 │
└─────────────────────────────────────────────────────────────┘

APP RESTARTS
    │
    ▼
┌─────────────────────────────────────────────────────────────┐
│ Check SharedPreferences again                               │
│ User is still logged in (session persists)                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 5. Error Handling Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    ERROR SCENARIOS                          │
└─────────────────────────────────────────────────────────────┘

GOOGLE LOGIN FAILS
    │
    ├─ User cancelled ──────────────────────────────────────┐
    │                                                        │
    │                                                        ▼
    │                                    ┌──────────────────────────┐
    │                                    │ Return to login screen   │
    │                                    │ No error message         │
    │                                    └──────────────────────────┘
    │
    ├─ Invalid token ───────────────────────────────────────┐
    │                                                        │
    │                                                        ▼
    │                                    ┌──────────────────────────┐
    │                                    │ Show error message       │
    │                                    │ "Google sign-in failed"  │
    │                                    │ Allow retry              │
    │                                    └──────────────────────────┘
    │
    └─ Network error ───────────────────────────────────────┐
                                                             │
                                                             ▼
                                    ┌──────────────────────────┐
                                    │ Show error message       │
                                    │ "Network error"          │
                                    │ Allow retry              │
                                    └──────────────────────────┘

REVIEW SUBMISSION FAILS
    │
    ├─ Validation error ────────────────────────────────────┐
    │                                                        │
    │                                                        ▼
    │                                    ┌──────────────────────────┐
    │                                    │ Show validation error    │
    │                                    │ "Comment too short"      │
    │                                    │ Allow user to fix        │
    │                                    └──────────────────────────┘
    │
    ├─ Auth token expired ──────────────────────────────────┐
    │                                                        │
    │                                                        ▼
    │                                    ┌──────────────────────────┐
    │                                    │ Show error message       │
    │                                    │ "Session expired"        │
    │                                    │ Redirect to login        │
    │                                    └──────────────────────────┘
    │
    └─ Network error ───────────────────────────────────────┐
                                                             │
                                                             ▼
                                    ┌──────────────────────────┐
                                    │ Show error message       │
                                    │ "Failed to submit"       │
                                    │ Allow retry              │
                                    └──────────────────────────┘
```

---

## 6. Security Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY CHECKS                          │
└─────────────────────────────────────────────────────────────┘

FRONTEND (Flutter)
    │
    ├─ Validate form data
    │  ├─ Rating: 1-5
    │  ├─ Name: Not empty
    │  ├─ Comment: Min 10 chars
    │  └─ Photos: Max 5
    │
    ├─ Check user is logged in
    │  └─ authUserProvider != null
    │
    └─ Include auth token in request
       └─ Authorization header

BACKEND (Laravel)
    │
    ├─ Verify auth token
    │  ├─ Token is valid
    │  ├─ Token not expired
    │  └─ Token matches user
    │
    ├─ Validate user_id
    │  ├─ user_id exists in database
    │  ├─ user_id matches token
    │  └─ User has permission
    │
    ├─ Validate review data
    │  ├─ Rating: 1-5
    │  ├─ Comment: Min 10 chars
    │  ├─ Images: Valid files
    │  └─ Spot exists
    │
    └─ Store securely
       ├─ Use prepared statements
       ├─ Sanitize input
       ├─ Hash sensitive data
       └─ Log audit trail

DATABASE
    │
    └─ Enforce constraints
       ├─ Foreign keys
       ├─ NOT NULL constraints
       ├─ Check constraints
       └─ Unique constraints
```

---

## Summary

This visual guide shows:
1. **Login Flow** - How users authenticate
2. **Review Submission** - How reviews are submitted
3. **Data Flow** - How data moves through the system
4. **Session Management** - How sessions persist
5. **Error Handling** - How errors are managed
6. **Security** - How data is protected

All components work together to create a secure, user-friendly authentication system.
