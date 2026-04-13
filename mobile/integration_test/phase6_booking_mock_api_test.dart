import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/models/available_slot.dart';
import 'package:agenda_ya/data/providers/appointment_service.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/booking/screens/booking_screen.dart';
import 'package:agenda_ya/features/notifications/services/notification_coordinator_service.dart';

class _FakeNotificationCoordinatorService extends NotificationCoordinatorService {
  int confirmDispatchCount = 0;

  @override
  Future<void> onAppointmentConfirmed(Appointment appointment) async {
    confirmDispatchCount += 1;
  }

  @override
  Future<void> onAppointmentsSynced(List<Appointment> appointments) async {}

  @override
  Future<void> onAppointmentCancelled(Appointment appointment) async {}

  @override
  Future<void> processPendingLocalReminders() async {}
}

class _FakeAppointmentService extends AppointmentService {
  _FakeAppointmentService();

  final List<Appointment> _appointments = [];

  @override
  Future<List<AvailableSlot>> getAvailableSlots({
    required int businessId,
    required int serviceId,
    required DateTime fechaInicio,
    DateTime? fechaFin,
    int? employeeId,
  }) async {
    return [
      AvailableSlot(
        startAt: DateTime(fechaInicio.year, fechaInicio.month, fechaInicio.day, 10),
        employeeId: 5,
        employeeName: 'Ana',
        sourceTimezone: 'America/Mexico_City',
      ),
      AvailableSlot(
        startAt: DateTime(fechaInicio.year, fechaInicio.month, fechaInicio.day, 11),
        employeeId: 5,
        employeeName: 'Ana',
        sourceTimezone: 'America/Mexico_City',
      ),
    ];
  }

  @override
  Future<Appointment> createAppointment({
    required int businessId,
    required int serviceId,
    required int employeeId,
    required DateTime fechaHoraInicio,
    String? notasCliente,
    Map<String, dynamic>? customData,
  }) async {
    final appointment = Appointment(
      id: 100 + _appointments.length,
      businessId: businessId,
      userId: 1,
      serviceId: serviceId,
      employeeId: employeeId,
      fechaHoraInicio: fechaHoraInicio,
      fechaHoraFin: fechaHoraInicio.add(const Duration(minutes: 30)),
      estado: 'confirmed',
      notasCliente: notasCliente,
      createdAt: DateTime.now(),
      serviceName: 'Servicio Mock',
      businessName: 'Negocio Mock',
      employeeName: 'Ana',
    );

    _appointments.insert(0, appointment);
    return appointment;
  }

  @override
  Future<List<Appointment>> getMyAppointments({
    String? estado,
    bool? futuras,
    bool? pasadas,
  }) async {
    return List<Appointment>.from(_appointments);
  }

  @override
  Future<Appointment> cancelAppointment(
    int appointmentId, {
    String? motivoCancelacion,
  }) async {
    final index = _appointments.indexWhere((item) => item.id == appointmentId);
    if (index == -1) {
      throw Exception('Appointment not found');
    }

    final previous = _appointments[index];
    final updated = Appointment(
      id: previous.id,
      businessId: previous.businessId,
      userId: previous.userId,
      serviceId: previous.serviceId,
      employeeId: previous.employeeId,
      fechaHoraInicio: previous.fechaHoraInicio,
      fechaHoraFin: previous.fechaHoraFin,
      estado: 'cancelled',
      notasCliente: previous.notasCliente,
      motivoCancelacion: motivoCancelacion,
      createdAt: previous.createdAt,
      serviceName: previous.serviceName,
      businessName: previous.businessName,
      employeeName: previous.employeeName,
    );

    _appointments[index] = updated;
    return updated;
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  setUpAll(() async {
    Intl.defaultLocale = 'es_MX';
    await initializeDateFormatting('es_MX', null);
  });

  testWidgets('booking flow works with mocked API service', (tester) async {
    final fakeService = _FakeAppointmentService();
    final fakeCoordinator = _FakeNotificationCoordinatorService();

    await tester.pumpWidget(
      ChangeNotifierProvider(
        create: (_) => AppointmentProvider(
          appointmentService: fakeService,
          notificationCoordinatorService: fakeCoordinator,
        ),
        child: const MaterialApp(
          home: BookingScreen(
            businessId: 10,
            serviceId: 5,
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('Reservar Cita'), findsOneWidget);
    expect(find.text('Horarios Disponibles'), findsOneWidget);

    await tester.tap(find.text('10:00'));
    await tester.pumpAndSettle();

    await tester.tap(find.text('Confirmar Reserva'));
    await tester.pumpAndSettle();

    expect(find.text('¡Reserva Exitosa!'), findsOneWidget);
    expect(fakeCoordinator.confirmDispatchCount, 1);
  });
}
