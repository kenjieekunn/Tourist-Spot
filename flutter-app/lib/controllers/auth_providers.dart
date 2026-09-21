import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:tourist_spot_app/models/auth_user_model.dart';
import 'package:tourist_spot_app/services/auth_service.dart';

// AuthService provider
final authServiceProvider = Provider((ref) => AuthService());

// Current authenticated user (null if not logged in)
final authUserProvider =
    StateNotifierProvider<AuthUserNotifier, AuthUser?>((ref) {
  final authService = ref.watch(authServiceProvider);
  return AuthUserNotifier(authService);
});

// Simple boolean provider for login state
final isLoggedInProvider = Provider<bool>((ref) {
  final user = ref.watch(authUserProvider);
  return user != null;
});

class AuthUserNotifier extends StateNotifier<AuthUser?> {
  final AuthService _authService;

  AuthUserNotifier(this._authService) : super(null) {
    _loadUser();
  }

  Future<void> _loadUser() async {
    final user = await _authService.getCurrentUser();
    state = user;
  }

  Future<void> signInWithGoogle() async {
    final user = await _authService.signInWithGoogle();
    state = user;
  }

  Future<void> signInWithFacebook() async {
    final user = await _authService.signInWithFacebook();
    state = user;
  }

  Future<void> logout() async {
    await _authService.logout();
    state = null;
  }

  String? get token => state?.token;

  Future<void> refresh() async {
    final user = await _authService.getCurrentUser();
    state = user;
  }
}
