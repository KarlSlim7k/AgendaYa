class AppointmentReminderPlan {
  const AppointmentReminderPlan({
    required this.appointmentId,
    required this.serviceName,
    required this.startAt,
    required this.detailRoute,
    this.sent24h = false,
    this.sent1h = false,
  });

  final int appointmentId;
  final String serviceName;
  final DateTime startAt;
  final String detailRoute;
  final bool sent24h;
  final bool sent1h;

  AppointmentReminderPlan copyWith({
    String? serviceName,
    DateTime? startAt,
    String? detailRoute,
    bool? sent24h,
    bool? sent1h,
  }) {
    return AppointmentReminderPlan(
      appointmentId: appointmentId,
      serviceName: serviceName ?? this.serviceName,
      startAt: startAt ?? this.startAt,
      detailRoute: detailRoute ?? this.detailRoute,
      sent24h: sent24h ?? this.sent24h,
      sent1h: sent1h ?? this.sent1h,
    );
  }

  factory AppointmentReminderPlan.fromJson(Map<String, dynamic> json) {
    return AppointmentReminderPlan(
      appointmentId: (json['appointment_id'] as num).toInt(),
      serviceName: json['service_name'] as String,
      startAt: DateTime.parse(json['start_at'] as String),
      detailRoute: json['detail_route'] as String,
      sent24h: json['sent_24h'] as bool? ?? false,
      sent1h: json['sent_1h'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'appointment_id': appointmentId,
      'service_name': serviceName,
      'start_at': startAt.toIso8601String(),
      'detail_route': detailRoute,
      'sent_24h': sent24h,
      'sent_1h': sent1h,
    };
  }
}
