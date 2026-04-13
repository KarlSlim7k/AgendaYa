import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

import 'package:agenda_ya/data/providers/api_client.dart';

void main() {
  group('ApiClient', () {
    group('handleResponse', () {
      test('returns data on 200 OK', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        final result = client.handleResponse(
          http.Response('{"data":{"id":1}}', 200, headers: {'content-type': 'application/json'}),
        );

        expect(result, isA<Map<String, dynamic>>());
        expect(result['data'], {'id': 1});
      });

      test('throws on 401 Unauthorized', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 401)),
          usePersistentStorage: false,
        );

        expect(
          () => client.handleResponse(http.Response('{"message":"Unauthorized"}', 401)),
          throwsA(isA<Exception>()),
        );
      });

      test('throws on 500 Server Error', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 500)),
          usePersistentStorage: false,
        );

        expect(
          () => client.handleResponse(http.Response('{"error":"Internal"}', 500)),
          throwsA(isA<Exception>()),
        );
      });

      test('extracts error message from validation errors', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 422)),
          usePersistentStorage: false,
        );

        final body = jsonEncode({
          'message': 'Los datos proporcionados no son válidos.',
          'errors': {'email': ['El email ya está registrado.']},
        });

        expect(
          () => client.handleResponse(http.Response(body, 422)),
          throwsA(predicate((e) => e.toString().contains('Los datos proporcionados'))),
        );
      });

      test('throws on invalid JSON response', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        expect(
          () => client.handleResponse(http.Response('not-json', 200)),
          throwsA(isA<FormatException>()),
        );
      });

      test('handles empty body as empty map', () {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 204)),
          usePersistentStorage: false,
        );

        final result = client.handleResponse(http.Response('', 204));
        expect(result, {});
      });
    });

    group('token management', () {
      test('setToken stores token in memory', () async {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        await client.setToken('test_token_123');
        final token = await client.getToken();
        expect(token, 'test_token_123');
      });

      test('clearToken removes token from memory', () async {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        await client.setToken('test_token_123');
        await client.clearToken();
        final token = await client.getToken();
        expect(token, isNull);
      });

      test('hasPersistedToken returns true when token exists', () async {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        await client.setToken('test_token_123');
        final hasToken = await client.hasPersistedToken();
        expect(hasToken, isTrue);
      });

      test('hasPersistedToken returns false when no token', () async {
        final client = ApiClient(
          httpClient: MockClient((_) async => http.Response('', 200)),
          usePersistentStorage: false,
        );

        final hasToken = await client.hasPersistedToken();
        expect(hasToken, isFalse);
      });
    });

    group('HTTP methods', () {
      test('GET sends request with correct path', () async {
        final mockClient = MockClient((request) async {
          expect(request.method, 'GET');
          expect(request.url.path, '/api/v1/test-endpoint');
          expect(request.headers['Content-Type'], 'application/json');
          return http.Response('{"data":{}}', 200, headers: {'content-type': 'application/json'});
        });

        final client = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );

        await client.get('/test-endpoint');
      });

      test('POST sends request with JSON body', () async {
        final mockClient = MockClient((request) async {
          expect(request.method, 'POST');
          expect(request.url.path, '/api/v1/test-endpoint');
          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['email'], 'test@example.com');
          expect(body['password'], 'secret123');
          return http.Response('{"data":{}}', 200, headers: {'content-type': 'application/json'});
        });

        final client = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );

        await client.post(
          '/test-endpoint',
          body: {'email': 'test@example.com', 'password': 'secret123'},
        );
      });

      test('PUT sends request with JSON body', () async {
        final mockClient = MockClient((request) async {
          expect(request.method, 'PUT');
          expect(request.url.path, '/api/v1/test-endpoint');
          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['name'], 'Updated Name');
          return http.Response('{"data":{}}', 200, headers: {'content-type': 'application/json'});
        });

        final client = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );

        await client.put('/test-endpoint', body: {'name': 'Updated Name'});
      });

      test('PATCH sends request with JSON body', () async {
        final mockClient = MockClient((request) async {
          expect(request.method, 'PATCH');
          expect(request.url.path, '/api/v1/test-endpoint');
          return http.Response('{"data":{}}', 200, headers: {'content-type': 'application/json'});
        });

        final client = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );

        await client.patch('/test-endpoint', body: {'name': 'Patched'});
      });

      test('DELETE sends request', () async {
        final mockClient = MockClient((request) async {
          expect(request.method, 'DELETE');
          expect(request.url.path, '/api/v1/test-endpoint');
          return http.Response('{"data":{}}', 200, headers: {'content-type': 'application/json'});
        });

        final client = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );

        await client.delete('/test-endpoint');
      });
    });
  });
}
