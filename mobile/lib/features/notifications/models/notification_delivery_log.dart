class NotificationDeliveryLog {
  const NotificationDeliveryLog({
    required this.id,
    required this.channel,
    required this.event,
    required this.status,
    required this.createdAt,
    this.appointmentId,
    this.message,
    this.metadata = const <String, dynamic>{},
  });

  final String id;
  final int? appointmentId;
  final String channel;
  final String event;
  final String status;
  final String? message;
  final DateTime createdAt;
  final Map<String, dynamic> metadata;

  factory NotificationDeliveryLog.fromJson(Map<String, dynamic> json) {
    final createdAtRaw = json['created_at'];
    final createdAt = createdAtRaw is String
        ? DateTime.tryParse(createdAtRaw)
        : null;

    return NotificationDeliveryLog(
      id: json['id'] as String,
      appointmentId: (json['appointment_id'] as num?)?.toInt(),
      channel: json['channel'] as String,
      event: json['event'] as String,
      status: json['status'] as String,
      message: json['message'] as String?,
      createdAt: createdAt ?? DateTime.now(),
      metadata: json['metadata'] is Map<String, dynamic>
          ? json['metadata'] as Map<String, dynamic>
          : <String, dynamic>{},
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'appointment_id': appointmentId,
      'channel': channel,
      'event': event,
      'status': status,
      'message': message,
      'created_at': createdAt.toIso8601String(),
      'metadata': metadata,
    };
  }
}
