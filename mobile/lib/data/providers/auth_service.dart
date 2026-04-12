import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/data/models/user.dart';

import 'api_client.dart';

class AuthService {
  final ApiClient _apiClient = ApiClient();

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? telefono,
    bool rememberSession = true,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.register,
      body: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        if (telefono != null) 'telefono': telefono,
      },
    );

    final data = _apiClient.handleResponse(response);

    final payload = _extractPayload(data);
    final token = payload['token'] as String?;
    final userJson = payload['user'];

    if (token == null || userJson is! Map<String, dynamic>) {
      throw const FormatException('Respuesta de registro inválida.');
    }

    await _apiClient.setToken(token, persist: rememberSession);

    return {
      'user': User.fromJson(userJson),
      'token': token,
    };
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
    bool rememberSession = true,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.login,
      body: {
        'email': email,
        'password': password,
      },
    );

    final data = _apiClient.handleResponse(response);

    final payload = _extractPayload(data);
    final token = payload['token'] as String?;
    final userJson = payload['user'];

    if (token == null || userJson is! Map<String, dynamic>) {
      throw const FormatException('Respuesta de login inválida.');
    }

    await _apiClient.setToken(token, persist: rememberSession);

    return {
      'user': User.fromJson(userJson),
      'token': token,
    };
  }

  Future<void> logout() async {
    final response = await _apiClient.post(ApiConstants.logout);
    _apiClient.handleResponse(response);
    
    // Limpiar token local
    await _apiClient.clearToken();
  }

  Future<User> getProfile() async {
    final response = await _apiClient.get(ApiConstants.profile);
    final data = _apiClient.handleResponse(response);

    final payload = _extractPayload(data);
    final userJson = payload['user'] ?? payload;
    if (userJson is! Map<String, dynamic>) {
      throw const FormatException('Respuesta de perfil inválida.');
    }

    return User.fromJson(userJson);
  }

  Future<User> updateProfile({
    required String name,
    String? telefono,
  }) async {
    final body = {
      'name': name,
      if (telefono != null) 'telefono': telefono,
    };

    Map<String, dynamic> data;

    try {
      final response = await _apiClient.put(
        ApiConstants.updateProfile,
        body: body,
      );
      data = _apiClient.handleResponse(response);
    } catch (_) {
      final response = await _apiClient.patch(
        ApiConstants.updateProfile,
        body: body,
      );
      data = _apiClient.handleResponse(response);
    }

    final payload = _extractPayload(data);
    final userJson = payload['user'] ?? payload;
    if (userJson is! Map<String, dynamic>) {
      throw const FormatException('Respuesta de actualización de perfil inválida.');
    }

    return User.fromJson(userJson);
  }

  Future<bool> isAuthenticated() async {
    final token = await _apiClient.getToken();
    return token != null && token.isNotEmpty;
  }

  Future<bool> hasPersistedSession() {
    return _apiClient.hasPersistedToken();
  }

  Future<void> updateSessionPersistence(bool persist) async {
    final token = await _apiClient.getToken();
    if (token == null || token.isEmpty) {
      return;
    }

    await _apiClient.setToken(token, persist: persist);
  }

  Future<void> clearLocalSession() async {
    await _apiClient.clearToken();
  }

  Map<String, dynamic> _extractPayload(Map<String, dynamic> data) {
    final payload = data['data'];
    if (payload is Map<String, dynamic>) {
      return payload;
    }

    return data;
  }
}
