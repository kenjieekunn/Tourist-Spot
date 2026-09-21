import 'dart:convert';
import 'package:dio/dio.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:flutter_facebook_auth/flutter_facebook_auth.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:tourist_spot_app/config/constants/api_constants.dart';
import 'package:tourist_spot_app/models/auth_user_model.dart';
import 'package:tourist_spot_app/services/api_base_url_resolver.dart';

class AuthService {
  static const String _authUserKey = 'auth_user';
  static const String _authTokenKey = 'auth_token';

  late Dio _dio;
  bool _initialized = false;

  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
  );

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

  // ==================== GOOGLE SIGN IN ====================

  Future<AuthUser?> signInWithGoogle() async {
    try {
      await _ensureInitialized();

      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      if (googleUser == null) {
        // User cancelled the sign-in
        return null;
      }

      final GoogleSignInAuthentication googleAuth =
          await googleUser.authentication;
      final String? idToken = googleAuth.idToken;

      if (idToken == null) {
        throw Exception('Failed to get Google ID token');
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

  // ==================== FACEBOOK SIGN IN ====================

  Future<AuthUser?> signInWithFacebook() async {
    try {
      await _ensureInitialized();

      final LoginResult result = await FacebookAuth.instance.login(
        permissions: ['email', 'public_profile'],
      );

      if (result.status != LoginStatus.success) {
        if (result.status == LoginStatus.cancelled) {
          return null;
        }
        throw Exception(result.message ?? 'Facebook login failed');
      }

      final String accessToken = result.accessToken!.token;

      // Send access token to backend for verification
      final response = await _dio.post(
        ApiConstants.facebookAuth,
        data: {'access_token': accessToken},
      );

      if (response.statusCode == 200 && response.data['success'] == true) {
        final authUser = AuthUser.fromJson(response.data['data']);
        await _saveSession(authUser);
        return authUser;
      } else {
        throw Exception(response.data['message'] ?? 'Facebook login failed');
      }
    } on DioException catch (e) {
      final data = e.response?.data;
      if (data is Map && data['message'] != null) {
        throw Exception(data['message']);
      }
      throw Exception('Facebook sign-in error: ${e.message}');
    } catch (e) {
      throw Exception('Facebook sign-in error: $e');
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
    try {
      await _googleSignIn.signOut();
    } catch (_) {}

    // Sign out from Facebook
    try {
      await FacebookAuth.instance.logOut();
    } catch (_) {}

    // Clear local session
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_authUserKey);
    await prefs.remove(_authTokenKey);
  }
}
