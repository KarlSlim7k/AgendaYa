import 'package:flutter/foundation.dart';
import 'package:flutter_web_plugins/url_strategy.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';

import 'package:agenda_ya/app.dart';
import 'package:agenda_ya/core/services/local_notification_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  if (kIsWeb) {
    usePathUrlStrategy();
  }

  Intl.defaultLocale = 'es_MX';
  await initializeDateFormatting('es_MX', null);

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

  runApp(const AgendaYaApp());

  WidgetsBinding.instance.addPostFrameCallback((_) {
    final payload = LocalNotificationService.instance.consumePendingNavigationPayload();
    if (payload == null || payload.isEmpty) {
      return;
    }

    appNavigatorKey.currentState?.pushNamed(payload);
  });
}
