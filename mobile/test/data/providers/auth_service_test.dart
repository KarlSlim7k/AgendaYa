import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

import 'package:agenda_ya/data/providers/api_client.dart';
import 'package:agenda_ya/data/providers/auth_service.dart';

void main() {
  group('AuthService', () {
    group('register', () {
      test('registers user and returns user + token', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/auth/register');
          expect(request.method, 'POST');

          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['name'], 'Test User');
          expect(body['email'], 'test@example.com');

          return http.Response(
            jsonEncode({
              'data': {
                'token': 'mock_token_abc123',
                'user': {
                  'id': 1,
                  'name': 'Test User',
                  'email': 'test@example.com',
                  'email_verified_at': '2026-04-12T10:00:00Z',
                },
              },
            }),
            201,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        final result = await service.register(
          name: 'Test User',
          email: 'test@example.com',
          password: 'SecurePass123!',
          passwordConfirmation: 'SecurePass123!',
          rememberSession: false,
        );

        expect(result['user'], isNotNull);
        expect(result['token'], 'mock_token_abc123');
        expect((result['user'] as dynamic).name, 'Test User');
      });

      test('throws on validation error', () async {
        final mockClient = MockClient((request) async {
          return http.Response(
            jsonEncode({
              'message': 'El email ya está registrado.',
              'errors': {'email': ['El email ya está registrado.']},
            }),
            422,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        expect(
          () => service.register(
            name: 'Test User',
            email: 'duplicate@example.com',
            password: 'SecurePass123!',
            passwordConfirmation: 'SecurePass123!',
            rememberSession: false,
          ),
          throwsA(predicate((e) => e.toString().contains('email'))),
        );
      });
    });

    group('login', () {
      test('logs in user and returns user + token', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/auth/login');
          expect(request.method, 'POST');

          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['email'], 'user@example.com');

          return http.Response(
            jsonEncode({
              'data': {
                'token': 'session_token_xyz',
                'user': {
                  'id': 5,
                  'name': 'Existing User',
                  'email': 'user@example.com',
                  'email_verified_at': '2026-04-10T08:00:00Z',
                },
              },
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        final result = await service.login(
          email: 'user@example.com',
          password: 'password123',
          rememberSession: false,
        );

        expect(result['token'], 'session_token_xyz');
        expect((result['user'] as dynamic).id, 5);
      });

      test('throws on invalid credentials', () async {
        final mockClient = MockClient((request) async {
          return http.Response(
            jsonEncode({'message': 'Credenciales inválidas.'}),
            401,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        expect(
          () => service.login(
            email: 'wrong@example.com',
            password: 'wrongpass',
            rememberSession: false,
          ),
          throwsA(isA<Exception>()),
        );
      });
    });

    group('logout', () {
      test('calls logout endpoint and clears token', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/auth/logout');
          expect(request.method, 'POST');
          return http.Response('{"message":"Logged out"}', 200, headers: {'content-type': 'application/json'});
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        await service.logout();
      });
    });

    group('getProfile', () {
      test('returns user profile', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/user/profile');
          expect(request.method, 'GET');

          return http.Response(
            jsonEncode({
              'data': {
                'user': {
                  'id': 10,
                  'name': 'Profile User',
                  'email': 'profile@example.com',
                  'email_verified_at': '2026-04-11T12:00:00Z',
                },
              },
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        final user = await service.getProfile();

        expect(user.id, 10);
        expect(user.name, 'Profile User');
        expect(user.email, 'profile@example.com');
      });
    });

    group('updateProfile', () {
      test('updates user profile via PUT', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/user/profile');
          expect(request.method, 'PUT');

          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['name'], 'New Name');

          return http.Response(
            jsonEncode({
              'data': {
                'user': {
                  'id': 10,
                  'name': 'New Name',
                  'email': 'profile@example.com',
                  'email_verified_at': '2026-04-11T12:00:00Z',
                },
              },
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        final user = await service.updateProfile(name: 'New Name');

        expect(user.name, 'New Name');
      });
    });

    group('isAuthenticated', () {
      test('returns true when token is set', () async {
        final mockClient = MockClient((_) async => http.Response('', 200));

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        await apiClient.setToken('some_token');
        final authenticated = await service.isAuthenticated();

        expect(authenticated, isTrue);
      });

      test('returns false when no token', () async {
        final mockClient = MockClient((_) async => http.Response('', 200));

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = AuthService(apiClient: apiClient);

        final authenticated = await service.isAuthenticated();

        expect(authenticated, isFalse);
      });
    });
  });
}
