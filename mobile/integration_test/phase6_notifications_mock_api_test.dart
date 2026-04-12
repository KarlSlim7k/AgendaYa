import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/providers/notification_provider.dart';

class _FakeNotificationProvider extends NotificationProvider {
  bool _whatsappEnabled = false;
  bool _browserEnabled = true;

  @override
  bool get whatsAppRemindersEnabled => _whatsappEnabled;

  @override
  bool get browserNotificationsEnabled => _browserEnabled;

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
        NotificationDeliveryLog(
          id: 'l2',
          appointmentId: 1,
          channel: 'whatsapp',
          event: 'recordatorio_24h',
          status: 'fallido',
          message: 'Fallback a email',
          createdAt: DateTime(2026, 4, 12, 12, 30),
        ),
      ];

  @override
  Future<void> initialize() async {}

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

class _NotificationSettingsHarness extends StatelessWidget {
  const _NotificationSettingsHarness();

  @override
  Widget build(BuildContext context) {
    return Consumer<NotificationProvider>(
      builder: (context, provider, child) {
        return Scaffold(
          body: Column(
            children: [
              SwitchListTile(
                title: const Text('Recordatorios WhatsApp'),
                value: provider.whatsAppRemindersEnabled,
                onChanged: provider.setWhatsAppRemindersEnabled,
              ),
              Text('Logs: ${provider.logs.length}'),
            ],
          ),
        );
      },
    );
  }
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('notification preferences and logs can be consumed by UI',
      (tester) async {
    await tester.pumpWidget(
      ChangeNotifierProvider<NotificationProvider>(
        create: (_) => _FakeNotificationProvider(),
        child: const MaterialApp(home: _NotificationSettingsHarness()),
      ),
    );

    await tester.pumpAndSettle();

    expect(find.text('Recordatorios WhatsApp'), findsOneWidget);
    expect(find.text('Logs: 2'), findsOneWidget);

    await tester.tap(find.byType(Switch));
    await tester.pumpAndSettle();

    final switchWidget = tester.widget<Switch>(find.byType(Switch));
    expect(switchWidget.value, isTrue);
  });
}
