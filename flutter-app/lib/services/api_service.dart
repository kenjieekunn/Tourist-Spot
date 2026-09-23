import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';
import 'package:tourist_spot_app/models/municipality_model.dart';
import 'package:tourist_spot_app/models/tourist_spot_model.dart';
import 'package:tourist_spot_app/services/api_base_url_resolver.dart';

class ApiService {
  late Dio _dio;
  bool _initialized = false;
  static const String _authTokenKey = 'auth_token';
  static const String _municipalitiesCacheKey = 'cache_municipalities_v1';
  static const String _touristSpotsCacheKey = 'cache_tourist_spots_v1';

  static String _municipalitySpotsCacheKey(int id) =>
      'cache_municipality_spots_v1_$id';

  ApiService() {
    _initializeDio(baseUrl: ApiConstants.baseUrl);
  }

  void _initializeDio({required String baseUrl}) {
    _dio = Dio(
      BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(seconds: ApiConstants.timeoutSeconds),
        receiveTimeout: const Duration(seconds: ApiConstants.timeoutSeconds),
        headers: {
          'Accept': 'application/json',
        },
      ),
    );

    // Add logging interceptor
    _dio.interceptors.add(LogInterceptor(
      requestBody: true,
      responseBody: true,
      logPrint: (obj) => print(obj),
    ));
  }

  Future<Set<int>> _getLocalFavorites() async {
    final prefs = await SharedPreferences.getInstance();
    final favoritesJson = prefs.getString('local_favorites') ?? '{}';
    try {
      final favorites = Map<String, bool>.from(
        (jsonDecode(favoritesJson) as Map).cast<String, bool>(),
      );
      return favorites.keys.map((key) => int.parse(key)).toSet();
    } catch (_) {
      return <int>{};
    }
  }

  Future<void> _ensureInitialized({bool forceDiscover = false}) async {
    if (_initialized && !forceDiscover) return;
    final resolvedBaseUrl =
        await ApiBaseUrlResolver.resolve(forceDiscover: forceDiscover);
    _initializeDio(baseUrl: resolvedBaseUrl);
    await _syncAuthTokenFromPrefs();
    _initialized = true;
  }

  Future<void> _syncAuthTokenFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString(_authTokenKey);
    if (token != null && token.isNotEmpty) {
      _dio.options.headers['Authorization'] = 'Bearer $token';
    } else {
      _dio.options.headers.remove('Authorization');
    }
  }

  // GET request
  Future<Response> get(String endpoint,
      {Map<String, dynamic>? queryParameters}) async {
    try {
      await _ensureInitialized();
      await _syncAuthTokenFromPrefs();
      return await _dio.get(endpoint, queryParameters: queryParameters);
    } catch (e) {
      await _ensureInitialized(forceDiscover: true);
      await _syncAuthTokenFromPrefs();
      return await _dio.get(endpoint, queryParameters: queryParameters);
    }
  }

  // POST request
  Future<Response> post(String endpoint, {dynamic data}) async {
    try {
      await _ensureInitialized();
      await _syncAuthTokenFromPrefs();
      return await _dio.post(endpoint, data: data);
    } catch (e) {
      // Don't retry FormData requests (file uploads) as FormData can only be sent once
      if (data is FormData) {
        rethrow;
      }
      await _ensureInitialized(forceDiscover: true);
      await _syncAuthTokenFromPrefs();
      return await _dio.post(endpoint, data: data);
    }
  }

  // PUT request
  Future<Response> put(String endpoint, {dynamic data}) async {
    try {
      await _ensureInitialized();
      await _syncAuthTokenFromPrefs();
      return await _dio.put(endpoint, data: data);
    } catch (e) {
      await _ensureInitialized(forceDiscover: true);
      await _syncAuthTokenFromPrefs();
      return await _dio.put(endpoint, data: data);
    }
  }

  // DELETE request
  Future<Response> delete(String endpoint) async {
    try {
      await _ensureInitialized();
      await _syncAuthTokenFromPrefs();
      return await _dio.delete(endpoint);
    } catch (e) {
      await _ensureInitialized(forceDiscover: true);
      await _syncAuthTokenFromPrefs();
      return await _dio.delete(endpoint);
    }
  }

  // Upload file
  Future<Response> uploadFile(String endpoint, String filePath) async {
    try {
      await _ensureInitialized();
      FormData formData = FormData.fromMap({
        'file': await MultipartFile.fromFile(filePath),
      });
      return await _dio.post(endpoint, data: formData);
    } catch (e) {
      await _ensureInitialized(forceDiscover: true);
      FormData formData = FormData.fromMap({
        'file': await MultipartFile.fromFile(filePath),
      });
      return await _dio.post(endpoint, data: formData);
    }
  }

  void setAuthToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
  }

  void clearAuthToken() {
    _dio.options.headers.remove('Authorization');
  }

  Future<void> _writeListCache(
      String key, List<Map<String, dynamic>> items) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(key, jsonEncode(items));
    await prefs.setInt('${key}_ts', DateTime.now().millisecondsSinceEpoch);
  }

  Future<List<Map<String, dynamic>>?> _readListCache(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(key);
    if (raw == null) return null;
    try {
      final decoded = jsonDecode(raw);
      if (decoded is List) {
        final items = <Map<String, dynamic>>[];
        for (final item in decoded) {
          if (item is Map) {
            items.add(Map<String, dynamic>.from(item));
          }
        }
        return items;
      }
    } catch (_) {
      return null;
    }
    return null;
  }

  // ===== Municipalities Endpoints =====

// REMOVED: _getDemoMunicipalities() - Now fully dependent on real web API for sync

// REMOVED: _getDemoTouristSpots() - Now fully dependent on real web API for sync

  Future<List<Municipality>> getMunicipalities() async {
    try {
      final response = await get(ApiConstants.municipalities);
      final List<dynamic> data = response.data['data'] ?? response.data;
      final municipalities = data
          .map((m) => Municipality.fromJson(m as Map<String, dynamic>))
          .toList();
      await _writeListCache(
        _municipalitiesCacheKey,
        municipalities.map((m) => m.toJson()).toList(),
      );
      return municipalities;
    } catch (e) {
      final cached = await _readListCache(_municipalitiesCacheKey);
      if (cached != null) {
        return cached.map((m) => Municipality.fromJson(m)).toList();
      }
      throw Exception(
          'Cannot fetch municipalities. Ensure XAMPP/MySQL running and DB seeded: $e');
    }
  }

  Future<Municipality> getMunicipality(int id) async {
    try {
      final response = await get(ApiConstants.municipalityDetail(id));
      return Municipality.fromJson(response.data['data'] ?? response.data);
    } catch (e) {
      throw Exception('Failed to fetch municipality: $e');
    }
  }

  // ===== Tourist Spots Endpoints =====
  Future<List<TouristSpot>> getTouristSpots() async {
    try {
      final response = await get(ApiConstants.touristSpots);
      final List<dynamic> data = response.data['data'] ?? response.data;
      final spots = data
          .map((s) => TouristSpot.fromJson(s as Map<String, dynamic>))
          .toList();
      await _writeListCache(
        _touristSpotsCacheKey,
        spots.map((s) => s.toJson()).toList(),
      );
      return spots;
    } catch (e) {
      final cached = await _readListCache(_touristSpotsCacheKey);
      if (cached != null) {
        return cached.map((s) => TouristSpot.fromJson(s)).toList();
      }
      throw Exception('Failed to fetch tourist spots: $e');
    }
  }

  Future<List<TouristSpot>> getTouristSpotsByMunicipality(
      int municipalityId) async {
    try {
      final response =
          await get(ApiConstants.municipalitySpots(municipalityId));
      final List<dynamic> data = response.data['data'] ?? response.data;

      // Get local favorites for non-authenticated users
      final localFavorites = await _getLocalFavorites();

      final spots = data.map((s) {
        final spot = TouristSpot.fromJson(s as Map<String, dynamic>);
        // Override is_favorited if in local favorites
        if (localFavorites.contains(spot.id)) {
          return TouristSpot(
            id: spot.id,
            name: spot.name,
            description: spot.description,
            address: spot.address,
            category: spot.category,
            latitude: spot.latitude,
            longitude: spot.longitude,
            openingDays: spot.openingDays,
            openingTime: spot.openingTime,
            closingTime: spot.closingTime,
            phone: spot.phone,
            website: spot.website,
            entranceFee: spot.entranceFee,
            imageUrl: spot.imageUrl,
            nearbyDining: spot.nearbyDining,
            nearbyGasStations: spot.nearbyGasStations,
            nearbyFacilities: spot.nearbyFacilities,
            status: spot.status,
            verificationStatus: spot.verificationStatus,
            isFavorited: true,
            municipality: spot.municipality,
            averageRating: spot.averageRating,
            reviewsCount: spot.reviewsCount,
          );
        }
        return spot;
      }).toList();
      await _writeListCache(
        _municipalitySpotsCacheKey(municipalityId),
        spots.map((s) => s.toJson()).toList(),
      );
      return spots;
    } catch (e) {
      final cached =
          await _readListCache(_municipalitySpotsCacheKey(municipalityId));
      if (cached != null) {
        return cached.map((s) => TouristSpot.fromJson(s)).toList();
      }
      throw Exception('Cannot fetch spots. Ensure Laravel API running: $e');
    }
  }

  Future<TouristSpot> getTouristSpot(int spotId) async {
    try {
      final response = await get(ApiConstants.spotDetail(spotId));
      return TouristSpot.fromJson(response.data['data'] ?? response.data);
    } catch (e) {
      throw Exception('Cannot fetch spot details. Check API: $e');
    }
  }

  Future<Map<String, dynamic>> toggleSpotFavorite(int spotId) async {
    try {
      await _ensureInitialized();
      await _syncAuthTokenFromPrefs();
      final response = await post(ApiConstants.toggleSpotFavorite(spotId));
      return Map<String, dynamic>.from(response.data['data'] ?? response.data);
    } catch (e) {
      throw Exception('Failed to update favorite: $e');
    }
  }

  // ===== Reviews Endpoints =====
// REMOVED: _getDemoReviews() - Use real user reviews from DB

  Future<List<Map<String, dynamic>>> getReviews(int spotId) async {
    try {
      final response = await get(ApiConstants.spotDetail(spotId));
      final List<dynamic> data =
          (response.data['data']?['reviews']) ?? response.data['reviews'] ?? [];
      return data
          .whereType<Map>()
          .map((review) => Map<String, dynamic>.from(review))
          .toList();
    } catch (e) {
      throw Exception('Reviews unavailable. Check API: $e');
    }
  }

  Future<Map<String, dynamic>> submitReview({
    required int spotId,
    required String userName,
    required int rating,
    required String comment,
    List<String>? imagePaths,
    List<String>? videoPaths,
    int? userId,
    String? authToken,
  }) async {
    try {
      final formDataMap = <String, dynamic>{
        'user_name': userName,
        'rating': rating.toString(),
        'comment': comment,
      };

      if (userId != null) {
        formDataMap['user_id'] = userId.toString();
      }

      final mediaPaths = <String>[
        ...?imagePaths,
        ...?videoPaths,
      ];
      if (mediaPaths.isNotEmpty) {
        formDataMap['media'] = await Future.wait(
          mediaPaths.map((mediaPath) => MultipartFile.fromFile(mediaPath)),
        );
      }

      final formData = FormData.fromMap(formDataMap);

      final response = await post(
        ApiConstants.createReview(spotId),
        data: formData,
      );

      // Handle the response
      if (response.statusCode == 201 || response.statusCode == 200) {
        return response.data['data'] ?? response.data;
      } else {
        throw Exception(
            'Server returned: ${response.statusCode} - ${response.data}');
      }
    } on DioException catch (e) {
      final data = e.response?.data;
      if (data is Map) {
        final message = data['message']?.toString();
        final errors = data['errors'];
        if (errors is Map && errors.isNotEmpty) {
          final firstError = errors.values.first;
          if (firstError is List && firstError.isNotEmpty) {
            throw Exception(firstError.first.toString());
          }
        }
        if (message != null && message.isNotEmpty) {
          throw Exception(message);
        }
      }

      throw Exception('Failed to submit review: ${e.message}');
    } catch (e) {
      throw Exception('Failed to submit review: $e');
    }
  }

  Future<Map<String, dynamic>> uploadReviewImage(String filePath) async {
    try {
      return (await uploadFile('reviews/upload-image', filePath)).data;
    } catch (e) {
      throw Exception('Failed to upload image: $e');
    }
  }

  // ===== Search Endpoints =====
  Future<List<TouristSpot>> searchTouristSpots(String query) async {
    try {
      final response =
          await get(ApiConstants.touristSpotSearch, queryParameters: {
        'q': query,
      });
      final List<dynamic> data = response.data['data'] ?? response.data;
      return data
          .map((s) => TouristSpot.fromJson(s as Map<String, dynamic>))
          .toList();
    } catch (e) {
      throw Exception('Failed to search tourist spots: $e');
    }
  }

  // ===== Navigation Endpoints =====
  /// Get directions from origin to destination
  /// Returns a list of direction steps with instructions
  Future<List<Map<String, dynamic>>> getDirections({
    required double originLat,
    required double originLng,
    required double destLat,
    required double destLng,
  }) async {
    try {
      final response = await get('directions', queryParameters: {
        'origin': '$originLat,$originLng',
        'destination': '$destLat,$destLng',
      });

      final List<dynamic> steps = response.data['steps'] ?? [];
      return steps
          .map((step) => Map<String, dynamic>.from(step as Map))
          .toList();
    } catch (e) {
      // Return empty list if directions fail (fall back to direct route)
      print('Failed to get directions: $e');
      return [];
    }
  }

  /// Get nearby tourist spots based on user's current location
  Future<List<TouristSpot>> getNearbyTouristSpots({
    required double latitude,
    required double longitude,
    double radiusInKm = 10,
  }) async {
    try {
      final response = await get('tourist-spots/nearby', queryParameters: {
        'latitude': latitude,
        'longitude': longitude,
        'radius': radiusInKm,
      });

      final List<dynamic> data = response.data['data'] ?? response.data;
      return data
          .map((s) => TouristSpot.fromJson(s as Map<String, dynamic>))
          .toList();
    } catch (e) {
      throw Exception('Failed to get nearby tourist spots: $e');
    }
  }
}
