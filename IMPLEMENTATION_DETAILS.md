# Implementation Details - Favorites & Map System

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Flutter App                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  TouristSpotsListScreen                              │  │
│  │  - Displays grid of tourist spots                    │  │
│  │  - Heart button for each spot                        │  │
│  │  - Calls _toggleFavorite() on tap                    │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ApiService.toggleSpotFavorite(spotId)               │  │
│  │  1. Sync auth token from SharedPreferences           │  │
│  │  2. POST to /api/v1/spots/{spotId}/favorite          │  │
│  │  3. Return response with is_favorited status         │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ref.invalidate(touristSpotsByMunicipalityStream)    │  │
│  │  - Triggers stream provider to re-fetch data         │  │
│  │  - Stream fetches fresh data from API                │  │
│  │  - API returns spots with updated is_favorited       │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  UI Updates                                          │  │
│  │  - Heart icon fills/empties                          │  │
│  │  - Snackbar shows success message                    │  │
│  │  - List refreshes with new data                      │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel API                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  POST /api/v1/spots/{spotId}/favorite                │  │
│  │  - ApiTokenAuth middleware authenticates user        │  │
│  │  - Looks up user by api_token                        │  │
│  │  - Sets $request->user() for controller              │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  TouristSpotApiController.toggleFavorite()           │  │
│  │  1. Get authenticated user from $request->user()     │  │
│  │  2. Find existing favorite record                    │  │
│  │  3. Delete if exists, create if not                  │  │
│  │  4. Return is_favorited status                       │  │
│  └──────────────────────────────────────────────────────┘  │
│                          │                                   │
│                          ▼                                   │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Database                                            │  │
│  │  - Insert/Delete from tourist_spot_favorites         │  │
│  │  - Unique constraint on (user_id, tourist_spot_id)   │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Data Flow - Favorite Toggle

### Step 1: User Clicks Heart Button
```dart
// In _buildFavoriteButton()
InkWell(
  onTap: isLoading ? null : () => _handleFavoritePressed(spot, context),
  child: Icon(
    spot.isFavorited ? Icons.favorite : Icons.favorite_border,
    color: spot.isFavorited ? Colors.redAccent : Colors.white,
  ),
)
```

### Step 2: Handle Favorite Press
```dart
Future<void> _handleFavoritePressed(TouristSpot spot, BuildContext context) async {
  final currentUser = ref.read(authUserProvider);
  await _toggleFavorite(spot, context, isLoggedIn: currentUser != null);
}
```

### Step 3: Toggle Favorite
```dart
Future<void> _toggleFavorite(TouristSpot spot, BuildContext context, {bool isLoggedIn = false}) async {
  setState(() {
    _updatingFavorites.add(spot.id);  // Show loading spinner
  });

  try {
    bool newFavoriteState = !spot.isFavorited;  // Calculate new state
    
    if (isLoggedIn) {
      final apiService = ref.read(apiServiceProvider);
      await apiService.toggleSpotFavorite(spot.id);  // Call API
    } else {
      await _toggleLocalFavorite(spot.id);  // Save locally
    }
    
    if (!mounted) return;
    
    // Invalidate stream to refresh data
    ref.invalidate(touristSpotsByMunicipalityStreamProvider(widget.municipality.id));
    
    // Show success message
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          newFavoriteState ? 'Added to favorites' : 'Removed from favorites',
        ),
        backgroundColor: Colors.green,
      ),
    );
  } catch (e) {
    // Show error message
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Could not update favorite: $e'),
        backgroundColor: Colors.red,
      ),
    );
  } finally {
    setState(() {
      _updatingFavorites.remove(spot.id);  // Hide loading spinner
    });
  }
}
```

### Step 4: API Service Calls Backend
```dart
Future<Map<String, dynamic>> toggleSpotFavorite(int spotId) async {
  try {
    await _ensureInitialized();
    await _syncAuthTokenFromPrefs();  // ← KEY FIX: Sync token before request
    
    final response = await post(ApiConstants.toggleSpotFavorite(spotId));
    // POST to: /api/v1/spots/{spotId}/favorite
    // Headers: Authorization: Bearer <token>
    
    return Map<String, dynamic>.from(response.data['data'] ?? response.data);
  } catch (e) {
    throw Exception('Failed to update favorite: $e');
  }
}
```

### Step 5: Backend Processes Request
```php
// In TouristSpotApiController.toggleFavorite()
public function toggleFavorite(Request $request, $spotId)
{
    // ApiTokenAuth middleware already authenticated user
    $user = $request->user();  // User is set by middleware
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please log in to save favorites.',
        ], 401);
    }
    
    $spot = TouristSpot::find($spotId);
    if (!$spot) {
        return response()->json([
            'success' => false,
            'message' => 'Tourist spot not found',
        ], 404);
    }
    
    // Check if favorite exists
    $favorite = TouristSpotFavorite::where('user_id', $user->id)
        ->where('tourist_spot_id', $spot->id)
        ->first();
    
    $isFavorited = false;
    if ($favorite) {
        $favorite->delete();  // Remove favorite
    } else {
        TouristSpotFavorite::create([  // Add favorite
            'user_id' => $user->id,
            'tourist_spot_id' => $spot->id,
        ]);
        $isFavorited = true;
    }
    
    return response()->json([
        'success' => true,
        'message' => $isFavorited ? 'Spot added to favorites.' : 'Spot removed from favorites.',
        'data' => [
            'spot_id' => $spot->id,
            'is_favorited' => $isFavorited,
        ],
    ]);
}
```

### Step 6: Stream Provider Refreshes Data
```dart
// In app_providers.dart
final touristSpotsByMunicipalityStreamProvider =
    StreamProvider.family<List<TouristSpot>, int>((ref, municipalityId) async* {
  final apiService = ref.watch(apiServiceProvider);
  while (true) {
    try {
      // Fetch fresh data from API
      final spots = await apiService.getTouristSpotsByMunicipality(municipalityId);
      // API returns spots with updated is_favorited status
      yield spots;
    } catch (e) {
      yield* Stream.error(e);
    }
    // Refresh every 15 seconds
    await Future.delayed(const Duration(seconds: 15));
  }
});
```

### Step 7: UI Updates
```dart
// In _buildSpotCard()
// The widget rebuilds with new data from stream
// Heart icon now shows correct state
Icon(
  spot.isFavorited ? Icons.favorite : Icons.favorite_border,
  color: spot.isFavorited ? Colors.redAccent : Colors.white,
)
```

---

## Data Flow - Map Display

### Step 1: User Taps "Full Map" Button
```dart
ElevatedButton.icon(
  onPressed: () => Navigator.pushNamed(
    context,
    '/spot-map',
    arguments: widget.spot,
  ),
  icon: const Icon(Icons.map),
  label: Text('Full Map'),
)
```

### Step 2: Route Navigation
```dart
// In app_routes.dart
case spotMap:
  final spot = settings.arguments as TouristSpot;
  return MaterialPageRoute(
    builder: (_) => TouristSpotMapScreen(touristSpot: spot),
  );
```

### Step 3: Map Screen Initializes
```dart
class _TouristSpotMapScreenState extends State<TouristSpotMapScreen> {
  @override
  void initState() {
    super.initState();
    _initializeMap();  // Start initialization
  }
  
  Future<void> _initializeMap() async {
    try {
      // 1. Request location permission
      final hasPermission = await LocationService.requestLocationPermission();
      
      // 2. Get current user position
      final position = await LocationService.getCurrentPosition();
      
      // 3. Calculate distance
      _calculateDistance();
      
      // 4. Setup map markers and polylines
      _setupMapElements();
      
      // 5. Fetch directions
      await _fetchDirections();
      
      setState(() {
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _locationError = true;
        _isLoading = false;
      });
    }
  }
}
```

### Step 4: Map Elements Setup
```dart
void _setupMapElements() {
  if (_userPosition == null) return;
  
  // Create markers
  final userMarker = Marker(
    markerId: const MarkerId('user_location'),
    position: LatLng(_userPosition!.latitude, _userPosition!.longitude),
    icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueBlue),
  );
  
  final destMarker = Marker(
    markerId: const MarkerId('destination'),
    position: LatLng(widget.touristSpot.latitude, widget.touristSpot.longitude),
    icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
  );
  
  // Create polyline (route)
  final polyline = Polyline(
    polylineId: const PolylineId('route'),
    color: Colors.blue,
    width: 3,
    points: [
      LatLng(_userPosition!.latitude, _userPosition!.longitude),
      LatLng(widget.touristSpot.latitude, widget.touristSpot.longitude),
    ],
  );
  
  setState(() {
    _markers = {userMarker, destMarker};
    _polylines = {polyline};
  });
}
```

### Step 5: Fetch Directions
```dart
Future<void> _fetchDirections() async {
  try {
    if (_userPosition == null) return;
    
    final apiService = ApiService();
    final directions = await apiService.getDirections(
      originLat: _userPosition!.latitude,
      originLng: _userPosition!.longitude,
      destLat: widget.touristSpot.latitude,
      destLng: widget.touristSpot.longitude,
    );
    
    setState(() {
      _directions = directions;
    });
  } catch (e) {
    // Fallback to mock directions
    final mockDirections = _generateMockDirections();
    setState(() {
      _directions = mockDirections;
    });
  }
}
```

### Step 6: Display Map
```dart
GoogleMap(
  onMapCreated: (controller) {
    _mapController = controller;
    // Animate camera to show both user and destination
    _updateCameraToBounds();
  },
  initialCameraPosition: CameraPosition(
    target: LatLng(
      _userPosition!.latitude,
      _userPosition!.longitude,
    ),
    zoom: 15,
  ),
  markers: _markers,
  polylines: _polylines,
  myLocationEnabled: _hasLocationPermission,
  myLocationButtonEnabled: _hasLocationPermission,
  zoomControlsEnabled: true,
  mapToolbarEnabled: true,
)
```

---

## Authentication Flow

### Token Generation (Backend)
```php
// In SocialAuthController
public function googleLogin(Request $request)
{
    // Verify Google ID token
    $user = User::firstOrCreate([
        'email' => $googleUser['email'],
    ], [
        'name' => $googleUser['name'],
        'auth_provider' => 'google',
        'provider_id' => $googleUser['sub'],
    ]);
    
    // Generate API token
    $user->api_token = Str::random(80);
    $user->save();
    
    return response()->json([
        'success' => true,
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'token' => $user->api_token,
        ],
    ]);
}
```

### Token Storage (Frontend)
```dart
// In AuthService._saveSession()
Future<void> _saveSession(AuthUser user) async {
  final prefs = await SharedPreferences.getInstance();
  await prefs.setString(_authUserKey, jsonEncode(user.toJson()));
  await prefs.setString(_authTokenKey, user.token);  // Store token
}
```

### Token Usage (API Requests)
```dart
// In ApiService._syncAuthTokenFromPrefs()
Future<void> _syncAuthTokenFromPrefs() async {
  final prefs = await SharedPreferences.getInstance();
  final token = prefs.getString(_authTokenKey);
  if (token != null && token.isNotEmpty) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  } else {
    _dio.options.headers.remove('Authorization');
  }
}
```

### Token Verification (Backend)
```php
// In ApiTokenAuth middleware
public function handle(Request $request, Closure $next): Response
{
    $token = $request->bearerToken();
    
    if (empty($token)) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. API token required.',
        ], 401);
    }
    
    $user = User::where('api_token', $token)->first();
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid API token.',
        ], 401);
    }
    
    // Set user for this request
    auth()->setUser($user);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });
    
    return $next($request);
}
```

---

## Key Improvements

1. **Proper Authentication**: Token is synced before every API request
2. **Server-Side Favorites**: Favorites are stored in database, not just locally
3. **Real-Time Updates**: Stream provider refreshes every 15 seconds
4. **Better UX**: Full map button provides dedicated map experience
5. **Error Handling**: Proper error messages and fallbacks
6. **Performance**: Efficient database queries with unique constraints

---

## Testing Checklist

- [ ] User can log in with Google/Facebook
- [ ] Heart button shows loading state while updating
- [ ] Favorite status persists after refresh
- [ ] Favorite status syncs across devices
- [ ] Map displays with user location
- [ ] Route shows between user and spot
- [ ] Directions display turn-by-turn steps
- [ ] Nearby facilities show on map
- [ ] Navigation to external maps app works
- [ ] Error messages display correctly
