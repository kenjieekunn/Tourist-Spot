# Troubleshooting Guide - Favorites & Map Features

## Favorite Button Not Working

### Issue: Heart button doesn't save favorite
**Checklist:**
- [ ] User is logged in (check if auth token is present)
- [ ] API token is being sent with the request (check network logs)
- [ ] Database `tourist_spot_favorites` table exists
- [ ] User has `api_token` field populated in `users` table

**Debug Steps:**
1. Check if user is authenticated:
   ```dart
   final user = ref.read(authUserProvider);
   print('User: $user');
   print('Token: ${user?.token}');
   ```

2. Check API response in network logs:
   - Should return 200 status
   - Should include `is_favorited` in response

3. Verify database:
   ```sql
   SELECT * FROM users WHERE id = <user_id>;
   -- Check if api_token is populated
   
   SELECT * FROM tourist_spot_favorites WHERE user_id = <user_id>;
   -- Check if favorite record exists
   ```

### Issue: Favorite status not persisting after refresh
**Solution:**
- Ensure `ref.invalidate()` is being called (not `ref.refresh()`)
- Check that stream provider is properly re-fetching data
- Verify API returns updated `is_favorited` status

---

## Map Not Displaying

### Issue: Map screen shows blank or error
**Checklist:**
- [ ] Google Maps API key is set in `.env`
- [ ] Location permissions are granted
- [ ] Tourist spot has valid latitude/longitude
- [ ] Google Maps Flutter package is properly initialized

**Debug Steps:**
1. Verify API key in `.env`:
   ```
   GOOGLE_MAPS_API_KEY=AIzaSyDXUjga_-ckxO6o1frZ-PuGL9XM_9tR6HY
   ```

2. Check location permissions:
   - Android: `android/app/src/main/AndroidManifest.xml`
   - iOS: `ios/Runner/Info.plist`

3. Verify spot coordinates:
   ```dart
   print('Latitude: ${spot.latitude}');
   print('Longitude: ${spot.longitude}');
   ```

### Issue: Map shows but route/directions not displaying
**Solution:**
- Ensure Google Maps Directions API is enabled
- Check if user location is being obtained
- Verify coordinates are valid (lat: -90 to 90, lng: -180 to 180)

### Issue: "Full Map" button not appearing
**Solution:**
- Ensure you're on the latest version of `tourist_spot_detail_screen.dart`
- Check that route `/spot-map` is registered in `app_routes.dart`
- Verify `TouristSpotMapScreen` is imported

---

## Common Error Messages

### "Unauthorized. Please log in to save favorites."
**Cause:** User is not authenticated or token is invalid
**Solution:**
1. Log out and log back in
2. Check if token is being stored in SharedPreferences
3. Verify API token in database matches what's being sent

### "Failed to update favorite: Exception: ..."
**Cause:** Network error or API issue
**Solution:**
1. Check internet connection
2. Verify XAMPP/Laravel server is running
3. Check API logs: `storage/logs/laravel.log`

### "Location services not available"
**Cause:** Location permissions not granted or services disabled
**Solution:**
1. Enable location services on device
2. Grant location permission to app
3. Check device settings

### "No maps app installed"
**Cause:** Device doesn't have Google Maps or Apple Maps
**Solution:**
- This is expected on some devices
- The in-app map should still work
- User can still see directions in the app

---

## Performance Tips

### Favorite Toggle Slow
- Check network latency
- Ensure database indexes are present:
  ```sql
  CREATE UNIQUE INDEX unique_user_spot 
  ON tourist_spot_favorites(user_id, tourist_spot_id);
  ```

### Map Loading Slow
- Reduce number of markers displayed
- Limit nearby facilities to top 5-10
- Cache map tiles locally

### Stream Provider Refreshing Too Often
- Current: 15 seconds
- To adjust, modify in `app_providers.dart`:
  ```dart
  await Future.delayed(const Duration(seconds: 15));
  ```

---

## Database Queries for Debugging

### Check user's favorites
```sql
SELECT 
  tsf.id,
  tsf.user_id,
  ts.name as spot_name,
  ts.latitude,
  ts.longitude
FROM tourist_spot_favorites tsf
JOIN tourist_spots ts ON tsf.tourist_spot_id = ts.id
WHERE tsf.user_id = <user_id>
ORDER BY tsf.created_at DESC;
```

### Check if favorite exists
```sql
SELECT * FROM tourist_spot_favorites 
WHERE user_id = <user_id> AND tourist_spot_id = <spot_id>;
```

### Count favorites per user
```sql
SELECT user_id, COUNT(*) as favorite_count
FROM tourist_spot_favorites
GROUP BY user_id
ORDER BY favorite_count DESC;
```

### Check API tokens
```sql
SELECT id, name, email, api_token 
FROM users 
WHERE api_token IS NOT NULL;
```

---

## Network Debugging

### Enable Dio Logging
The API service already has logging enabled. Check console output for:
- Request headers (should include `Authorization: Bearer <token>`)
- Request body
- Response status and body

### Check Request Headers
```dart
// In api_service.dart, the interceptor logs:
// - Authorization header
// - Content-Type
// - Accept header
```

### Verify Endpoint
- Favorite toggle: `POST /api/v1/spots/{spotId}/favorite`
- Get spots: `GET /api/v1/municipalities/{municipalityId}/spots`

---

## Reset/Clear Data

### Clear Local Favorites (for testing)
```dart
final prefs = await SharedPreferences.getInstance();
await prefs.remove('local_favorites');
```

### Clear Auth Session
```dart
final prefs = await SharedPreferences.getInstance();
await prefs.remove('auth_user');
await prefs.remove('auth_token');
```

### Reset Database Favorites
```sql
DELETE FROM tourist_spot_favorites;
```

---

## Support

For additional help:
1. Check Laravel logs: `admin-system/storage/logs/laravel.log`
2. Check Flutter console output
3. Enable network debugging in Dio
4. Verify database connectivity
