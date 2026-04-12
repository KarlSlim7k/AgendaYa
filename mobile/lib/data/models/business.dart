import 'business_location.dart';

class Business {
  final int id;
  final String nombre;
  final String? descripcion;
  final String telefono;
  final String email;
  final String categoria;
  final String estado;
  final List<BusinessLocation> locations;
  final int? totalServices;
  final int? totalEmployees;
  final DateTime? createdAt;

  Business({
    required this.id,
    required this.nombre,
    this.descripcion,
    required this.telefono,
    required this.email,
    required this.categoria,
    required this.estado,
    this.locations = const [],
    this.totalServices,
    this.totalEmployees,
    this.createdAt,
  });

  factory Business.fromJson(Map<String, dynamic> json) {
    final locationsRaw = json['locations'];

    return Business(
      id: (json['id'] as num?)?.toInt() ?? 0,
      nombre: (json['nombre'] ?? 'Negocio') as String,
      descripcion: json['descripcion'] as String?,
      telefono: (json['telefono'] ?? '') as String,
      email: (json['email'] ?? '') as String,
      categoria: (json['categoria'] ?? 'otro') as String,
      estado: (json['estado'] ?? 'pending') as String,
      locations: locationsRaw is List
        ? locationsRaw
          .whereType<Map<String, dynamic>>()
          .map(BusinessLocation.fromJson)
              .toList()
          : [],
      totalServices: (json['total_services'] as num?)?.toInt(),
      totalEmployees: (json['total_employees'] as num?)?.toInt(),
      createdAt: json['created_at'] != null
          ? DateTime.parse(json['created_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'descripcion': descripcion,
      'telefono': telefono,
      'email': email,
      'categoria': categoria,
      'estado': estado,
      'locations': locations.map((loc) => loc.toJson()).toList(),
      'total_services': totalServices,
      'total_employees': totalEmployees,
      'created_at': createdAt?.toIso8601String(),
    };
  }
}
