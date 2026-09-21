class ApiConstants {
  // Base API URL - Local XAMPP dev server for real-time sync with admin system
  // Override at runtime:
  // flutter run --dart-define=API_BASE_URL=http://<YOUR-PC-IP>/tourist-spot-system/admin-system/public/api/v1/
  static const String envBaseUrl =
      String.fromEnvironment('API_BASE_URL', defaultValue: '');
  static const bool useAndroidEmulator =
      bool.fromEnvironment('USE_ANDROID_EMULATOR', defaultValue: false);
  static const String androidEmulatorUrl =
      'http://10.0.2.2/tourist-spot-system/admin-system/public/api/v1/';
  static const String localNetworkUrl =
      'http://192.168.100.188/tourist-spot-system/admin-system/public/api/v1/';
  static const String webLocalhostUrl =
      'http://localhost/tourist-spot-system/admin-system/public/api/v1/';

  static String get baseUrl {
    if (envBaseUrl.isNotEmpty) return envBaseUrl;
    return localNetworkUrl;
  }

  // Admin web URLs (login/dashboard)
  // These are derived from the API base URL so they always match your server.
  static String get adminBaseUrl {
    return baseUrl.replaceFirst(RegExp(r'/api/v1/?$'), '');
  }

  static String get adminLoginUrl => '$adminBaseUrl/login';
  static String get adminDashboardUrl => '$adminBaseUrl/dashboard';

  // Google Maps Directions API Key
  // Provide via: flutter run --dart-define=MAPS_API_KEY=YOUR_KEY
  static const String mapsApiKey =
      String.fromEnvironment('MAPS_API_KEY', defaultValue: '');

  // Municipalities Endpoints
  static const String municipalities = 'municipalities';
  static String municipalityDetail(int id) => 'municipalities/$id';

  // **NEW: Exact match with web API for municipality spots**
  static String municipalitySpots(int id) => 'municipalities/$id/spots';

  // Tourist Spots Endpoints (general)
  static const String touristSpots = 'tourist-spots';
  static const String touristSpotSearch = 'tourist-spots/search';
  static String spotDetail(int id) => 'spots/$id';
  static String toggleSpotFavorite(int id) => 'spots/$id/favorite';

  // Auth Endpoints
  static const String googleAuth = 'auth/google';
  static const String facebookAuth = 'auth/facebook';
  static const String currentUser = 'user';

  // Reviews Endpoints
  static String spotReviews(int spotId) => 'spots/$spotId/reviews';
  static String createReview(int spotId) => 'spots/$spotId/reviews';

  // Timeout
  static const int timeoutSeconds = 30;
}
