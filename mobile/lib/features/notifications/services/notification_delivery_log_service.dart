import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/core/constants/app_constants.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';

class NotificationDeliveryLogService {
  static const int _maxLogs = 300;

  Future<List<NotificationDeliveryLog>> getLogs({
    int? appointmentId,
    int limit = 80,
  }) async {
    final logs = await _readLogs();

    final filtered = appointmentId == null
        ? logs
        : logs.where((log) => log.appointmentId == appointmentId).toList();

    filtered.sort((a, b) => b.createdAt.compareTo(a.createdAt));
    if (filtered.length <= limit) {
      return filtered;
    }

    return filtered.take(limit).toList();
  }

  Future<void> addLog(NotificationDeliveryLog log) async {
    final logs = await _readLogs();
    logs.add(log);

    logs.sort((a, b) => b.createdAt.compareTo(a.createdAt));
    final trimmed = logs.length > _maxLogs
        ? logs.take(_maxLogs).toList()
        : logs;

    await _writeLogs(trimmed);
  }

  Future<void> clearLogs() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(AppConstants.notificationLogsKey);
  }

  Future<List<NotificationDeliveryLog>> _readLogs() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(AppConstants.notificationLogsKey);

    if (raw == null || raw.isEmpty) {
      return <NotificationDeliveryLog>[];
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! List) {
        return <NotificationDeliveryLog>[];
      }

      return decoded
          .whereType<Map>()
          .map(
            (item) => NotificationDeliveryLog.fromJson(
              Map<String, dynamic>.from(item),
            ),
          )
          .toList();
    } catch (_) {
      return <NotificationDeliveryLog>[];
    }
  }

  Future<void> _writeLogs(List<NotificationDeliveryLog> logs) async {
    final prefs = await SharedPreferences.getInstance();
    final encoded = jsonEncode(logs.map((log) => log.toJson()).toList());
    await prefs.setString(AppConstants.notificationLogsKey, encoded);
  }
}
