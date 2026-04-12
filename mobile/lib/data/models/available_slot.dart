class AvailableSlot {
  const AvailableSlot({
    required this.startAt,
    this.endAt,
    this.employeeId,
    this.employeeName,
    this.sourceTimezone,
    this.isAvailable = true,
    this.raw = const <String, dynamic>{},
  });

  final DateTime startAt;
  final DateTime? endAt;
  final int? employeeId;
  final String? employeeName;
  final String? sourceTimezone;
  final bool isAvailable;
  final Map<String, dynamic> raw;

  DateTime get startAtLocal => startAt.isUtc ? startAt.toLocal() : startAt;

  DateTime? get endAtLocal {
    if (endAt == null) {
      return null;
    }

    return endAt!.isUtc ? endAt!.toLocal() : endAt!;
  }

  String get uniqueKey {
    return '${startAtLocal.toIso8601String()}|${employeeId ?? 0}';
  }

  Map<String, dynamic> toJson() {
    return {
      'start_at': startAt.toIso8601String(),
      'end_at': endAt?.toIso8601String(),
      'employee_id': employeeId,
      'employee_name': employeeName,
      'source_timezone': sourceTimezone,
      'is_available': isAvailable,
      'raw': raw,
    };
  }

  factory AvailableSlot.fromCacheJson(Map<String, dynamic> json) {
    final startAtRaw = json['start_at'];
    if (startAtRaw is! String) {
      throw const FormatException('Slot de cache inválido: start_at no encontrado.');
    }

    return AvailableSlot(
      startAt: DateTime.parse(startAtRaw),
      endAt: json['end_at'] is String ? DateTime.parse(json['end_at'] as String) : null,
      employeeId: (json['employee_id'] as num?)?.toInt(),
      employeeName: json['employee_name'] as String?,
      sourceTimezone: json['source_timezone'] as String?,
      isAvailable: json['is_available'] as bool? ?? true,
      raw: json['raw'] is Map<String, dynamic>
          ? json['raw'] as Map<String, dynamic>
          : <String, dynamic>{},
    );
  }

  factory AvailableSlot.fromJson(
    Map<String, dynamic> json, {
    String? timezoneHint,
  }) {
    final start =
        _parseDateTime(json['slot']) ??
        _parseDateTime(json['start_at']) ??
        _parseDateTime(json['start']) ??
        _parseDateTime(json['fecha_hora_inicio']) ??
        _parseDateTime(json['fecha_inicio']) ??
        _parseDateTime(json['datetime']);

    if (start == null) {
      throw const FormatException('Slot inválido: fecha de inicio no reconocida.');
    }

    final end =
        _parseDateTime(json['end_at']) ??
        _parseDateTime(json['end']) ??
        _parseDateTime(json['fecha_hora_fin']) ??
        _parseDateTime(json['fecha_fin']);

    final timezone =
        json['timezone'] as String? ??
        json['time_zone'] as String? ??
        json['zona_horaria'] as String? ??
        timezoneHint;

    final employeeId =
        (json['employee_id'] as num?)?.toInt() ??
        (json['empleado_id'] as num?)?.toInt();

    final employeeName =
        json['employee_name'] as String? ??
        json['employee']?['nombre'] as String? ??
        json['empleado']?['nombre'] as String?;

    return AvailableSlot(
      startAt: start,
      endAt: end,
      employeeId: employeeId,
      employeeName: employeeName,
      sourceTimezone: timezone,
      isAvailable: json['available'] as bool? ?? json['is_available'] as bool? ?? true,
      raw: json,
    );
  }

  static DateTime? _parseDateTime(dynamic value) {
    if (value is String && value.trim().isNotEmpty) {
      return DateTime.tryParse(value);
    }

    return null;
  }
}
