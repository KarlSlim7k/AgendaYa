import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class LocalNotificationService {
  LocalNotificationService._();

  static final LocalNotificationService instance = LocalNotificationService._();

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();

  bool _initialized = false;

  static const int _sessionReminderNotificationId = 1001;

  Future<void> initialize() async {
    if (kIsWeb) {
      return;
    }

    if (_initialized) {
      return;
    }

    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const darwinSettings = DarwinInitializationSettings();

    const settings = InitializationSettings(
      android: androidSettings,
      iOS: darwinSettings,
      macOS: darwinSettings,
    );

    await _plugin.initialize(settings);

    await _plugin
        .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin
        >()
        ?.requestNotificationsPermission();

    await _plugin
        .resolvePlatformSpecificImplementation<
          DarwinFlutterLocalNotificationsPlugin
        >()
        ?.requestPermissions(
          alert: true,
          badge: true,
          sound: true,
        );

    _initialized = true;
  }

  Future<void> scheduleSessionReminder() async {
    if (kIsWeb) {
      return;
    }

    await initialize();

    await _plugin.periodicallyShow(
      _sessionReminderNotificationId,
      'AgendaYa',
      'Tu sesión está activa. Revisa tus próximas citas.',
      RepeatInterval.daily,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'session-reminders',
          'Recordatorios de sesión',
          channelDescription: 'Recordatorios locales de seguridad de sesión',
          importance: Importance.defaultImportance,
          priority: Priority.defaultPriority,
        ),
        iOS: DarwinNotificationDetails(),
        macOS: DarwinNotificationDetails(),
      ),
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
    );
  }

  Future<void> cancelSessionReminder() async {
    if (kIsWeb) {
      return;
    }

    await initialize();
    await _plugin.cancel(_sessionReminderNotificationId);
  }
}
