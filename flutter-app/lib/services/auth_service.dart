import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';
import 'package:tourist_spot_app/models/auth_user_model.dart';
import 'package:tourist_spot_app/services/api_base_url_resolver.dart';

class AuthService {
  static const String _authUserKey = 'auth_user';
  static const String _authTokenKey = 'auth_token';
  static const String _googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue:
        '677725501414-qd35pvtvsh42njd7mh8mnrbqks5rhts6.apps.googleusercontent.com',
  );

  late Dio _dio;
  bool _initialized = false;
  Future<void>? _googleSignInInitialization;

  final GoogleSignIn _googleSignIn = GoogleSignIn.instance;

  AuthService() {
    _initializeDio();
  }

  void _initializeDio() {
    _dio = Dio(
      BaseOptions(
        baseUrl: ApiConstants.baseUrl,
        connectTimeout: const Duration(seconds: ApiConstants.timeoutSeconds),
        receiveTimeout: const Duration(seconds: ApiConstants.timeoutSeconds),
        headers: {
          'Accept': 'application/json',
        },
      ),
    );
  }

  Future<void> _ensureInitialized() async {
    if (_initialized) return;
    final resolvedBaseUrl = await ApiBaseUrlResolver.resolve();
    _dio.options.baseUrl = resolvedBaseUrl;
    _initialized = true;
  }

  Future<void> _initializeGoogleSignIn() async {
    _googleSignInInitialization ??= _googleSignIn.initialize(
      serverClientId: _googleServerClientId,
    );
    try {
      await _googleSignInInitialization;
    } catch (_) {
      _googleSignInInitialization = null;
      rethrow;
    }
  }

  // ==================== GOOGLE SIGN IN ====================

  Future<AuthUser?> signInWithGoogle() async {
    try {
      if (_googleServerClientId.isEmpty) {
        throw Exception(
          'Google sign-in is not configured. Run with '
          '--dart-define=GOOGLE_SERVER_CLIENT_ID=<Web OAuth client ID>.',
        );
      }
      await _initializeGoogleSignIn();
      await _ensureInitialized();

      final googleUser = await _googleSignIn.authenticate(
        scopeHint: const ['email'],
      );

      final GoogleSignInAuthentication googleAuth = googleUser.authentication;
      final String? idToken = googleAuth.idToken;

      if (idToken == null) {
        throw Exception(
          'Google did not return an ID token. Configure the Web OAuth client '
          'ID with --dart-define=GOOGLE_SERVER_CLIENT_ID=<client-id>.',
        );
      }

      // Send ID token to backend for verification
      final response = await _dio.post(
        ApiConstants.googleAuth,
        data: {'id_token': idToken},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final authUser = AuthUser.fromJson(response.data['data']);
        await _saveSession(authUser);
        return authUser;
      } else {
        throw Exception(response.data['message'] ?? 'Google login failed');
      }
    } on GoogleSignInException catch (e) {
      if (e.code == GoogleSignInExceptionCode.canceled) return null;
      throw Exception('Google sign-in error: ${e.code.name}');
    } on DioException catch (e) {
      final data = e.response?.data;
      if (data is Map && data['message'] != null) {
        throw Exception(data['message']);
      }
      throw Exception('Google sign-in error: ${e.message}');
    } catch (e) {
      throw Exception('Google sign-in error: $e');
    }
  }

  // ==================== SESSION MANAGEMENT ====================

  Future<void> _saveSession(AuthUser user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_authUserKey, jsonEncode(user.toJson()));
    await prefs.setString(_authTokenKey, user.token);
  }

  Future<AuthUser?> getCurrentUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString(_authUserKey);
    if (userJson == null) return null;

    try {
      final Map<String, dynamic> data = jsonDecode(userJson);
      return AuthUser.fromJson(data);
    } catch (_) {
      return null;
    }
  }

  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_authTokenKey);
  }

  Future<bool> isLoggedIn() async {
    final user = await getCurrentUser();
    return user != null && user.token.isNotEmpty;
  }

  Future<void> logout() async {
    // Sign out from Google
    if (_googleServerClientId.isNotEmpty) {
      try {
        await _initializeGoogleSignIn();
        await _googleSignIn.signOut();
      } catch (_) {}
    }

    // Clear local session
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_authUserKey);
    await prefs.remove(_authTokenKey);
  }
}
