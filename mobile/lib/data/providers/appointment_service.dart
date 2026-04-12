import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/data/models/available_slot.dart';
import 'package:agenda_ya/data/models/appointment.dart';

import 'api_client.dart';

class AppointmentService {
  AppointmentService({ApiClient? apiClient})
      : _apiClient = apiClient ?? ApiClient();

  final ApiClient _apiClient;

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

  Future<List<AvailableSlot>> getAvailableSlots({
    required int businessId,
    required int serviceId,
    required DateTime fechaInicio,
    DateTime? fechaFin,
    int? employeeId,
  }) async {
    final normalizedStart = DateTime(
      fechaInicio.year,
      fechaInicio.month,
      fechaInicio.day,
    );

    final normalizedEnd = DateTime(
      (fechaFin ?? fechaInicio).year,
      (fechaFin ?? fechaInicio).month,
      (fechaFin ?? fechaInicio).day,
    );

    final queryParams = {
      'business_id': businessId.toString(),
      'service_id': serviceId.toString(),
      'fecha_inicio': normalizedStart.toIso8601String().split('T')[0],
      'fecha_fin': normalizedEnd.toIso8601String().split('T')[0],
      if (employeeId != null) 'employee_id': employeeId.toString(),
    };

    final response = await _apiClient.get(
      ApiConstants.availabilitySlots,
      queryParams: queryParams,
    );

    final data = _apiClient.handleResponse(response);
    final timezoneHint = _extractTimezoneHint(data);

    return _extractList(data)
        .map((slot) => AvailableSlot.fromJson(slot, timezoneHint: timezoneHint))
        .toList();
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

  String? _extractTimezoneHint(Map<String, dynamic> data) {
    final directTimezone = data['timezone'] as String?;
    if (directTimezone != null && directTimezone.isNotEmpty) {
      return directTimezone;
    }

    final meta = data['meta'];
    if (meta is Map<String, dynamic>) {
      final metaTimezone =
          meta['timezone'] as String? ??
          meta['time_zone'] as String? ??
          meta['zona_horaria'] as String?;

      if (metaTimezone != null && metaTimezone.isNotEmpty) {
        return metaTimezone;
      }
    }

    final payload = data['data'];
    if (payload is Map<String, dynamic>) {
      return payload['timezone'] as String?;
    }

    return null;
  }
}
