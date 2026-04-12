import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/data/models/appointment.dart';

import 'api_client.dart';

class AppointmentService {
  final ApiClient _apiClient = ApiClient();

  Future<Appointment> createAppointment({
    required int businessId,
    required int serviceId,
    required int employeeId,
    required DateTime fechaHoraInicio,
    String? notasCliente,
    Map<String, dynamic>? customData,
  }) async {
    final response = await _apiClient.post(
      ApiConstants.appointments,
      body: {
        'business_id': businessId,
        'service_id': serviceId,
        'employee_id': employeeId,
        'fecha_hora_inicio': fechaHoraInicio.toIso8601String(),
        if (notasCliente != null) 'notas_cliente': notasCliente,
        if (customData != null) 'custom_data': customData,
      },
    );

    final data = _apiClient.handleResponse(response);
    final payload = _extractPayload(data);
    return Appointment.fromJson(payload);
  }

  Future<List<Appointment>> getMyAppointments({
    String? estado,
    bool? futuras,
    bool? pasadas,
  }) async {
    final queryParams = <String, String>{};
    
    if (estado != null) queryParams['estado'] = estado;
    if (futuras != null) queryParams['futuras'] = futuras.toString();
    if (pasadas != null) queryParams['pasadas'] = pasadas.toString();

    final response = await _apiClient.get(
      ApiConstants.appointments,
      queryParams: queryParams.isNotEmpty ? queryParams : null,
    );

    final data = _apiClient.handleResponse(response);

    return _extractList(data)
        .map((json) => Appointment.fromJson(json))
        .toList();
  }

  Future<Appointment> cancelAppointment(
    int appointmentId, {
    String? motivoCancelacion,
  }) async {
    final response = await _apiClient.patch(
      ApiConstants.appointmentCancel(appointmentId),
      body: {
        if (motivoCancelacion != null) 'motivo_cancelacion': motivoCancelacion,
      },
    );

    final data = _apiClient.handleResponse(response);
    final payload = _extractPayload(data);
    return Appointment.fromJson(payload);
  }

  Future<List<Map<String, dynamic>>> getAvailableSlots({
    required int businessId,
    required int serviceId,
    required DateTime fecha,
    int? employeeId,
  }) async {
    final queryParams = {
      'business_id': businessId.toString(),
      'service_id': serviceId.toString(),
      'fecha_inicio': fecha.toIso8601String().split('T')[0],
      'fecha_fin': fecha.toIso8601String().split('T')[0],
      if (employeeId != null) 'employee_id': employeeId.toString(),
    };

    final response = await _apiClient.get(
      ApiConstants.availabilitySlots,
      queryParams: queryParams,
    );

    final data = _apiClient.handleResponse(response);
    return _extractList(data);
  }

  List<Map<String, dynamic>> _extractList(Map<String, dynamic> data) {
    if (data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    final payload = data['data'];
    if (payload is Map<String, dynamic> && payload['data'] is List) {
      return (payload['data'] as List)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    return <Map<String, dynamic>>[];
  }

  Map<String, dynamic> _extractPayload(Map<String, dynamic> data) {
    final payload = data['data'];
    if (payload is Map<String, dynamic>) {
      if (payload['data'] is Map<String, dynamic>) {
        return payload['data'] as Map<String, dynamic>;
      }
      return payload;
    }

    return data;
  }
}
