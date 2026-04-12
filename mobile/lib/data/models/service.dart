class Service {
  final int id;
  final int businessId;
  final String nombre;
  final String? descripcion;
  final int duracionMinutos;
  final double precio;
  final int bufferPreMinutos;
  final int bufferPostMinutos;
  final bool activo;
  final Map<String, dynamic>? meta;

  Service({
    required this.id,
    required this.businessId,
    required this.nombre,
    this.descripcion,
    required this.duracionMinutos,
    required this.precio,
    this.bufferPreMinutos = 0,
    this.bufferPostMinutos = 0,
    required this.activo,
    this.meta,
  });

  factory Service.fromJson(Map<String, dynamic> json) {
    final activoRaw = json['activo'];

    return Service(
      id: (json['id'] as num?)?.toInt() ?? 0,
      businessId: (json['business_id'] as num?)?.toInt() ?? 0,
      nombre: (json['nombre'] ?? 'Servicio') as String,
      descripcion: json['descripcion'] as String?,
      duracionMinutos: (json['duracion_minutos'] as num?)?.toInt() ?? 0,
      precio: (json['precio'] as num?)?.toDouble() ?? 0,
      bufferPreMinutos: (json['buffer_pre_minutos'] as num?)?.toInt() ?? 0,
      bufferPostMinutos: (json['buffer_post_minutos'] as num?)?.toInt() ?? 0,
      activo: activoRaw is bool
          ? activoRaw
          : ((activoRaw as num?)?.toInt() ?? 1) == 1,
      meta: json['meta'] as Map<String, dynamic>?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'business_id': businessId,
      'nombre': nombre,
      'descripcion': descripcion,
      'duracion_minutos': duracionMinutos,
      'precio': precio,
      'buffer_pre_minutos': bufferPreMinutos,
      'buffer_post_minutos': bufferPostMinutos,
      'activo': activo,
      'meta': meta,
    };
  }

  String get precioFormateado => '\$${precio.toStringAsFixed(2)}';
  String get duracionFormateada => '$duracionMinutos min';
}
