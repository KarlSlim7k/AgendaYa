import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

import 'package:agenda_ya/data/providers/api_client.dart';
import 'package:agenda_ya/data/providers/business_service.dart';

void main() {
  group('BusinessService', () {
    group('searchBusinesses', () {
      test('returns paginated list with meta', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/businesses');
          expect(request.url.queryParameters['category'], 'peluqueria');
          expect(request.url.queryParameters['page'], '1');

          return http.Response(
            jsonEncode({
              'data': [
                {
                  'id': 1,
                  'nombre': 'Peluquería Elegante',
                  'categoria': 'peluqueria',
                  'estado': 'active',
                  'telefono': '+525512345678',
                  'email': 'pelu@email.com',
                  'locations': [],
                },
              ],
              'meta': {
                'current_page': 1,
                'last_page': 3,
                'per_page': 15,
                'total': 42,
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
        final service = BusinessService(apiClient: apiClient);

        final result = await service.searchBusinesses(category: 'peluqueria');

        expect(result['data'], isA<List>());
        expect(result['data'], hasLength(1));
        expect(result['meta']['last_page'], 3);
      });

      test('returns empty list when no results', () async {
        final mockClient = MockClient((request) async {
          return http.Response(
            jsonEncode({
              'data': [],
              'meta': {'current_page': 1, 'last_page': 0, 'per_page': 15, 'total': 0},
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = BusinessService(apiClient: apiClient);

        final result = await service.searchBusinesses(search: 'nonexistent');

        expect(result['data'], isEmpty);
      });
    });

    group('getBusinessDetail', () {
      test('returns business with locations', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/businesses/42');

          return http.Response(
            jsonEncode({
              'data': {
                'id': 42,
                'nombre': 'Spa Relax',
                'categoria': 'spa',
                'estado': 'active',
                'descripcion': 'Un spa increíble',
                'telefono': '+525598765432',
                'email': 'spa@email.com',
                'locations': [
                  {
                    'id': 1,
                    'nombre': 'Sucursal Centro',
                    'direccion': 'Calle 123',
                    'ciudad': 'CDMX',
                    'estado': 'CDMX',
                    'codigo_postal': '01000',
                    'activo': true,
                  },
                ],
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
        final service = BusinessService(apiClient: apiClient);

        final business = await service.getBusinessDetail(42);

        expect(business.id, 42);
        expect(business.nombre, 'Spa Relax');
        expect(business.locations, hasLength(1));
        expect(business.locations.first.nombre, 'Sucursal Centro');
      });
    });

    group('getBusinessServices', () {
      test('returns list of services for business', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/businesses/5/services');

          return http.Response(
            jsonEncode({
              'data': [
                {
                  'id': 10,
                  'business_id': 5,
                  'nombre': 'Masaje relajante',
                  'duracion_minutos': 60,
                  'precio': 500.0,
                  'activo': true,
                  'buffer_pre_minutos': 5,
                  'buffer_post_minutos': 5,
                },
              ],
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = BusinessService(apiClient: apiClient);

        final services = await service.getBusinessServices(5);

        expect(services, hasLength(1));
        expect(services.first.id, 10);
        expect(services.first.precio, 500.0);
        expect(services.first.duracionMinutos, 60);
      });
    });

    group('getBusinessEmployees', () {
      test('returns list of employees for business', () async {
        final mockClient = MockClient((request) async {
          expect(request.url.path, '/api/v1/businesses/5/employees');

          return http.Response(
            jsonEncode({
              'data': [
                {
                  'id': 20,
                  'business_id': 5,
                  'nombre': 'Ana García',
                  'email': 'ana@email.com',
                },
              ],
            }),
            200,
            headers: {'content-type': 'application/json'},
          );
        });

        final apiClient = ApiClient(
          httpClient: mockClient,
          usePersistentStorage: false,
        );
        final service = BusinessService(apiClient: apiClient);

        final employees = await service.getBusinessEmployees(5);

        expect(employees, hasLength(1));
        expect(employees.first.id, 20);
        expect(employees.first.nombre, 'Ana García');
      });
    });
  });
}
