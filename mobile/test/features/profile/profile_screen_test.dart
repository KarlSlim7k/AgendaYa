import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/features/auth/providers/auth_provider.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/providers/notification_provider.dart';
import 'package:agenda_ya/features/profile/screens/profile_screen.dart';

class _FakeAuthProvider extends AuthProvider {
  bool _biometricEnabled = false;

  @override
  User? get user => User(
        id: 1,
        name: 'Usuario QA',
        email: 'qa@agendaya.mx',
        telefono: '+525512345678',
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

class _FakeAppointmentProvider extends AppointmentProvider {
  _FakeAppointmentProvider() : super();

  @override
  Future<void> loadMyAppointments({
    String? estado,
    bool? futuras,
    bool? pasadas,
    bool showLoading = true,
  }) async {}
}

class _FakeNotificationProvider extends NotificationProvider {
  bool _whatsappEnabled = true;
  bool _browserEnabled = true;

  @override
  bool get whatsAppRemindersEnabled => _whatsappEnabled;

  @override
  bool get browserNotificationsEnabled => _browserEnabled;

  @override
  List<NotificationDeliveryLog> get logs => const [
        NotificationDeliveryLog(
          id: 'n1',
          appointmentId: 11,
          channel: 'email',
          event: 'confirmacion',
          status: 'enviado',
          message: 'Confirmación enviada',
          createdAt: DateTime(2026, 4, 12, 9, 0),
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

  @override
  Future<void> setBrowserNotificationsEnabled(bool value) async {
    _browserEnabled = value;
    notifyListeners();
  }
}

void main() {
  testWidgets('ProfileScreen shows notification settings and traceability block',
      (tester) async {
    await tester.pumpWidget(
      MultiProvider(
        providers: [
          ChangeNotifierProvider<AuthProvider>(
            create: (_) => _FakeAuthProvider(),
          ),
          ChangeNotifierProvider<AppointmentProvider>(
            create: (_) => _FakeAppointmentProvider(),
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
    expect(find.text('Trazabilidad de notificaciones'), findsOneWidget);
    expect(find.textContaining('email • confirmacion • enviado'), findsOneWidget);
  });
}
