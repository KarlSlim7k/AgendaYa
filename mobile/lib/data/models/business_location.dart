class BusinessLocation {
  final int id;
  final String nombre;
  final String direccion;
  final String ciudad;
  final String estado;
  final String codigoPostal;
  final String? telefono;
  final double? latitud;
  final double? longitud;
  final bool activo;

  BusinessLocation({
    required this.id,
    required this.nombre,
    required this.direccion,
    required this.ciudad,
    required this.estado,
    required this.codigoPostal,
    this.telefono,
    this.latitud,
    this.longitud,
    required this.activo,
  });

  factory BusinessLocation.fromJson(Map<String, dynamic> json) {
    return BusinessLocation(
      id: json['id'] as int,
      nombre: json['nombre'] as String,
      direccion: json['direccion'] as String,
      ciudad: json['ciudad'] as String,
      estado: json['estado'] as String,
      codigoPostal: json['codigo_postal'] as String,
      telefono: json['telefono'] as String?,
      latitud: json['latitud'] != null ? (json['latitud'] as num).toDouble() : null,
      longitud: json['longitud'] != null ? (json['longitud'] as num).toDouble() : null,
      activo: json['activo'] as bool,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'direccion': direccion,
      'ciudad': ciudad,
      'estado': estado,
      'codigo_postal': codigoPostal,
      'telefono': telefono,
      'latitud': latitud,
      'longitud': longitud,
      'activo': activo,
    };
  }

  String get direccionCompleta => '$direccion, $ciudad, $estado $codigoPostal';
}
