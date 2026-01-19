import 'package:flutter_test/flutter_test.dart';
import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/business_location.dart';
import 'package:agenda_ya/data/models/service.dart';
import 'package:agenda_ya/data/models/appointment.dart';

/// Helper para crear usuarios de prueba
User createTestUser({
  int id = 1,
  String name = 'Test User',
  String email = 'test@example.com',
  String? telefono = '+52 55 1234 5678',
}) {
  return User(
    id: id,
    name: name,
    email: email,
    telefono: telefono,
    emailVerifiedAt: DateTime.now(),
  );
}

/// Helper para crear negocios de prueba
Business createTestBusiness({
  int id = 1,
  String nombre = 'Test Business',
  String categoria = 'peluqueria',
  String? descripcion = 'Descripción de prueba',
  String? telefono = '+52 55 8765 4321',
  String? email = 'business@example.com',
  int? totalServices = 5,
  int? totalEmployees = 3,
  List<BusinessLocation>? locations,
}) {
  return Business(
    id: id,
    nombre: nombre,
    categoria: categoria,
    descripcion: descripcion,
    telefono: telefono,
    email: email,
    totalServices: totalServices,
    totalEmployees: totalEmployees,
    locations: locations,
  );
}

/// Helper para crear ubicaciones de prueba
BusinessLocation createTestLocation({
  int id = 1,
  String nombre = 'Sucursal Centro',
  String direccion = 'Calle Principal 123',
  String ciudad = 'Ciudad de México',
  String estado = 'CDMX',
  String codigoPostal = '01000',
}) {
  return BusinessLocation(
    id: id,
    nombre: nombre,
    direccion: direccion,
    ciudad: ciudad,
    estado: estado,
    codigoPostal: codigoPostal,
  );
}

/// Helper para crear servicios de prueba
Service createTestService({
  int id = 1,
  String nombre = 'Corte de cabello',
  String? descripcion = 'Corte estilo moderno',
  int duracionMinutos = 30,
  double precio = 150.0,
  bool activo = true,
}) {
  return Service(
    id: id,
    nombre: nombre,
    descripcion: descripcion,
    duracionMinutos: duracionMinutos,
    precio: precio,
    activo: activo,
  );
}

/// Helper para crear citas de prueba
Appointment createTestAppointment({
  int id = 1,
  int userId = 1,
  int businessId = 1,
  int serviceId = 1,
  int employeeId = 1,
  DateTime? fechaHoraInicio,
  DateTime? fechaHoraFin,
  String estado = 'confirmed',
  String? businessName = 'Test Business',
  String? serviceName = 'Test Service',
  String? employeeName = 'Test Employee',
}) {
  final inicio = fechaHoraInicio ?? DateTime.now().add(const Duration(days: 1));
  final fin = fechaHoraFin ?? inicio.add(const Duration(minutes: 30));

  return Appointment(
    id: id,
    userId: userId,
    businessId: businessId,
    serviceId: serviceId,
    employeeId: employeeId,
    fechaHoraInicio: inicio,
    fechaHoraFin: fin,
    estado: estado,
    businessName: businessName,
    serviceName: serviceName,
    employeeName: employeeName,
  );
}

/// Helper para validar respuestas de API
void expectSuccessResponse(Map<String, dynamic> response) {
  expect(response, isA<Map<String, dynamic>>());
  expect(response.containsKey('data'), isTrue);
}

/// Helper para validar respuestas con errores
void expectErrorResponse(
  Map<String, dynamic> response, {
  String? message,
  int? statusCode,
}) {
  expect(response, isA<Map<String, dynamic>>());
  if (message != null) {
    expect(response['message'], contains(message));
  }
  if (statusCode != null) {
    expect(response['statusCode'], equals(statusCode));
  }
}

/// Helper para esperar navegación
Future<void> waitForNavigation(WidgetTester tester, {int milliseconds = 500}) async {
  await tester.pumpAndSettle(Duration(milliseconds: milliseconds));
}

/// Helper para encontrar widgets por texto
Finder findTextWidget(String text) {
  return find.text(text);
}

/// Helper para encontrar botones
Finder findButton(String text) {
  return find.widgetWithText(ElevatedButton, text);
}

/// Helper para simular tap y esperar
Future<void> tapAndWait(WidgetTester tester, Finder finder) async {
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

/// Helper para ingresar texto en campo
Future<void> enterText(WidgetTester tester, Finder finder, String text) async {
  await tester.enterText(finder, text);
  await tester.pumpAndSettle();
}
