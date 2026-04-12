import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/data/models/available_slot.dart';

class SlotCacheResult {
  const SlotCacheResult({
    required this.slots,
    this.sourceTimezone,
  });

  final List<AvailableSlot> slots;
  final String? sourceTimezone;
}

class SlotCacheService {
  static const String _prefix = 'slot_cache_v1_';
  static const String _indexKey = 'slot_cache_v1_index';
  static const Duration _ttl = Duration(minutes: 5);

  Future<void> writeSlots({
    required int businessId,
    required int serviceId,
    required DateTime rangeStart,
    required DateTime rangeEnd,
    required List<AvailableSlot> slots,
    int? employeeId,
    String? sourceTimezone,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final key = _buildKey(
      businessId: businessId,
      serviceId: serviceId,
      rangeStart: rangeStart,
      rangeEnd: rangeEnd,
      employeeId: employeeId,
    );

    final payload = {
      'business_id': businessId,
      'service_id': serviceId,
      'employee_id': employeeId,
      'range_start': _normalizeDate(rangeStart),
      'range_end': _normalizeDate(rangeEnd),
      'cached_at': DateTime.now().toUtc().toIso8601String(),
      'source_timezone': sourceTimezone,
      'slots': slots.map((slot) => slot.toJson()).toList(),
    };

    await prefs.setString(key, jsonEncode(payload));

    final index = await _readIndex(prefs);
    if (!index.contains(key)) {
      index.add(key);
      await prefs.setStringList(_indexKey, index);
    }
  }

  Future<SlotCacheResult?> readSlots({
    required int businessId,
    required int serviceId,
    required DateTime rangeStart,
    required DateTime rangeEnd,
    int? employeeId,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final key = _buildKey(
      businessId: businessId,
      serviceId: serviceId,
      rangeStart: rangeStart,
      rangeEnd: rangeEnd,
      employeeId: employeeId,
    );

    final raw = prefs.getString(key);
    if (raw == null || raw.isEmpty) {
      return null;
    }

    final payload = jsonDecode(raw) as Map<String, dynamic>;
    final cachedAtRaw = payload['cached_at'];
    if (cachedAtRaw is! String) {
      await prefs.remove(key);
      return null;
    }

    final cachedAt = DateTime.tryParse(cachedAtRaw);
    if (cachedAt == null || DateTime.now().toUtc().difference(cachedAt) > _ttl) {
      await prefs.remove(key);
      return null;
    }

    final slotsRaw = payload['slots'];
    if (slotsRaw is! List) {
      await prefs.remove(key);
      return null;
    }

    final slots = slotsRaw
        .whereType<Map>()
        .map((item) => AvailableSlot.fromCacheJson(Map<String, dynamic>.from(item)))
        .toList();

    return SlotCacheResult(
      slots: slots,
      sourceTimezone: payload['source_timezone'] as String?,
    );
  }

  Future<void> invalidateForBooking({
    required int businessId,
    required int serviceId,
    required DateTime appointmentDate,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final index = await _readIndex(prefs);
    final normalizedDate = _normalizeDate(appointmentDate);

    final keysToRemove = <String>[];

    for (final key in index) {
      final raw = prefs.getString(key);
      if (raw == null || raw.isEmpty) {
        keysToRemove.add(key);
        continue;
      }

      final payload = jsonDecode(raw) as Map<String, dynamic>;
      final bId = (payload['business_id'] as num?)?.toInt();
      final sId = (payload['service_id'] as num?)?.toInt();
      if (bId != businessId || sId != serviceId) {
        continue;
      }

      final rangeStart = payload['range_start'] as String?;
      final rangeEnd = payload['range_end'] as String?;
      if (rangeStart == null || rangeEnd == null) {
        keysToRemove.add(key);
        continue;
      }

      if (normalizedDate.compareTo(rangeStart) >= 0 &&
          normalizedDate.compareTo(rangeEnd) <= 0) {
        keysToRemove.add(key);
      }
    }

    if (keysToRemove.isEmpty) {
      return;
    }

    for (final key in keysToRemove) {
      await prefs.remove(key);
    }

    final updated = index.where((entry) => !keysToRemove.contains(entry)).toList();
    await prefs.setStringList(_indexKey, updated);
  }

  String _buildKey({
    required int businessId,
    required int serviceId,
    required DateTime rangeStart,
    required DateTime rangeEnd,
    int? employeeId,
  }) {
    return '$_prefix$businessId|$serviceId|${_normalizeDate(rangeStart)}|${_normalizeDate(rangeEnd)}|${employeeId ?? 0}';
  }

  String _normalizeDate(DateTime date) {
    final normalized = DateTime(date.year, date.month, date.day);
    return normalized.toIso8601String().split('T').first;
  }

  Future<List<String>> _readIndex(SharedPreferences prefs) async {
    return prefs.getStringList(_indexKey) ?? <String>[];
  }
}
