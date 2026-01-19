class Appointment {
  final int id;
  final int businessId;
  final int userId;
  final int serviceId;
  final int employeeId;
  final DateTime fechaHoraInicio;
  final DateTime fechaHoraFin;
  final String estado;
  final String? notasCliente;
  final String? notasInternas;
  final String? motivoCancelacion;
  final String? codigoConfirmacion;
  final Map<String, dynamic>? customData;
  final DateTime createdAt;

  // Relationships (cuando están eager loaded)
  final String? businessName;
  final String? serviceName;
  final String? employeeName;

  Appointment({
    required this.id,
    required this.businessId,
    required this.userId,
    required this.serviceId,
    required this.employeeId,
    required this.fechaHoraInicio,
    required this.fechaHoraFin,
    required this.estado,
    this.notasCliente,
    this.notasInternas,
    this.motivoCancelacion,
    this.codigoConfirmacion,
    this.customData,
    required this.createdAt,
    this.businessName,
    this.serviceName,
    this.employeeName,
  });

  factory Appointment.fromJson(Map<String, dynamic> json) {
    return Appointment(
      id: json['id'] as int,
      businessId: json['business_id'] as int,
      userId: json['user_id'] as int,
      serviceId: json['service_id'] as int,
      employeeId: json['employee_id'] as int,
      fechaHoraInicio: DateTime.parse(json['fecha_hora_inicio'] as String),
      fechaHoraFin: DateTime.parse(json['fecha_hora_fin'] as String),
      estado: json['estado'] as String,
      notasCliente: json['notas_cliente'] as String?,
      notasInternas: json['notas_internas'] as String?,
      motivoCancelacion: json['motivo_cancelacion'] as String?,
      codigoConfirmacion: json['codigo_confirmacion'] as String?,
      customData: json['custom_data'] as Map<String, dynamic>?,
      createdAt: DateTime.parse(json['created_at'] as String),
      businessName: json['business']?['nombre'] as String?,
      serviceName: json['service']?['nombre'] as String?,
      employeeName: json['employee']?['nombre'] as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'business_id': businessId,
      'user_id': userId,
      'service_id': serviceId,
      'employee_id': employeeId,
      'fecha_hora_inicio': fechaHoraInicio.toIso8601String(),
      'fecha_hora_fin': fechaHoraFin.toIso8601String(),
      'estado': estado,
      'notas_cliente': notasCliente,
      'custom_data': customData,
    };
  }

  bool get isPending => estado == 'pending';
  bool get isConfirmed => estado == 'confirmed';
  bool get isCancelled => estado == 'cancelled';
  bool get isCompleted => estado == 'completed';
  bool get canCancel => isPending || isConfirmed;
}
