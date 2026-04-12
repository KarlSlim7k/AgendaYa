import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

import 'package:agenda_ya/data/providers/api_client.dart';
import 'package:agenda_ya/data/providers/appointment_service.dart';

void main() {
  group('AppointmentService', () {
    test('getAvailableSlots parses timezone and slot payload', () async {
      final mockClient = MockClient((request) async {
        expect(request.url.path, '/api/v1/availability/slots');
        expect(request.url.queryParameters['business_id'], '10');
        expect(request.url.queryParameters['service_id'], '5');
        expect(request.url.queryParameters['fecha_inicio'], '2026-04-12');
        expect(request.url.queryParameters['fecha_fin'], '2026-04-12');

        return http.Response(
          '{"data":[{"slot":"2026-04-12T14:00:00Z","employee_id":7,"employee_name":"Ana"}],"meta":{"timezone":"America/Mexico_City"}}',
          200,
          headers: {'content-type': 'application/json'},
        );
      });

      final apiClient = ApiClient(
        httpClient: mockClient,
        usePersistentStorage: false,
      );
      final service = AppointmentService(apiClient: apiClient);

      final slots = await service.getAvailableSlots(
        businessId: 10,
        serviceId: 5,
        fechaInicio: DateTime(2026, 4, 12),
      );

      expect(slots, hasLength(1));
      expect(slots.first.employeeId, 7);
      expect(slots.first.employeeName, 'Ana');
      expect(slots.first.sourceTimezone, 'America/Mexico_City');
      expect(slots.first.startAt.toUtc(), DateTime.parse('2026-04-12T14:00:00Z'));
    });

    test('createAppointment parses nested envelope payload', () async {
      final mockClient = MockClient((request) async {
        expect(request.url.path, '/api/v1/appointments');
        expect(request.method, 'POST');

        return http.Response(
          '{"data":{"data":{"id":99,"business_id":2,"user_id":3,"service_id":4,"employee_id":6,"fecha_hora_inicio":"2026-05-10T15:00:00Z","fecha_hora_fin":"2026-05-10T15:30:00Z","estado":"confirmed","created_at":"2026-04-12T10:00:00Z","service":{"nombre":"Corte"},"business":{"nombre":"Agenda MX"},"employee":{"nombre":"Ana"}}}}',
          201,
          headers: {'content-type': 'application/json'},
        );
      });

      final apiClient = ApiClient(
        httpClient: mockClient,
        usePersistentStorage: false,
      );
      final service = AppointmentService(apiClient: apiClient);

      final appointment = await service.createAppointment(
        businessId: 2,
        serviceId: 4,
        employeeId: 6,
        fechaHoraInicio: DateTime.parse('2026-05-10T15:00:00Z'),
      );

      expect(appointment.id, 99);
      expect(appointment.serviceName, 'Corte');
      expect(appointment.businessName, 'Agenda MX');
      expect(appointment.employeeName, 'Ana');
      expect(appointment.isConfirmed, isTrue);
    });

    test('getMyAppointments parses plain list response', () async {
      final mockClient = MockClient((request) async {
        expect(request.url.path, '/api/v1/appointments');

        return http.Response(
          '{"data":[{"id":1,"business_id":1,"user_id":3,"service_id":10,"employee_id":20,"fecha_hora_inicio":"2026-04-20T18:00:00Z","fecha_hora_fin":"2026-04-20T18:30:00Z","estado":"confirmed","created_at":"2026-04-12T09:00:00Z"},{"id":2,"business_id":1,"user_id":3,"service_id":11,"employee_id":21,"fecha_hora_inicio":"2026-03-20T18:00:00Z","fecha_hora_fin":"2026-03-20T18:30:00Z","estado":"completed","created_at":"2026-03-12T09:00:00Z"}]}',
          200,
          headers: {'content-type': 'application/json'},
        );
      });

      final apiClient = ApiClient(
        httpClient: mockClient,
        usePersistentStorage: false,
      );
      final service = AppointmentService(apiClient: apiClient);

      final appointments = await service.getMyAppointments();

      expect(appointments, hasLength(2));
      expect(appointments.first.id, 1);
      expect(appointments.last.isCompleted, isTrue);
    });
  });
}
