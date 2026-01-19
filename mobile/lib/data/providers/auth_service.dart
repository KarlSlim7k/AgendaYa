import '../core/constants/api_constants.dart';
import '../models/user.dart';
import 'api_client.dart';

class AuthService {
  final ApiClient _apiClient = ApiClient();

  Future<Map<String, dynamic>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? telefono,
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
    
    // Guardar token
    final token = data['token'] as String;
    await _apiClient.setToken(token);
    
    return {
      'user': User.fromJson(data['user']),
      'token': token,
    };
  }

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.login,
      body: {
        'email': email,
        'password': password,
      },
    );

    final data = _apiClient.handleResponse(response);
    
    // Guardar token
    final token = data['token'] as String;
    await _apiClient.setToken(token);
    
    return {
      'user': User.fromJson(data['user']),
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
    
    return User.fromJson(data['user']);
  }

  Future<bool> isAuthenticated() async {
    final token = await _apiClient.getToken();
    return token != null && token.isNotEmpty;
  }
}
