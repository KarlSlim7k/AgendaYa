import 'test_helpers.dart';

/// Mock API Client para tests unitarios
class MockApiClient {
  bool shouldFail = false;
  String? errorMessage;
  
  // Simular login
  Future<Map<String, dynamic>> login(String email, String password) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Login failed');
    }
    
    return {
      'data': {
        'user': createTestUser(email: email).toJson(),
        'token': 'mock_token_12345',
      }
    };
  }
  
  // Simular registro
  Future<Map<String, dynamic>> register(Map<String, dynamic> data) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Registration failed');
    }
    
    return {
      'data': {
        'user': createTestUser(
          name: data['name'],
          email: data['email'],
        ).toJson(),
        'token': 'mock_token_12345',
      }
    };
  }
  
  // Simular búsqueda de negocios
  Future<Map<String, dynamic>> searchBusinesses({
    String? search,
    String? category,
    int page = 1,
  }) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Search failed');
    }
    
    final businesses = List.generate(
      10,
      (index) => createTestBusiness(
        id: index + 1,
        nombre: 'Business ${index + 1}',
        categoria: category ?? 'peluqueria',
      ),
    );
    
    return {
      'data': businesses.map((b) => b.toJson()).toList(),
      'meta': {
        'current_page': page,
        'per_page': 15,
        'total': 50,
        'last_page': 4,
      }
    };
  }
  
  // Simular detalle de negocio
  Future<Map<String, dynamic>> getBusinessDetail(int businessId) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Business not found');
    }
    
    final business = createTestBusiness(
      id: businessId,
      locations: [
        createTestLocation(id: 1),
        createTestLocation(id: 2, nombre: 'Sucursal Norte'),
      ],
    );
    
    return {
      'data': business.toJson(),
    };
  }
  
  // Simular servicios de negocio
  Future<Map<String, dynamic>> getBusinessServices(int businessId) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Services not found');
    }
    
    final services = List.generate(
      5,
      (index) => createTestService(
        id: index + 1,
        nombre: 'Service ${index + 1}',
        precio: 100.0 + (index * 50),
        businessId: businessId,
      ),
    );
    
    return {
      'data': services.map((s) => s.toJson()).toList(),
    };
  }
  
  // Simular slots disponibles
  Future<Map<String, dynamic>> getAvailableSlots({
    required int businessId,
    required int serviceId,
    required String date,
    int? employeeId,
  }) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'No slots available');
    }
    
    final slots = List.generate(
      10,
      (index) => {
        'slot': '$date ${9 + index}:00:00',
        'employee_id': employeeId ?? 1,
        'employee_name': 'Employee ${employeeId ?? 1}',
      },
    );
    
    return {
      'data': slots,
    };
  }
  
  // Simular crear cita
  Future<Map<String, dynamic>> createAppointment(Map<String, dynamic> data) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Appointment creation failed');
    }
    
    final appointment = createTestAppointment(
      businessId: data['business_id'],
      serviceId: data['service_id'],
      employeeId: data['employee_id'],
    );
    
    return {
      'data': appointment.toJson(),
      'message': 'Cita creada exitosamente',
    };
  }
  
  // Simular listar mis citas
  Future<Map<String, dynamic>> getMyAppointments() async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Failed to load appointments');
    }
    
    final appointments = [
      createTestAppointment(
        id: 1,
        estado: 'confirmed',
        fechaHoraInicio: DateTime.now().add(const Duration(days: 1)),
      ),
      createTestAppointment(
        id: 2,
        estado: 'completed',
        fechaHoraInicio: DateTime.now().subtract(const Duration(days: 1)),
      ),
    ];
    
    return {
      'data': appointments.map((a) => a.toJson()).toList(),
    };
  }
  
  // Simular cancelar cita
  Future<Map<String, dynamic>> cancelAppointment(
    int appointmentId,
    String? reason,
  ) async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Cancellation failed');
    }
    
    return {
      'message': 'Cita cancelada exitosamente',
    };
  }
  
  // Simular perfil de usuario
  Future<Map<String, dynamic>> getProfile() async {
    await Future.delayed(const Duration(milliseconds: 100));
    
    if (shouldFail) {
      throw Exception(errorMessage ?? 'Failed to load profile');
    }
    
    return {
      'data': createTestUser().toJson(),
    };
  }
}
