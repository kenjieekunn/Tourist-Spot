import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';

class ApiBaseUrlResolver {
  static const String _prefsKey = 'api_base_url';
  static String _activeBaseUrl = ApiConstants.baseUrl;

  static String get activeBaseUrl => _activeBaseUrl;

  static Future<String> resolve({bool forceDiscover = false}) async {
    if (ApiConstants.envBaseUrl.isNotEmpty) {
      _activeBaseUrl = ApiConstants.envBaseUrl;
      return _activeBaseUrl;
    }
    if (kIsWeb) {
      _activeBaseUrl = ApiConstants.webLocalhostUrl;
      return _activeBaseUrl;
    }
    if (!kIsWeb && ApiConstants.useAndroidEmulator) {
      _activeBaseUrl = ApiConstants.androidEmulatorUrl;
      return _activeBaseUrl;
    }

    final prefs = await SharedPreferences.getInstance();
    if (!forceDiscover) {
      final cached = prefs.getString(_prefsKey);
      if (cached != null && await _isReachable(cached)) {
        _activeBaseUrl = cached;
        return _activeBaseUrl;
      }
    }

    final candidates = <String>[];
    // Prefer the deployed API for release builds, then try the local network for development.
    candidates.add(ApiConstants.productionUrl);
    candidates.add(ApiConstants.localNetworkUrl);

    for (final candidate in candidates.toSet()) {
      if (await _isReachable(candidate)) {
        await prefs.setString(_prefsKey, candidate);
        _activeBaseUrl = candidate;
        return _activeBaseUrl;
      }
    }

    _activeBaseUrl = ApiConstants.productionUrl;
    return _activeBaseUrl;
  }

  static Future<bool> _isReachable(String baseUrl) async {
    try {
      final dio = Dio(BaseOptions(
        baseUrl: baseUrl,
        connectTimeout: const Duration(milliseconds: 700),
        receiveTimeout: const Duration(milliseconds: 700),
        headers: {
          'Accept': 'application/json',
        },
      ));
      final response = await dio.get('municipalities');
      return response.statusCode == 200;
    } catch (_) {
      return false;
    }
  }
}
