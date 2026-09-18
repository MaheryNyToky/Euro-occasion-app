import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;
import '../config/app_config.dart';

class ApiException implements Exception {
  final int statusCode;
  final String message;
  final String? requestId;
  final Map<String, dynamic>? validationErrors;

  ApiException({
    required this.statusCode,
    required this.message,
    this.requestId,
    this.validationErrors,
  });

  @override
  String toString() => message;
}

class ApiClient {
  static final ApiClient instance = ApiClient._internal();

  ApiClient._internal();

  String baseUrl = AppConfig.defaultApiBaseUrl;
  String? authToken;
  String? deviceIdentifier = 'flutter-terminal-${Random().nextInt(900000) + 100000}';

  void setAuthToken(String? token) {
    authToken = token;
  }

  String _generateUuid() {
    final rnd = Random();
    final bytes = List<int>.generate(16, (_) => rnd.nextInt(256));
    bytes[6] = (bytes[6] & 0x0f) | 0x40; // version 4
    bytes[8] = (bytes[8] & 0x3f) | 0x80; // variant
    final hex = bytes.map((b) => b.toRadixString(16).padLeft(2, '0')).join();
    return '${hex.substring(0, 8)}-${hex.substring(8, 12)}-${hex.substring(12, 16)}-${hex.substring(16, 20)}-${hex.substring(20)}';
  }

  Map<String, String> _buildHeaders() {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-Request-Id': _generateUuid(),
    };

    if (authToken != null && authToken!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $authToken';
    }

    if (deviceIdentifier != null && deviceIdentifier!.isNotEmpty) {
      headers['X-Device-Id'] = deviceIdentifier!;
    }

    return headers;
  }

  Uri _buildUri(String endpoint, [Map<String, String>? queryParams]) {
    final cleanBase = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    final cleanEndpoint = endpoint.startsWith('/') ? endpoint : '/$endpoint';
    final urlString = '$cleanBase$cleanEndpoint';

    final uri = Uri.parse(urlString);
    if (queryParams != null && queryParams.isNotEmpty) {
      return uri.replace(queryParameters: queryParams);
    }
    return uri;
  }

  dynamic _handleResponse(http.Response response) {
    dynamic decoded;
    try {
      decoded = jsonDecode(response.body);
    } catch (_) {
      decoded = null;
    }

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return decoded;
    }

    String message = 'Une erreur est survenue (${response.statusCode})';
    String? requestId;
    Map<String, dynamic>? validationErrors;

    if (decoded is Map<String, dynamic>) {
      if (decoded.containsKey('error') && decoded['error'] is Map) {
        final errorMap = decoded['error'] as Map<String, dynamic>;
        message = errorMap['message'] ?? message;
        requestId = errorMap['request_id'];
        if (errorMap.containsKey('validation_errors') && errorMap['validation_errors'] is Map) {
          validationErrors = Map<String, dynamic>.from(errorMap['validation_errors']);
        }
      } else if (decoded.containsKey('message')) {
        message = decoded['message'].toString();
      }
    }

    throw ApiException(
      statusCode: response.statusCode,
      message: message,
      requestId: requestId,
      validationErrors: validationErrors,
    );
  }

  Future<dynamic> get(String endpoint, {Map<String, String>? queryParams}) async {
    final uri = _buildUri(endpoint, queryParams);
    final response = await http.get(uri, headers: _buildHeaders());
    return _handleResponse(response);
  }

  Future<dynamic> post(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = _buildUri(endpoint);
    final response = await http.post(
      uri,
      headers: _buildHeaders(),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handleResponse(response);
  }

  Future<dynamic> patch(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = _buildUri(endpoint);
    final response = await http.patch(
      uri,
      headers: _buildHeaders(),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handleResponse(response);
  }

  Future<dynamic> delete(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = _buildUri(endpoint);
    final response = await http.delete(
      uri,
      headers: _buildHeaders(),
      body: body != null ? jsonEncode(body) : null,
    );
    return _handleResponse(response);
  }
}
