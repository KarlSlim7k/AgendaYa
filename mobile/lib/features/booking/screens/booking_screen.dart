import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/data/models/available_slot.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';

enum _DateRangeMode {
  day,
  week,
  month,
}

class BookingScreen extends StatefulWidget {
  final int businessId;
  final int serviceId;

  const BookingScreen({
    super.key,
    required this.businessId,
    required this.serviceId,
  });

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  DateTime _selectedDate = _normalizeDate(DateTime.now());
  DateTime? _selectedSlot;
  int? _selectedEmployeeId;
  _DateRangeMode _rangeMode = _DateRangeMode.day;
  final _notesController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadSlots();
  }

  @override
  void dispose() {
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadSlots() async {
    await _loadSlotsForCurrentRange();
  }

  Future<void> _loadSlotsForCurrentRange({bool forceRefresh = false}) async {
    await context.read<AppointmentProvider>().loadAvailableSlots(
          businessId: widget.businessId,
          serviceId: widget.serviceId,
          fechaInicio: _rangeStart,
          fechaFin: _rangeEnd,
          forceRefresh: forceRefresh,
        );
  }

  Future<void> _handleDateSelection() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 90)),
    );

    if (picked != null && picked != _selectedDate) {
      setState(() {
        _selectedDate = _normalizeDate(picked);
        _selectedSlot = null;
        _selectedEmployeeId = null;
      });
      await _loadSlots();
    }
  }

  Future<void> _moveRange(int delta) async {
    final today = _normalizeDate(DateTime.now());
    late DateTime next;

    switch (_rangeMode) {
      case _DateRangeMode.day:
        next = _selectedDate.add(Duration(days: delta));
        break;
      case _DateRangeMode.week:
        next = _selectedDate.add(Duration(days: delta * 7));
        break;
      case _DateRangeMode.month:
        next = DateTime(
          _selectedDate.year,
          _selectedDate.month + delta,
          _selectedDate.day,
        );
        break;
    }

    next = _normalizeDate(next);
    if (next.isBefore(today)) {
      return;
    }

    setState(() {
      _selectedDate = next;
      _selectedSlot = null;
      _selectedEmployeeId = null;
    });

    await _loadSlots();
  }

  Future<void> _changeRangeMode(_DateRangeMode mode) async {
    if (_rangeMode == mode) {
      return;
    }

    setState(() {
      _rangeMode = mode;
      _selectedSlot = null;
      _selectedEmployeeId = null;
    });

    await _loadSlots();
  }

  Future<void> _handleBooking() async {
    if (_selectedSlot == null || _selectedEmployeeId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Selecciona una fecha y hora'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final provider = context.read<AppointmentProvider>();
    
    final success = await provider.createAppointment(
      businessId: widget.businessId,
      serviceId: widget.serviceId,
      employeeId: _selectedEmployeeId!,
      fechaHoraInicio: _selectedSlot!,
      notasCliente: _notesController.text.trim().isNotEmpty
          ? _notesController.text.trim()
          : null,
    );

    if (success && mounted) {
      final createdAppointment = provider.lastCreatedAppointment;

      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => AlertDialog(
          title: const Text('¡Reserva Exitosa!'),
          content: Text(provider.successMessage ?? 'Tu cita ha sido confirmada'),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop(); // Close dialog
                if (createdAppointment != null) {
                  Navigator.of(context).pushNamed(
                    AppRoutes.appointmentDetailDeepLink(createdAppointment.id),
                    arguments: createdAppointment.id,
                  );
                  return;
                }

                Navigator.of(context).pushNamed(AppRoutes.profile);
              },
              child: const Text('Ver detalle de cita'),
            ),
          ],
        ),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(provider.errorMessage ?? 'Error al crear la cita'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  static DateTime _normalizeDate(DateTime date) {
    return DateTime(date.year, date.month, date.day);
  }

  DateTime get _rangeStart {
    final selected = _normalizeDate(_selectedDate);

    switch (_rangeMode) {
      case _DateRangeMode.day:
        return selected;
      case _DateRangeMode.week:
        final diff = selected.weekday - DateTime.monday;
        return selected.subtract(Duration(days: diff));
      case _DateRangeMode.month:
        return DateTime(selected.year, selected.month, 1);
    }
  }

  DateTime get _rangeEnd {
    switch (_rangeMode) {
      case _DateRangeMode.day:
        return _rangeStart;
      case _DateRangeMode.week:
        return _rangeStart.add(const Duration(days: 6));
      case _DateRangeMode.month:
        return DateTime(_rangeStart.year, _rangeStart.month + 1, 0);
    }
  }

  bool _isSameDate(DateTime a, DateTime b) {
    return a.year == b.year && a.month == b.month && a.day == b.day;
  }

  String _rangeLabel() {
    switch (_rangeMode) {
      case _DateRangeMode.day:
        return DateFormat('EEEE, dd MMMM yyyy', 'es').format(_selectedDate);
      case _DateRangeMode.week:
        return '${DateFormat('dd MMM', 'es').format(_rangeStart)} - ${DateFormat('dd MMM yyyy', 'es').format(_rangeEnd)}';
      case _DateRangeMode.month:
        return DateFormat('MMMM yyyy', 'es').format(_rangeStart);
    }
  }

  List<DateTime> _availableDays(List<AvailableSlot> slots) {
    final unique = <String, DateTime>{};

    for (final slot in slots) {
      final day = _normalizeDate(slot.startAtLocal);
      unique['${day.year}-${day.month}-${day.day}'] = day;
    }

    final result = unique.values.toList()
      ..sort((a, b) => a.compareTo(b));

    return result;
  }

  List<AvailableSlot> _selectedDaySlots(List<AvailableSlot> slots) {
    final filtered = slots
        .where((slot) => slot.isAvailable)
        .where((slot) => _isSameDate(slot.startAtLocal, _selectedDate))
        .toList();

    filtered.sort((a, b) => a.startAtLocal.compareTo(b.startAtLocal));
    return filtered;
  }

  bool _isSlotSelected(AvailableSlot slot) {
    if (_selectedSlot == null || _selectedEmployeeId == null) {
      return false;
    }

    return slot.startAtLocal.compareTo(_selectedSlot!) == 0 &&
        slot.employeeId == _selectedEmployeeId;
  }

  void _handleHorizontalSwipe(DragEndDetails details) {
    final velocity = details.primaryVelocity ?? 0;
    if (velocity.abs() < 250) {
      return;
    }

    if (velocity < 0) {
      _moveRange(1);
      return;
    }

    _moveRange(-1);
  }

  String _rangeModeLabel(_DateRangeMode mode) {
    switch (mode) {
      case _DateRangeMode.day:
        return 'Día';
      case _DateRangeMode.week:
        return 'Semana';
      case _DateRangeMode.month:
        return 'Mes';
    }
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final isDesktopLike = width >= 900;
    final canGoBack = _rangeStart.isAfter(_normalizeDate(DateTime.now()));
    final dayGridColumns = width >= 1100
        ? 5
        : width >= 860
            ? 4
            : 3;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Reservar Cita'),
      ),
      body: Consumer<AppointmentProvider>(
        builder: (context, provider, child) {
          final availableDays = _availableDays(provider.availableSlots);
          final slotsForSelectedDay = _selectedDaySlots(provider.availableSlots);

          if (availableDays.isNotEmpty &&
              !availableDays.any((day) => _isSameDate(day, _selectedDate))) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (!mounted) {
                return;
              }

              setState(() {
                _selectedDate = availableDays.first;
                _selectedSlot = null;
                _selectedEmployeeId = null;
              });
            });
          }

          return GestureDetector(
            onHorizontalDragEnd: _handleHorizontalSwipe,
            child: RefreshIndicator(
              onRefresh: () => _loadSlotsForCurrentRange(forceRefresh: true),
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(16),
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 4,
                      ),
                      child: Row(
                        children: [
                          IconButton(
                            onPressed: canGoBack ? () => _moveRange(-1) : null,
                            icon: const Icon(Icons.chevron_left),
                          ),
                          Expanded(
                            child: Text(
                              _rangeLabel(),
                              textAlign: TextAlign.center,
                              style: Theme.of(context)
                                  .textTheme
                                  .titleMedium
                                  ?.copyWith(fontWeight: FontWeight.w700),
                            ),
                          ),
                          IconButton(
                            onPressed: () => _moveRange(1),
                            icon: const Icon(Icons.chevron_right),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),

                  if (isDesktopLike)
                    Wrap(
                      spacing: 8,
                      children: _DateRangeMode.values
                          .map(
                            (mode) => ChoiceChip(
                              label: Text(_rangeModeLabel(mode)),
                              selected: _rangeMode == mode,
                              onSelected: (_) => _changeRangeMode(mode),
                            ),
                          )
                          .toList(),
                    ),
                  if (isDesktopLike) const SizedBox(height: 12),

                  Card(
                    child: ListTile(
                      leading: const Icon(Icons.calendar_today),
                      title: const Text('Fecha seleccionada'),
                      subtitle: Text(
                        DateFormat('EEEE, dd MMMM yyyy', 'es').format(_selectedDate),
                      ),
                      trailing: const Icon(Icons.edit_calendar),
                      onTap: _handleDateSelection,
                    ),
                  ),
                  const SizedBox(height: 12),

                  if (availableDays.isNotEmpty)
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: availableDays
                          .map(
                            (day) => ChoiceChip(
                              label: Text(
                                DateFormat('EEE dd', 'es').format(day),
                              ),
                              selected: _isSameDate(day, _selectedDate),
                              onSelected: (_) {
                                setState(() {
                                  _selectedDate = day;
                                  _selectedSlot = null;
                                  _selectedEmployeeId = null;
                                });
                              },
                            ),
                          )
                          .toList(),
                    ),

                  const SizedBox(height: 16),

                  if (provider.slotsFromCache || provider.slotsSourceTimezone != null)
                    Card(
                      child: ListTile(
                        dense: true,
                        leading: Icon(
                          provider.slotsFromCache
                              ? Icons.cloud_off
                              : Icons.schedule,
                        ),
                        title: Text(
                          provider.slotsFromCache
                              ? 'Mostrando horarios en cache (se sincronizan al actualizar)'
                              : 'Horarios sincronizados en tiempo real',
                        ),
                        subtitle: provider.slotsSourceTimezone == null
                            ? null
                            : Text('Zona fuente: ${provider.slotsSourceTimezone}'),
                      ),
                    ),

                  const SizedBox(height: 8),

                  const Text(
                    'Horarios Disponibles',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),

                  if (provider.isLoadingSlots && slotsForSelectedDay.isEmpty)
                    const Center(
                      child: Padding(
                        padding: EdgeInsets.all(32),
                        child: CircularProgressIndicator(),
                      ),
                    )
                  else if (slotsForSelectedDay.isEmpty)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 24),
                      child: Text(
                        'No hay horarios disponibles para ${DateFormat('dd/MM/yyyy').format(_selectedDate)}',
                        textAlign: TextAlign.center,
                      ),
                    )
                  else
                    GridView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: dayGridColumns,
                        crossAxisSpacing: 8,
                        mainAxisSpacing: 8,
                        childAspectRatio: 1.9,
                      ),
                      itemCount: slotsForSelectedDay.length,
                      itemBuilder: (context, index) {
                        final slot = slotsForSelectedDay[index];
                        final isSelected = _isSlotSelected(slot);
                        final disabled = slot.employeeId == null;

                        return InkWell(
                          onTap: disabled
                              ? null
                              : () {
                                  setState(() {
                                    _selectedSlot = slot.startAtLocal;
                                    _selectedEmployeeId = slot.employeeId;
                                  });
                                },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8),
                            decoration: BoxDecoration(
                              color: isSelected
                                  ? Theme.of(context).primaryColor
                                  : disabled
                                      ? Colors.grey.shade300
                                      : Colors.grey.shade100,
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Text(
                                    DateFormat('HH:mm').format(slot.startAtLocal),
                                    style: TextStyle(
                                      color: isSelected ? Colors.white : Colors.black87,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                                  if (slot.employeeName != null)
                                    Text(
                                      slot.employeeName!,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: TextStyle(
                                        color: isSelected
                                            ? Colors.white70
                                            : Colors.black54,
                                        fontSize: 11,
                                      ),
                                    ),
                                ],
                              ),
                            ),
                          ),
                        );
                      },
                    ),

                  const SizedBox(height: 24),

                  TextField(
                    controller: _notesController,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'Notas (opcional)',
                      hintText: 'Algún comentario o solicitud especial...',
                      border: OutlineInputBorder(),
                    ),
                  ),

                  const SizedBox(height: 24),

                  ElevatedButton(
                    onPressed: provider.isLoading ? null : _handleBooking,
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: provider.isLoading
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Text(
                            'Confirmar Reserva',
                            style: TextStyle(fontSize: 16),
                          ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
