import 'package:flutter/material.dart';

import 'package:agenda_ya/data/models/available_slot.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/providers/appointment_service.dart';
import 'package:agenda_ya/features/booking/services/slot_cache_service.dart';

class AppointmentProvider with ChangeNotifier {
  final AppointmentService _appointmentService = AppointmentService();
  final SlotCacheService _slotCacheService = SlotCacheService();
  
  List<Appointment> _appointments = [];
  List<AvailableSlot> _availableSlots = [];
  bool _isLoading = false;
  bool _isLoadingSlots = false;
  bool _slotsFromCache = false;
  String? _slotsSourceTimezone;
  String? _errorMessage;
  String? _successMessage;

  List<Appointment> get appointments => _appointments;
  List<AvailableSlot> get availableSlots => _availableSlots;
  bool get isLoading => _isLoading;
  bool get isLoadingSlots => _isLoadingSlots;
  bool get slotsFromCache => _slotsFromCache;
  String? get slotsSourceTimezone => _slotsSourceTimezone;
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

      await _slotCacheService.invalidateForBooking(
        businessId: businessId,
        serviceId: serviceId,
        appointmentDate: fechaHoraInicio,
      );
      _removeBookedSlot(
        appointmentStartAt: fechaHoraInicio,
        employeeId: employeeId,
      );

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
    required DateTime fechaInicio,
    DateTime? fechaFin,
    int? employeeId,
    bool forceRefresh = false,
  }) async {
    final rangeStart = DateTime(
      fechaInicio.year,
      fechaInicio.month,
      fechaInicio.day,
    );
    final rangeEnd = DateTime(
      (fechaFin ?? fechaInicio).year,
      (fechaFin ?? fechaInicio).month,
      (fechaFin ?? fechaInicio).day,
    );

    _isLoadingSlots = true;
    _errorMessage = null;
    notifyListeners();

    try {
      if (!forceRefresh) {
        final cached = await _slotCacheService.readSlots(
          businessId: businessId,
          serviceId: serviceId,
          rangeStart: rangeStart,
          rangeEnd: rangeEnd,
          employeeId: employeeId,
        );

        if (cached != null) {
          _availableSlots = cached.slots;
          _slotsSourceTimezone = cached.sourceTimezone;
          _slotsFromCache = true;
          _isLoadingSlots = false;
          notifyListeners();
        }
      }

      _availableSlots = await _appointmentService.getAvailableSlots(
        businessId: businessId,
        serviceId: serviceId,
        fechaInicio: rangeStart,
        fechaFin: rangeEnd,
        employeeId: employeeId,
      );

      _slotsSourceTimezone =
          _availableSlots.isNotEmpty ? _availableSlots.first.sourceTimezone : null;
      _slotsFromCache = false;

      await _slotCacheService.writeSlots(
        businessId: businessId,
        serviceId: serviceId,
        rangeStart: rangeStart,
        rangeEnd: rangeEnd,
        employeeId: employeeId,
        sourceTimezone: _slotsSourceTimezone,
        slots: _availableSlots,
      );
    } catch (e) {
      if (_availableSlots.isEmpty) {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      }
    } finally {
      _isLoadingSlots = false;
      notifyListeners();
    }
  }

  void _removeBookedSlot({
    required DateTime appointmentStartAt,
    required int employeeId,
  }) {
    if (_availableSlots.isEmpty) {
      return;
    }

    final bookedAtLocal =
        appointmentStartAt.isUtc ? appointmentStartAt.toLocal() : appointmentStartAt;

    _availableSlots = _availableSlots.where((slot) {
      final sameEmployee = slot.employeeId == employeeId;
      final sameStart = slot.startAtLocal.compareTo(bookedAtLocal) == 0;
      return !(sameEmployee && sameStart);
    }).toList();
  }

  void clearMessages() {
    _errorMessage = null;
    _successMessage = null;
    notifyListeners();
  }
}
