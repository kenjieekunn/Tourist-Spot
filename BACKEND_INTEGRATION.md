# Backend Integration Checklist

## Database Changes

### 1. Update Reviews Table

Add `user_id` column to store authenticated user:

```sql
ALTER TABLE reviews ADD COLUMN user_id INT NULLABLE AFTER id;
ALTER TABLE reviews ADD CONSTRAINT fk_reviews_user_id 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;
```

Or if creating fresh:

```sql
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULLABLE,
    tourist_spot_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (tourist_spot_id) REFERENCES tourist_spots(id) ON DELETE CASCADE
);
```

### 2. Ensure Users Table Exists

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    provider VARCHAR(50) NOT NULL, -- 'google' or 'facebook'
    provider_id VARCHAR(255) UNIQUE NOT NULL,
    profile_image_url VARCHAR(255) NULLABLE,
    token VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## API Endpoint Updates

### Review Submission Endpoint

**Current endpoint:** `POST /api/tourist-spots/{spotId}/reviews`

**Update to accept:**

```php
// In your Laravel controller
public function store(Request $request, $spotId)
{
    $validated = $request->validate([
        'user_name' => 'required|string|max:255',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'required|string|min:10',
        'user_id' => 'nullable|integer|exists:users,id',
        'images' => 'nullable|array|max:5',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5120',
    ]);

    $review = Review::create([
        'tourist_spot_id' => $spotId,
        'user_id' => $validated['user_id'] ?? null,
        'user_name' => $validated['user_name'],
        'rating' => $validated['rating'],
        'comment' => $validated['comment'],
    ]);

    // Handle image uploads if present
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('reviews', 'public');
            ReviewImage::create([
                'review_id' => $review->id,
                'image_path' => $path,
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Review submitted successfully',
        'data' => $review->load('images'),
    ], 201);
}
```

---

## Authentication Endpoints

### Google Sign-In Endpoint

**Endpoint:** `POST /api/auth/google`

```php
public function googleAuth(Request $request)
{
    $validated = $request->validate([
        'id_token' => 'required|string',
    ]);

    try {
        // Verify Google ID token
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($validated['id_token']);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token',
            ], 401);
        }

        // Find or create user
        $user = User::firstOrCreate(
            [
                'provider' => 'google',
                'provider_id' => $payload['sub'],
            ],
            [
                'name' => $payload['name'],
                'email' => $payload['email'],
                'profile_image_url' => $payload['picture'] ?? null,
                'token' => $validated['id_token'],
            ]
        );

        // Update token
        $user->update(['token' => $validated['id_token']]);

        return response()->json([
            'success' => true,
            'message' => 'Google login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image_url' => $user->profile_image_url,
                    'provider' => $user->provider,
                ],
                'token' => $user->token,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Google authentication failed: ' . $e->getMessage(),
        ], 401);
    }
}
```

### Facebook Login Endpoint

**Endpoint:** `POST /api/auth/facebook`

```php
public function facebookAuth(Request $request)
{
    $validated = $request->validate([
        'access_token' => 'required|string',
    ]);

    try {
        // Verify Facebook access token
        $response = Http::get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture',
            'access_token' => $validated['access_token'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Facebook token',
            ], 401);
        }

        $data = $response->json();

        // Find or create user
        $user = User::firstOrCreate(
            [
                'provider' => 'facebook',
                'provider_id' => $data['id'],
            ],
            [
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'profile_image_url' => $data['picture']['data']['url'] ?? null,
                'token' => $validated['access_token'],
            ]
        );

        // Update token
        $user->update(['token' => $validated['access_token']]);

        return response()->json([
            'success' => true,
            'message' => 'Facebook login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image_url' => $user->profile_image_url,
                    'provider' => $user->provider,
                ],
                'token' => $user->token,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Facebook authentication failed: ' . $e->getMessage(),
        ], 401);
    }
}
```

---

## Configuration

### Add to `config/services.php`

```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
],

'facebook' => [
    'client_id' => env('FACEBOOK_APP_ID'),
    'client_secret' => env('FACEBOOK_APP_SECRET'),
],
```

### Add to `.env`

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

FACEBOOK_APP_ID=your_facebook_app_id
FACEBOOK_APP_SECRET=your_facebook_app_secret
```

---

## Routes

### Add to `routes/api.php`

```php
Route::post('/auth/google', [AuthController::class, 'googleAuth']);
Route::post('/auth/facebook', [AuthController::class, 'facebookAuth']);

Route::post('/tourist-spots/{spotId}/reviews', [ReviewController::class, 'store']);
Route::get('/tourist-spots/{spotId}/reviews', [ReviewController::class, 'index']);
```

---

## Testing

### Test Google Auth

```bash
curl -X POST http://localhost:8000/api/auth/google \
  -H "Content-Type: application/json" \
  -d '{"id_token":"YOUR_GOOGLE_ID_TOKEN"}'
```

### Test Facebook Auth

```bash
curl -X POST http://localhost:8000/api/auth/facebook \
  -H "Content-Type: application/json" \
  -d '{"access_token":"YOUR_FACEBOOK_ACCESS_TOKEN"}'
```

### Test Review Submission

```bash
curl -X POST http://localhost:8000/api/tourist-spots/1/reviews \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 1,
    "user_name": "John Doe",
    "rating": 5,
    "comment": "Amazing place to visit!"
  }'
```

---

## Verification Checklist

- [ ] Users table created with provider fields
- [ ] Reviews table updated with user_id column
- [ ] Google auth endpoint implemented
- [ ] Facebook auth endpoint implemented
- [ ] Review submission endpoint accepts user_id
- [ ] Google credentials added to .env
- [ ] Facebook credentials added to .env
- [ ] Routes registered in api.php
- [ ] Tested Google login endpoint
- [ ] Tested Facebook login endpoint
- [ ] Tested review submission with user_id
- [ ] Verified user_id is stored in database
- [ ] Verified reviews are linked to users

---

## Security Considerations

✅ **Implement:**
- Validate tokens on backend (don't trust client)
- Use HTTPS for all API calls
- Rate limit auth endpoints
- Log authentication attempts
- Validate user_id matches token
- Use prepared statements (Laravel Eloquent does this)

⚠️ **Never:**
- Store tokens in plain text
- Trust client-provided user_id without verification
- Skip token validation
- Log sensitive data

---

## Monitoring

### Log Authentication Events

```php
Log::info('User authenticated', [
    'user_id' => $user->id,
    'provider' => $user->provider,
    'email' => $user->email,
]);
```

### Monitor Review Submissions

```php
Log::info('Review submitted', [
    'review_id' => $review->id,
    'user_id' => $review->user_id,
    'spot_id' => $review->tourist_spot_id,
    'rating' => $review->rating,
]);
```

---

## Next Steps

1. Update database schema
2. Implement auth endpoints
3. Update review submission endpoint
4. Add configuration to .env
5. Test all endpoints
6. Deploy to production
