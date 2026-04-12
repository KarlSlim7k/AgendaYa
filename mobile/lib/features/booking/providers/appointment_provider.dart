import 'package:flutter/material.dart';

import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/providers/appointment_service.dart';

class AppointmentProvider with ChangeNotifier {
  final AppointmentService _appointmentService = AppointmentService();
  
  List<Appointment> _appointments = [];
  List<Map<String, dynamic>> _availableSlots = [];
  bool _isLoading = false;
  bool _isLoadingSlots = false;
  String? _errorMessage;
  String? _successMessage;

  List<Appointment> get appointments => _appointments;
  List<Map<String, dynamic>> get availableSlots => _availableSlots;
  bool get isLoading => _isLoading;
  bool get isLoadingSlots => _isLoadingSlots;
  String? get errorMessage => _errorMessage;
  String? get successMessage => _successMessage;

  List<Appointment> get upcomingAppointments => _appointments
      .where((apt) => apt.fechaHoraInicio.isAfter(DateTime.now()))
      .toList()
    ..sort((a, b) => a.fechaHoraInicio.compareTo(b.fechaHoraInicio));

  List<Appointment> get pastAppointments => _appointments
      .where((apt) => apt.fechaHoraInicio.isBefore(DateTime.now()))
      .toList()
    ..sort((a, b) => b.fechaHoraInicio.compareTo(a.fechaHoraInicio));

  Future<void> loadMyAppointments({
    String? estado,
    bool? futuras,
    bool? pasadas,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _appointments = await _appointmentService.getMyAppointments(
        estado: estado,
        futuras: futuras,
        pasadas: pasadas,
      );
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> createAppointment({
    required int businessId,
    required int serviceId,
    required int employeeId,
    required DateTime fechaHoraInicio,
    String? notasCliente,
    Map<String, dynamic>? customData,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();

    try {
      final appointment = await _appointmentService.createAppointment(
        businessId: businessId,
        serviceId: serviceId,
        employeeId: employeeId,
        fechaHoraInicio: fechaHoraInicio,
        notasCliente: notasCliente,
        customData: customData,
      );

      _appointments.insert(0, appointment);
      _successMessage = 'Cita creada exitosamente';
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> cancelAppointment(int appointmentId, {String? motivo}) async {
    _isLoading = true;
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();

    try {
      final updatedAppointment = await _appointmentService.cancelAppointment(
        appointmentId,
        motivoCancelacion: motivo,
      );

      final index = _appointments.indexWhere((apt) => apt.id == appointmentId);
      if (index != -1) {
        _appointments[index] = updatedAppointment;
      }

      _successMessage = 'Cita cancelada exitosamente';
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> loadAvailableSlots({
    required int businessId,
    required int serviceId,
    required DateTime fecha,
    int? employeeId,
  }) async {
    _isLoadingSlots = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _availableSlots = await _appointmentService.getAvailableSlots(
        businessId: businessId,
        serviceId: serviceId,
        fecha: fecha,
        employeeId: employeeId,
      );
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoadingSlots = false;
      notifyListeners();
    }
  }

  void clearMessages() {
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();
  }
}
