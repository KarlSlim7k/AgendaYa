class User {
  final int id;
  final String name;
  final String email;
  final String? telefono;
  final DateTime? emailVerifiedAt;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.telefono,
    this.emailVerifiedAt,
  });

  bool get isEmailVerified => emailVerifiedAt != null;

  User copyWith({
    int? id,
    String? name,
    String? email,
    String? telefono,
    DateTime? emailVerifiedAt,
  }) {
    return User(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      telefono: telefono ?? this.telefono,
      emailVerifiedAt: emailVerifiedAt ?? this.emailVerifiedAt,
    );
  }

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      telefono: json['telefono'] as String?,
      emailVerifiedAt: json['email_verified_at'] != null
          ? DateTime.parse(json['email_verified_at'] as String)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'telefono': telefono,
      'email_verified_at': emailVerifiedAt?.toIso8601String(),
    };
  }
}
