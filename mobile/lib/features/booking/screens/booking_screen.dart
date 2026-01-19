import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../providers/appointment_provider.dart';
import '../../core/routes/app_routes.dart';

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
  DateTime _selectedDate = DateTime.now();
  String? _selectedSlot;
  int? _selectedEmployeeId;
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
    await context.read<AppointmentProvider>().loadAvailableSlots(
          businessId: widget.businessId,
          serviceId: widget.serviceId,
          date: _selectedDate,
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
        _selectedDate = picked;
        _selectedSlot = null;
        _selectedEmployeeId = null;
      });
      await _loadSlots();
    }
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
                Navigator.of(context).pushNamedAndRemoveUntil(
                  AppRoutes.home,
                  (route) => false,
                );
              },
              child: const Text('Ver mis citas'),
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Reservar Cita'),
      ),
      body: Consumer<AppointmentProvider>(
        builder: (context, provider, child) {
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Selector de fecha
                Card(
                  child: ListTile(
                    leading: const Icon(Icons.calendar_today),
                    title: const Text('Fecha'),
                    subtitle: Text(
                      DateFormat('EEEE, dd MMMM yyyy', 'es').format(_selectedDate),
                    ),
                    trailing: const Icon(Icons.arrow_forward_ios),
                    onTap: _handleDateSelection,
                  ),
                ),
                const SizedBox(height: 24),

                // Horarios disponibles
                const Text(
                  'Horarios Disponibles',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 16),

                if (provider.isLoadingSlots)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(32.0),
                      child: CircularProgressIndicator(),
                    ),
                  )
                else if (provider.availableSlots.isEmpty)
                  const Center(
                    child: Padding(
                      padding: EdgeInsets.all(32.0),
                      child: Text('No hay horarios disponibles para esta fecha'),
                    ),
                  )
                else
                  GridView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 3,
                      crossAxisSpacing: 8,
                      mainAxisSpacing: 8,
                      childAspectRatio: 2,
                    ),
                    itemCount: provider.availableSlots.length,
                    itemBuilder: (context, index) {
                      final slot = provider.availableSlots[index];
                      final slotTime = slot['slot'] as String;
                      final employeeId = slot['employee_id'] as int;
                      final isSelected = _selectedSlot == slotTime;

                      return InkWell(
                        onTap: () {
                          setState(() {
                            _selectedSlot = slotTime;
                            _selectedEmployeeId = employeeId;
                          });
                        },
                        child: Container(
                          decoration: BoxDecoration(
                            color: isSelected
                                ? Theme.of(context).primaryColor
                                : Colors.grey[200],
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Center(
                            child: Text(
                              slotTime.substring(11, 16), // HH:mm
                              style: TextStyle(
                                color: isSelected ? Colors.white : Colors.black,
                                fontWeight: isSelected
                                    ? FontWeight.bold
                                    : FontWeight.normal,
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),

                const SizedBox(height: 24),

                // Notas adicionales
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

                // Botón confirmar
                ElevatedButton(
                  onPressed: provider.isLoading ? null : _handleBooking,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  child: provider.isLoading
                      ? const CircularProgressIndicator()
                      : const Text(
                          'Confirmar Reserva',
                          style: TextStyle(fontSize: 16),
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
