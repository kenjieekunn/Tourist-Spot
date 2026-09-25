class ApiConstants {
  // Base API URL for the Laravel application.
  // Override at runtime for local development:
  // flutter run --dart-define=API_BASE_URL=http://<YOUR-PC-IP>/tourist-spot/web/public/api/v1/
  static const String envBaseUrl =
      String.fromEnvironment('API_BASE_URL', defaultValue: '');
  static const String envReverbUrl =
      String.fromEnvironment('REVERB_URL', defaultValue: '');
  static const bool useAndroidEmulator =
      bool.fromEnvironment('USE_ANDROID_EMULATOR', defaultValue: false);
  static const String androidEmulatorUrl =
      'http://10.0.2.2/tourist-spot/web/public/api/v1/';
  static const String localNetworkUrl =
      'http://192.168.100.188/tourist-spot/web/public/api/v1/';
  static const String webLocalhostUrl =
      'http://localhost/tourist-spot/web/public/api/v1/';
  static const String productionUrl =
      'https://district2touristspot.com/api/v1/';

  static String get baseUrl {
    if (envBaseUrl.isNotEmpty) return envBaseUrl;
    return productionUrl;
  }

  // Admin web URLs (login/dashboard)
  // These are derived from the API base URL so they always match your server.
  static String get adminBaseUrl {
    return baseUrl.replaceFirst(RegExp(r'/api/v1/?$'), '');
  }

  static String get adminLoginUrl => '$adminBaseUrl/login';
  static String get adminDashboardUrl => '$adminBaseUrl/dashboard';

  static String get reverbUrl => envReverbUrl;

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
