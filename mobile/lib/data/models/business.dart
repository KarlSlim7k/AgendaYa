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
    return Business(
      id: json['id'] as int,
      nombre: json['nombre'] as String,
      descripcion: json['descripcion'] as String?,
      telefono: json['telefono'] as String,
      email: json['email'] as String,
      categoria: json['categoria'] as String,
      estado: json['estado'] as String,
      locations: json['locations'] != null
          ? (json['locations'] as List)
              .map((loc) => BusinessLocation.fromJson(loc))
              .toList()
          : [],
      totalServices: json['total_services'] as int?,
      totalEmployees: json['total_employees'] as int?,
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
