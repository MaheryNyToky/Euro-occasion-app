import 'dart:io';
import 'package:flutter/foundation.dart';
import '../../../core/network/api_client.dart';
import '../models/user_session.dart';

class AuthService {
  static final AuthService instance = AuthService._internal();

  AuthService._internal();

  AuthSession? _currentSession;
  AuthSession? get currentSession => _currentSession;
  bool get isAuthenticated => _currentSession != null;

  String _detectPlatform() {
    if (kIsWeb) return 'web';
    if (Platform.isWindows) return 'windows';
    if (Platform.isAndroid) return 'android';
    if (Platform.isIOS) return 'ios';
    if (Platform.isMacOS) return 'web';
    return 'web';
  }

  Future<AuthSession> login({
    required String tenantCode,
    required String email,
    required String password,
    String? deviceName,
  }) async {
    final client = ApiClient.instance;

    final response = await client.post('/auth/login', body: {
      'tenant_code': tenantCode,
      'email': email,
      'password': password,
      'device_identifier': client.deviceIdentifier,
      'device_name': deviceName ?? 'Terminal ${_detectPlatform().toUpperCase()}',
      'platform': _detectPlatform(),
      'app_version': '1.0.0',
    });

    final session = AuthSession.fromJson(response);
    _currentSession = session;
    client.setAuthToken(session.token);

    return session;
  }

  Future<void> logout() async {
    final client = ApiClient.instance;
    try {
      if (client.authToken != null) {
        await client.post('/auth/logout');
      }
    } catch (_) {
      // Ignore network errors on logout
    } finally {
      _currentSession = null;
      client.setAuthToken(null);
    }
  }

  Future<UserInfo?> getMe() async {
    final client = ApiClient.instance;
    if (client.authToken == null) return null;

    final response = await client.get('/me');
    if (response is Map<String, dynamic> && response.containsKey('data')) {
      final user = UserInfo.fromJson(response['data'] as Map<String, dynamic>);
      return user;
    }
    return null;
  }
}
