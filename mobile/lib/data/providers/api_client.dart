import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import '../core/constants/api_constants.dart';
import '../core/constants/app_constants.dart';

class ApiClient {
  static final ApiClient _instance = ApiClient._internal();
  factory ApiClient() => _instance;
  ApiClient._internal();

  final _storage = const FlutterSecureStorage();
  String? _token;

  Future<void> setToken(String token) async {
    _token = token;
    await _storage.write(key: AppConstants.authTokenKey, value: token);
  }

  Future<String?> getToken() async {
    _token ??= await _storage.read(key: AppConstants.authTokenKey);
    return _token;
  }

  Future<void> clearToken() async {
    _token = null;
    await _storage.delete(key: AppConstants.authTokenKey);
  }

  Future<http.Response> get(String endpoint, {Map<String, String>? queryParams}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final uriWithParams = queryParams != null 
        ? uri.replace(queryParameters: queryParams) 
        : uri;

    final headers = await _getHeaders();
    return await http.get(uriWithParams, headers: headers);
  }

  Future<http.Response> post(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await http.post(
      uri,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
  }

  Future<http.Response> patch(String endpoint, {Map<String, dynamic>? body}) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await http.patch(
      uri,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
  }

  Future<http.Response> delete(String endpoint) async {
    final uri = Uri.parse('${ApiConstants.baseUrl}$endpoint');
    final headers = await _getHeaders();
    
    return await http.delete(uri, headers: headers);
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
    if (response.statusCode >= 200 && response.statusCode < 300) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      throw Exception(AppConstants.unauthorizedError);
    } else if (response.statusCode >= 500) {
      throw Exception(AppConstants.serverError);
    } else {
      final errorBody = jsonDecode(response.body);
      throw Exception(errorBody['message'] ?? 'Error desconocido');
    }
  }
}
