import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Base URL for the api/ endpoints on the PHP backend. Override at build/run
/// time, e.g.:
///   flutter run --dart-define=API_BASE_URL=http://10.0.2.2/dr-website/api
/// (10.0.2.2 is how the Android emulator reaches the host machine's localhost.)
/// Defaults to the production site.
const String apiBaseUrl = String.fromEnvironment(
  'API_BASE_URL',
  defaultValue: 'https://drprasannakumark.com/api',
);

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  ApiException(this.message, [this.statusCode]);
  @override
  String toString() => message;
}

/// Thin wrapper around Dio: injects the bearer token, and turns error
/// responses (which the backend always returns as JSON, even on 4xx/5xx)
/// into ApiException with the server's own message.
class ApiClient {
  static const _storage = FlutterSecureStorage();
  static const _tokenKey = 'auth_token';

  final Dio _dio = Dio(BaseOptions(
    baseUrl: apiBaseUrl,
    connectTimeout: const Duration(seconds: 15),
    receiveTimeout: const Duration(seconds: 20),
    validateStatus: (_) => true, // handle all status codes ourselves
  ));

  String? _cachedToken;

  Future<String?> get token async {
    _cachedToken ??= await _storage.read(key: _tokenKey);
    return _cachedToken;
  }

  Future<void> setToken(String? token) async {
    _cachedToken = token;
    if (token == null) {
      await _storage.delete(key: _tokenKey);
    } else {
      await _storage.write(key: _tokenKey, value: token);
    }
  }

  Future<Map<String, String>> _authHeaders() async {
    final t = await token;
    return t == null ? {} : {'Authorization': 'Bearer $t'};
  }

  Map<String, dynamic> _unwrap(Response res) {
    final data = res.data;
    if (res.statusCode != null && res.statusCode! >= 200 && res.statusCode! < 300) {
      return data is Map<String, dynamic> ? data : <String, dynamic>{};
    }
    final msg = (data is Map && data['message'] is String)
        ? data['message'] as String
        : 'Something went wrong (${res.statusCode}). Please try again.';
    throw ApiException(msg, res.statusCode);
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query, bool auth = false}) async {
    try {
      final res = await _dio.get(path,
          queryParameters: query,
          options: Options(headers: auth ? await _authHeaders() : null));
      return _unwrap(res);
    } on DioException catch (e) {
      throw ApiException(_networkMessage(e));
    }
  }

  Future<Map<String, dynamic>> post(String path, {Map<String, dynamic>? data, bool auth = false}) async {
    try {
      final res = await _dio.post(path,
          data: data,
          options: Options(
            contentType: Headers.formUrlEncodedContentType,
            headers: auth ? await _authHeaders() : null,
          ));
      return _unwrap(res);
    } on DioException catch (e) {
      throw ApiException(_networkMessage(e));
    }
  }

  Future<Map<String, dynamic>> postMultipart(String path, FormData form, {bool auth = false}) async {
    try {
      final res = await _dio.post(path,
          data: form,
          options: Options(headers: auth ? await _authHeaders() : null));
      return _unwrap(res);
    } on DioException catch (e) {
      throw ApiException(_networkMessage(e));
    }
  }

  /// Downloads an authenticated record file's bytes (for images/PDF viewing).
  Future<List<int>> getBytes(String path) async {
    try {
      final res = await _dio.get<List<int>>(path,
          options: Options(responseType: ResponseType.bytes, headers: await _authHeaders()));
      if (res.statusCode == 200 && res.data != null) return res.data!;
      throw ApiException('Could not load file (${res.statusCode}).');
    } on DioException catch (e) {
      throw ApiException(_networkMessage(e));
    }
  }

  String _networkMessage(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout ||
        e.type == DioExceptionType.receiveTimeout ||
        e.error is SocketException) {
      return 'Network error. Please check your connection and try again.';
    }
    return 'Network error. Please try again.';
  }
}
