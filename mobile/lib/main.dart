import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_web_plugins/url_strategy.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';

import 'package:agenda_ya/app.dart';
import 'package:agenda_ya/core/services/local_notification_service.dart';

/// Flag para detectar si se corre como integration test
const bool _isIntegrationTest = bool.fromEnvironment('INTEGRATION_TEST');

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  if (kIsWeb) {
    usePathUrlStrategy();
  }

  Intl.defaultLocale = 'es_MX';
  await initializeDateFormatting('es_MX', null);

  // En integration tests, saltar inicialización de notificaciones
  // para evitar diálogos de permisos que bloquean los tests
  if (!_isIntegrationTest) {
    await LocalNotificationService.instance.initialize(
      onNotificationTap: (payload) {
        final navigatorState = appNavigatorKey.currentState;
        if (navigatorState == null) {
          LocalNotificationService.instance.setPendingNavigationPayload(payload);
          return;
        }

        navigatorState.pushNamed(payload);
      },
    );
  }

  runApp(const AgendaYaApp());

  if (!_isIntegrationTest) {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final payload = LocalNotificationService.instance.consumePendingNavigationPayload();
      if (payload == null || payload.isEmpty) {
        return;
      }

      appNavigatorKey.currentState?.pushNamed(payload);
    });
  }
}
