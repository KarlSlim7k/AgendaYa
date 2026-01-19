class ApiConstants {
  // Base URL - Cambiar según entorno
  static const String baseUrl = 'http://127.0.0.1:8000/api/v1';
  
  // Auth endpoints
  static const String register = '/auth/register';
  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String profile = '/user/profile';
  
  // Business endpoints
  static const String businesses = '/businesses';
  static String businessDetail(int id) => '/businesses/$id';
  static String businessServices(int id) => '/businesses/$id/services';
  
  // Appointment endpoints
  static const String appointments = '/appointments';
  static String appointmentCancel(int id) => '/appointments/$id/cancel';
  
  // Availability endpoint
  static const String availabilitySlots = '/availability/slots';
  
  // Headers
  static const String contentTypeJson = 'application/json';
  static const String acceptJson = 'application/json';
}
