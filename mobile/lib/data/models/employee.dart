class Employee {
  final int id;
  final String nombre;
  final String? email;
  final String? telefono;
  final String? fotoUrl;

  const Employee({
    required this.id,
    required this.nombre,
    this.email,
    this.telefono,
    this.fotoUrl,
  });

  factory Employee.fromJson(Map<String, dynamic> json) {
    return Employee(
      id: (json['id'] as num?)?.toInt() ?? 0,
      nombre: (json['nombre'] ?? json['name'] ?? 'Sin nombre') as String,
      email: json['email'] as String?,
      telefono: json['telefono'] as String?,
      fotoUrl: (json['foto_url'] ?? json['avatar_url'] ?? json['foto']) as String?,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'email': email,
      'telefono': telefono,
      'foto_url': fotoUrl,
    };
  }
}
