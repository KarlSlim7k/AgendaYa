import 'package:flutter/material.dart';

import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/services/notification_delivery_log_service.dart';
import 'package:agenda_ya/features/notifications/services/notification_preferences_service.dart';

class NotificationProvider with ChangeNotifier {
  final NotificationPreferencesService _preferencesService =
      NotificationPreferencesService();
  final NotificationDeliveryLogService _logService =
      NotificationDeliveryLogService();

  bool _isLoading = false;
  bool _whatsAppRemindersEnabled = false;
  bool _browserNotificationsEnabled = true;
  List<NotificationDeliveryLog> _logs = <NotificationDeliveryLog>[];

  bool get isLoading => _isLoading;
  bool get whatsAppRemindersEnabled => _whatsAppRemindersEnabled;
  bool get browserNotificationsEnabled => _browserNotificationsEnabled;
  List<NotificationDeliveryLog> get logs => _logs;

  Future<void> initialize() async {
    _isLoading = true;
    notifyListeners();

    _whatsAppRemindersEnabled =
        await _preferencesService.getWhatsAppRemindersEnabled();
    _browserNotificationsEnabled =
        await _preferencesService.getBrowserNotificationsEnabled();
    _logs = await _logService.getLogs();

    _isLoading = false;
    notifyListeners();
  }

  Future<void> refreshLogs({int limit = 80}) async {
    _logs = await _logService.getLogs(limit: limit);
    notifyListeners();
  }

  Future<void> setWhatsAppRemindersEnabled(bool value) async {
    _whatsAppRemindersEnabled = value;
    await _preferencesService.setWhatsAppRemindersEnabled(value);
    notifyListeners();
  }

  Future<void> setBrowserNotificationsEnabled(bool value) async {
    _browserNotificationsEnabled = value;
    await _preferencesService.setBrowserNotificationsEnabled(value);
    notifyListeners();
  }
}
