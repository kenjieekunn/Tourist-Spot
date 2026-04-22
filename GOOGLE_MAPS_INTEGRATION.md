# Google Maps Integration Guide

This guide covers the Google Maps integration for both the Laravel Admin Panel and the Flutter Mobile App.

## Current Status

### ✅ Flutter App
- **Location Tracking**: Real-time geolocator integration with permission handling
- **Google Maps Display**: Google Maps Flutter with markers for user and tourist spot
- **Turn-by-turn Routing**: Google Directions API integration with polyline visualization
- **Route Information**: Distance and ETA display with expandable step-by-step directions
- **Live Updates**: 20-second auto-refresh for route updates based on user movement

### ✅ Laravel Admin
- **Leaflet Maps**: Interactive map on tourist spot details page
- **Location Search**: Nominatim OSM API for location search
- **Facility Placement**: Visual map-based placement of nearby facilities (dining, gas stations, restrooms)
- **Multi-layer View**: Street view and satellite imagery options
- **Facility Visualization**: Color-coded markers for different facility types

---

## Setup Instructions

### Part 1: Flutter App - Google Maps & Directions API

#### Prerequisites
- Google Cloud Project created
- Billing enabled on the project

#### Step 1: Create Google Cloud Project
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project named "Tourist Spot System"
3. Enable billing for the project

#### Step 2: Enable Required APIs
1. In Google Cloud Console, go to **APIs & Services > Library**
2. Search for and enable:
   - **Google Maps Platform** > **Maps SDK for Android**
   - **Google Maps Platform** > **Maps SDK for iOS**
   - **Google Maps Platform** > **Directions API**

#### Step 3: Create API Keys
1. Go to **APIs & Services > Credentials**
2. Click **Create Credentials > API Key**
3. Create separate keys for:
   - **Android app**: Restrict to Android / set package name to `com.example.tourist_spot_app`
   - **iOS app**: Restrict to iOS / set Bundle ID
   - **Web app**: Restrict to HTTP referrers (optional)
   - **Server**: For any backend requirements

#### Step 4: Configure Android
1. Open `android/app/build.gradle`:
   ```gradle
   android {
       ...
       defaultConfig {
           ...
           manifestPlaceholders = [MAPS_API_KEY: "YOUR_ANDROID_API_KEY"]
       }
   }
   ```

2. The `AndroidManifest.xml` already includes:
   ```xml
   <meta-data
       android:name="com.google.android.geo.API_KEY"
       android:value="${MAPS_API_KEY}" />
   ```

#### Step 5: Configure iOS
1. Open `ios/Runner/GeneratedPluginRegistrant.m`
2. In `ios/Runner/Info.plist`, add:
   ```xml
   <key>com.google.ios.API_KEY</key>
   <string>YOUR_IOS_API_KEY</string>
   ```

#### Step 6: Run Flutter App with Maps API Key

**Option A: Using dart-define (Recommended for Development)**
```bash
flutter run --dart-define=MAPS_API_KEY=YOUR_API_KEY
```

**Option B: Hardcode in api_constants.dart (Not recommended for production)**
Edit `flutter-app/lib/config/constants/api_constants.dart`:
```dart
static const String mapsApiKey = 'YOUR_API_KEY'; // Replace with actual key
```

#### Step 7: Test the Implementation
1. Launch the Flutter app
2. Navigate to any tourist spot detail page
3. Verify:
   - Map displays correctly with spot location
   - Your location is tracked (if permissions granted)
   - Polyline shows route (if route fetched)
   - Turn-by-turn directions appear in expandable tile
   - Distance and ETA display

---

### Part 2: Laravel Admin - Google Maps Integration

#### Current Implementation
The Laravel admin uses Leaflet.js (an open-source alternative) for:
- `show.blade.php`: View tourist spot with nearby facilities map
- `create.blade.php`: Place new spot on map with location search
- `edit.blade.php`: Update spot location and facilities

#### Optional: Upgrade to Google Maps
If you want to use Google Maps instead of Leaflet on the admin panel:

1. Add to your `.env`:
   ```
   GOOGLE_MAPS_API_KEY=YOUR_API_KEY
   ```

2. Update `show.blade.php` to use Google Maps instead of Leaflet:
   ```html
   <script src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY') }}"></script>
   ```

Current Leaflet implementation covers all major features, so this is optional.

---

## How the Integration Works

### Flutter App Flow

1. **User opens tourist spot detail page**
   - Spot location (latitude/longitude) displayed
   - Google Map loads with spot marker

2. **Location Permission Request**
   - App requests location permission
   - If granted: Starts location tracking

3. **User Moves & Route Auto-Updates**
   - Every 20 seconds, app checks for significant movement (>30 meters)
   - If moved, fetches new route from user location to spot
   - Google Directions API processes request

4. **Route Display**
   - Polyline drawn on map showing current path
   - Distance and ETA updated in real-time
   - Turn-by-turn steps extracted from directions response
   - Steps shown in expandable list

5. **Live Location Marker**
   - User location shown with blue marker
   - Updates as they move
   - Camera auto-adjusts to show both user and spot

### Laravel Admin Flow

1. **Creating/Editing a Spot**
   - Admin searches for location using Nominatim (free API)
   - Clicks on map to place spot marker
   - Nearby facilities can be added by clicking map

2. **Viewing a Spot**
   - Show page displays Leaflet map
   - Spot marked in red
   - All nearby facilities shown as colored dots
   - Admin can switch between Street and Satellite views

3. **Facility Management**
   - Three types: Dining (orange), Gas (blue), Restroom (green)
   - Coordinates stored in JSON format
   - Visual validation before saving

---

## API Keys & Security

### For Development
Use `--dart-define` flag to pass API key at runtime:
```bash
flutter run --dart-define=MAPS_API_KEY=dev_key_here
```

### For Production
1. **Android**:
   - Restrict API key to Android app
   - Add package fingerprint verification
   - Rotate key periodically

2. **iOS**:
   - Restrict API key to iOS app  
   - Use iOS Bundle ID restriction
   - Rotate key periodically

3. **Backend** (if needed):
   - Use server-side API key
   - Never expose in client code
   - Use API key restrictions

### Cost Optimization
- Directions API in Google Cloud is $5 per 1000 requests
- Implement client-side caching to reduce API calls
- The app auto-fetches every 20s max with 30m movement threshold
- Estimate: ~7,200 requests/day for active users = ~$36/day if 1k users moving

---

## Troubleshooting

### Flutter App
1. **Map Not Displaying**
   - Verify API key is correct and has Maps SDK enabled
   - Check AndroidManifest.xml has correct meta-data
   - Ensure all permissions granted in app settings

2. **Location Not Tracking**
   - Verify location permission granted
   - Check device location services enabled
   - Review geolocator configuration

3. **Route Not Showing**
   - Verify Directions API enabled
   - Check MAPS_API_KEY is passed via --dart-define
   - Ensure both user location and spot location valid

### Laravel Admin
1. **Location Search Not Working**
   - Nominatim API might be rate-limited (1 request/second)
   - Check browser console for CORS errors
   - Verify spelling in search query

2. **Facilities Not Placing**
   - Click map after selecting facility type and name
   - Verify coordinates are within valid range

---

## Performance Notes

### Flutter App Optimization
- **Polyline Caching**: Routes cached to reduce API calls
- **Location Batching**: Only fetches when 30+ meters moved
- **Debouncing**: Route refreshes max every 20 seconds
- **Memory**: Large numbers of facilities handled efficiently

### Laravel Admin Optimization
- **Nominatim Rate Limiting**: Implemented 500ms debounce
- **GeoJSON Storage**: Facilities stored as efficient JSON
- **Lazy Loading**: Map only loads when viewing show page

---

## Future Enhancements

1. **Flutter App**
   - Offline map caching using cached_network_image
   - Custom map styling
   - Voice-guided turn-by-turn
   - Real-time traffic layer

2. **Laravel Admin**
   - Distance calculation between spots
   - Route optimization for multiple spots
   - Advanced facility filtering
   - Bulk geocoding

---

## API References

- [Google Maps Platform](https://developers.google.com/maps)
- [Google Directions API](https://developers.google.com/maps/documentation/directions)
- [Leaflet.js Documentation](https://leafletjs.com/)
- [Nominatim API](https://nominatim.org/release-docs/)
- [Geolocator Plugin](https://pub.dev/packages/geolocator)
- [Google Maps Flutter Plugin](https://pub.dev/packages/google_maps_flutter)

---

## Support & Next Steps

For issues:
1. Check browser/console logs for errors
2. Verify API keys in Google Cloud Console
3. Test individual APIs using curl/Postman
4. Review logs in app settings

To continue development:
1. Set up API keys following this guide
2. Test location tracking on a physical device
3. Monitor API usage in Google Cloud Console
4. Implement caching for production use
