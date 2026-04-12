import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:intl/intl.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/core/constants/app_constants.dart';
import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/core/services/browser_notification_service.dart';

class LocalNotificationService {
  LocalNotificationService._();

  static final LocalNotificationService instance = LocalNotificationService._();

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();

  bool _initialized = false;
  void Function(String payload)? _onNotificationTap;
  String? _pendingNavigationPayload;

  static const int _sessionReminderNotificationId = 1001;

  Future<void> initialize({void Function(String payload)? onNotificationTap}) async {
    if (onNotificationTap != null) {
      _onNotificationTap = onNotificationTap;
    }

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

    await _plugin.initialize(
      settings,
      onDidReceiveNotificationResponse: (response) {
        _handleNotificationTap(response.payload);
      },
    );

    final launchDetails = await _plugin.getNotificationAppLaunchDetails();
    if (launchDetails?.didNotificationLaunchApp ?? false) {
      _handleNotificationTap(launchDetails?.notificationResponse?.payload);
    }

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

  Future<bool> showAppointmentConfirmationNotification({
    required int appointmentId,
    required String serviceName,
    required DateTime startAt,
  }) async {
    if (kIsWeb) {
      final enabled = await _areBrowserNotificationsEnabled();
      if (!enabled) {
        return false;
      }

      final appointmentDate =
          DateFormat('dd MMM, HH:mm', 'es').format(startAt.toLocal());
      return browserNotificationService.showNotification(
        title: 'Reserva confirmada',
        body: '$serviceName • $appointmentDate',
        tag: 'appointment-confirmed-$appointmentId',
      );
    }

    await initialize();

    final appointmentDate =
        DateFormat('dd MMM, HH:mm', 'es').format(startAt.toLocal());
    final payload = AppRoutes.appointmentDetailDeepLink(appointmentId);

    await _plugin.show(
      200000 + appointmentId,
      'Reserva confirmada',
      '$serviceName • $appointmentDate',
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'appointment-confirmations',
          'Confirmaciones de cita',
          channelDescription: 'Notificaciones locales de confirmacion de citas',
          importance: Importance.max,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
        macOS: DarwinNotificationDetails(),
      ),
      payload: payload,
    );

    return true;
  }

  Future<bool> showAppointmentReminderNotification({
    required int appointmentId,
    required String serviceName,
    required DateTime startAt,
    required int hoursBefore,
  }) async {
    final reminderText =
        hoursBefore == 24 ? 'Tu cita es mañana.' : 'Tu cita es en 1 hora.';
    final appointmentDate =
        DateFormat('dd MMM, HH:mm', 'es').format(startAt.toLocal());

    if (kIsWeb) {
      final enabled = await _areBrowserNotificationsEnabled();
      if (!enabled) {
        return false;
      }

      return browserNotificationService.showNotification(
        title: 'Recordatorio de cita',
        body: '$reminderText $serviceName • $appointmentDate',
        tag: 'appointment-reminder-$appointmentId-$hoursBefore',
      );
    }

    await initialize();

    await _plugin.show(
      _reminderNotificationId(appointmentId, hoursBefore),
      'Recordatorio de cita',
      '$reminderText $serviceName • $appointmentDate',
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'appointment-reminders',
          'Recordatorios de cita',
          channelDescription: 'Recordatorios locales de citas agendadas',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
        macOS: DarwinNotificationDetails(),
      ),
      payload: AppRoutes.appointmentDetailDeepLink(appointmentId),
    );

    return true;
  }

  Future<void> cancelAppointmentReminderNotifications(int appointmentId) async {
    if (kIsWeb) {
      return;
    }

    await initialize();
    await _plugin.cancel(_reminderNotificationId(appointmentId, 24));
    await _plugin.cancel(_reminderNotificationId(appointmentId, 1));
  }

  int _reminderNotificationId(int appointmentId, int hoursBefore) {
    final slot = hoursBefore == 24 ? 1 : 2;
    return 300000 + (appointmentId * 10) + slot;
  }

  Future<bool> _areBrowserNotificationsEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(AppConstants.browserNotificationsEnabledKey) ?? true;
  }

  void setPendingNavigationPayload(String payload) {
    _pendingNavigationPayload = payload;
  }

  String? consumePendingNavigationPayload() {
    final payload = _pendingNavigationPayload;
    _pendingNavigationPayload = null;
    return payload;
  }

  void _handleNotificationTap(String? payload) {
    if (payload == null || payload.isEmpty) {
      return;
    }

    if (_onNotificationTap != null) {
      _onNotificationTap!(payload);
      return;
    }

    _pendingNavigationPayload = payload;
  }
}
