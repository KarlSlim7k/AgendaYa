import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/data/providers/appointment_service.dart';
import 'package:agenda_ya/features/auth/providers/auth_provider.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/providers/notification_provider.dart';
import 'package:agenda_ya/features/notifications/services/notification_coordinator_service.dart';
import 'package:agenda_ya/features/profile/screens/profile_screen.dart';

class _FakeNotificationCoordinatorService extends NotificationCoordinatorService {
  @override
  Future<void> onAppointmentConfirmed(Appointment appointment) async {}

  @override
  Future<void> onAppointmentsSynced(List<Appointment> appointments) async {}

  @override
  Future<void> onAppointmentCancelled(Appointment appointment) async {}

  @override
  Future<void> processPendingLocalReminders() async {}
}

class _FakeAppointmentService extends AppointmentService {
  @override
  Future<List<Appointment>> getMyAppointments({
    String? estado,
    bool? futuras,
    bool? pasadas,
  }) async {
    return [
      Appointment(
        id: 1,
        businessId: 10,
        userId: 1,
        serviceId: 5,
        employeeId: 2,
        fechaHoraInicio: DateTime.now().add(const Duration(days: 1)),
        fechaHoraFin: DateTime.now().add(const Duration(days: 1, minutes: 30)),
        estado: 'confirmed',
        createdAt: DateTime.now(),
        serviceName: 'Corte Premium',
        businessName: 'Peluqueria Centro',
        employeeName: 'Ana',
      ),
      Appointment(
        id: 2,
        businessId: 10,
        userId: 1,
        serviceId: 5,
        employeeId: 2,
        fechaHoraInicio: DateTime.now().subtract(const Duration(days: 3)),
        fechaHoraFin: DateTime.now().subtract(const Duration(days: 3)).add(const Duration(minutes: 30)),
        estado: 'completed',
        createdAt: DateTime.now(),
        serviceName: 'Corte Clásico',
        businessName: 'Peluqueria Centro',
        employeeName: 'Ana',
      ),
    ];
  }
}

class _FakeAuthProvider extends AuthProvider {
  bool _biometricEnabled = false;

  @override
  User? get user => User(
        id: 1,
        name: 'Cliente QA',
        email: 'cliente.qa@agendaya.mx',
        telefono: '+525512341234',
        emailVerifiedAt: DateTime.now(),
      );

  @override
  bool get biometricAvailable => true;

  @override
  bool get biometricEnabled => _biometricEnabled;

  @override
  Future<void> initializeSecurityState() async {}

  @override
  Future<bool> toggleBiometric(bool enable) async {
    _biometricEnabled = enable;
    notifyListeners();
    return true;
  }

  @override
  Future<void> logout() async {}

  @override
  Future<bool> updateProfile({required String name, String? telefono}) async {
    return true;
  }
}

class _FakeNotificationProvider extends NotificationProvider {
  bool _whatsappEnabled = false;

  @override
  bool get whatsAppRemindersEnabled => _whatsappEnabled;

  @override
  List<NotificationDeliveryLog> get logs => const [
        NotificationDeliveryLog(
          id: 'l1',
          appointmentId: 1,
          channel: 'email',
          event: 'confirmacion',
          status: 'enviado',
          message: 'Confirmación por backend',
          createdAt: DateTime(2026, 4, 12, 12, 0),
        ),
      ];

  @override
  Future<void> initialize() async {}

  @override
  Future<void> refreshLogs({int limit = 80}) async {}

  @override
  Future<void> setWhatsAppRemindersEnabled(bool value) async {
    _whatsappEnabled = value;
    notifyListeners();
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('profile flow renders upcoming/past tabs with notification controls',
      (tester) async {
    final appointmentProvider = AppointmentProvider(
      appointmentService: _FakeAppointmentService(),
      notificationCoordinatorService: _FakeNotificationCoordinatorService(),
    );

    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthProvider>(
            create: (_) => _FakeAuthProvider(),
          ),
          ChangeNotifierProvider<AppointmentProvider>.value(
            value: appointmentProvider,
          ),
          ChangeNotifierProvider<NotificationProvider>(
            create: (_) => _FakeNotificationProvider(),
          ),
        ],
        child: const MaterialApp(
          home: ProfileScreen(),
        ),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('Mi Perfil'), findsOneWidget);
    expect(find.text('Recordatorios WhatsApp'), findsOneWidget);
    expect(find.text('Próximas'), findsOneWidget);
    expect(find.text('Pasadas'), findsOneWidget);

    await tester.tap(find.text('Pasadas'));
    await tester.pumpAndSettle();

    expect(find.text('Corte Clásico'), findsOneWidget);
  });
}
