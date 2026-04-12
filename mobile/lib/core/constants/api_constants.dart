import 'package:agenda_ya/core/config/app_environment.dart';

class ApiConstants {
  static String get baseUrl => AppEnvironmentConfig.apiBaseUrl;
  
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
