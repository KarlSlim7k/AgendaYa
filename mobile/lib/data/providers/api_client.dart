import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/core/constants/app_constants.dart';

class ApiClient {
  static ApiClient? _sharedInstance;

  factory ApiClient({
    http.Client? httpClient,
    bool usePersistentStorage = true,
  }) {
    if (httpClient != null || !usePersistentStorage) {
      return ApiClient._internal(
        httpClient: httpClient ?? http.Client(),
        usePersistentStorage: usePersistentStorage,
      );
    }

    _sharedInstance ??= ApiClient._internal(
      httpClient: http.Client(),
      usePersistentStorage: true,
    );
    return _sharedInstance!;
  }

  ApiClient._internal({
    required http.Client httpClient,
    required bool usePersistentStorage,
  })  : _httpClient = httpClient,
        _usePersistentStorage = usePersistentStorage;

  final http.Client _httpClient;
  final bool _usePersistentStorage;
  final _storage = const FlutterSecureStorage();
  String? _token;

  Future<void> setToken(String token, {bool persist = true}) async {
    _token = token;
    if (!_usePersistentStorage) {
      return;
    }

    if (persist) {
      await _storage.write(key: AppConstants.authTokenKey, value: token);
      return;
    }

    await _storage.delete(key: AppConstants.authTokenKey);
  }

  Future<String?> getToken() async {
    if (_token != null) {
      return _token;
    }

    if (!_usePersistentStorage) {
      return _token;
    }

    _token ??= await _storage.read(key: AppConstants.authTokenKey);
    return _token;
  }

  Future<void> clearToken() async {
    _token = null;
    if (!_usePersistentStorage) {
      return;
    }

    await _storage.delete(key: AppConstants.authTokenKey);
  }

  Future<bool> hasPersistedToken() async {
    if (!_usePersistentStorage) {
      return _token != null && _token!.isNotEmpty;
    }

    final token = await _storage.read(key: AppConstants.authTokenKey);
    return token != null && token.isNotEmpty;
  }

  Future<http.Response> get(String endpoint, {Map<String, String>? queryParams}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final uriWithParams = queryParams != null 
        ? uri.replace(queryParameters: queryParams) 
        : uri;

    final headers = await _getHeaders();
    return await _httpClient.get(uriWithParams, headers: headers);
  }

  Future<http.Response> post(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await _httpClient.post(
      uri,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
  }

  Future<http.Response> patch(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await _httpClient.patch(
      uri,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
  }

  Future<http.Response> put(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();

    return await _httpClient.put(
      uri,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
  }

  Future<http.Response> delete(String endpoint) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await _httpClient.delete(uri, headers: headers);
  }

  Future<Map<String, String>> _getHeaders() async {
    final headers = {
      'Content-Type': ApiConstants.contentTypeJson,
      'Accept': ApiConstants.acceptJson,
    };

    final token = await getToken();
    if (token != null) {
      headers['Authorization'] = 'Bearer $token';
    }

    return headers;
  }

  Map<String, dynamic> handleResponse(http.Response response) {
    final decodedBody = _decodeResponseBody(response.body);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      if (decodedBody is Map<String, dynamic>) {
        return decodedBody;
      }

      throw const FormatException('La respuesta de API no es un objeto JSON válido.');
    } else if (response.statusCode == 401) {
      throw Exception(AppConstants.unauthorizedError);
    } else if (response.statusCode >= 500) {
      throw Exception(AppConstants.serverError);
    } else {
      if (decodedBody is Map<String, dynamic>) {
        throw Exception(_extractErrorMessage(decodedBody));
      }

      throw Exception('Error desconocido');
    }
  }

  dynamic _decodeResponseBody(String body) {
    if (body.trim().isEmpty) {
      return <String, dynamic>{};
    }

    try {
      return jsonDecode(body);
    } on FormatException {
      throw const FormatException('La respuesta del servidor no contiene JSON válido.');
    }
  }

  String _extractErrorMessage(Map<String, dynamic> errorBody) {
    final message = errorBody['message'];
    if (message is String && message.isNotEmpty) {
      return message;
    }

    final errors = errorBody['errors'];
    if (errors is Map<String, dynamic>) {
      for (final value in errors.values) {
        if (value is List && value.isNotEmpty && value.first is String) {
          return value.first as String;
        }
        if (value is String && value.isNotEmpty) {
          return value;
        }
      }
    }

    return 'Error desconocido';
  }
}
