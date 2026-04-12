import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';

class AppointmentDetailScreen extends StatefulWidget {
  const AppointmentDetailScreen({
    super.key,
    required this.appointmentId,
  });

  final int appointmentId;

  @override
  State<AppointmentDetailScreen> createState() => _AppointmentDetailScreenState();
}

class _AppointmentDetailScreenState extends State<AppointmentDetailScreen> {
  bool _isRefreshing = false;
  bool _isExporting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadAppointmentIfNeeded();
    });
  }

  Future<void> _loadAppointmentIfNeeded({bool forceRefresh = false}) async {
    final provider = context.read<AppointmentProvider>();
    final existing = provider.findAppointmentById(widget.appointmentId);

    if (existing != null && !forceRefresh) {
      return;
    }

    setState(() {
      _isRefreshing = true;
    });

    await provider.loadMyAppointments(showLoading: false);

    if (!mounted) {
      return;
    }

    setState(() {
      _isRefreshing = false;
    });
  }

  Future<void> _cancelAppointment(Appointment appointment) async {
    final shouldCancel = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Cancelar cita'),
        content: const Text('Esta accion no se puede deshacer. ¿Deseas continuar?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(false),
            child: const Text('No'),
          ),
          TextButton(
            onPressed: () => Navigator.of(dialogContext).pop(true),
            style: TextButton.styleFrom(foregroundColor: Colors.red),
            child: const Text('Si, cancelar'),
          ),
        ],
      ),
    );

    if (shouldCancel != true || !mounted) {
      return;
    }

    final provider = context.read<AppointmentProvider>();
    final success = await provider.cancelAppointment(
      appointment.id,
      motivo: 'Cancelado desde detalle de cita',
    );

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          success
              ? 'Cita cancelada correctamente.'
              : (provider.errorMessage ?? 'No se pudo cancelar la cita.'),
        ),
        backgroundColor: success ? Colors.green : Colors.red,
      ),
    );

    if (success) {
      await _loadAppointmentIfNeeded(forceRefresh: true);
    }
  }

  Future<void> _openGoogleCalendar(Appointment appointment) async {
    final title = appointment.serviceName ?? 'Cita AgendaYa';
    final details =
        'Cita en ${appointment.businessName ?? 'AgendaYa'}${appointment.employeeName != null ? ' con ${appointment.employeeName}' : ''}';
    final location = appointment.businessName ?? 'AgendaYa';

    final uri = Uri.https(
      'calendar.google.com',
      '/calendar/render',
      {
        'action': 'TEMPLATE',
        'text': title,
        'details': details,
        'location': location,
        'dates': '${_toUtcCalendarFormat(appointment.fechaHoraInicio)}/${_toUtcCalendarFormat(appointment.fechaHoraFin)}',
      },
    );

    final launched = await launchUrl(
      uri,
      mode: kIsWeb ? LaunchMode.platformDefault : LaunchMode.externalApplication,
    );

    if (!launched && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo abrir Google Calendar.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _exportIcal(Appointment appointment) async {
    if (_isExporting) {
      return;
    }

    setState(() {
      _isExporting = true;
    });

    final summary = _escapeIcs(appointment.serviceName ?? 'Cita AgendaYa');
    final description = _escapeIcs(
      'Cita en ${appointment.businessName ?? 'AgendaYa'}${appointment.employeeName != null ? ' con ${appointment.employeeName}' : ''}',
    );
    final location = _escapeIcs(appointment.businessName ?? 'AgendaYa');

    final ics = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'PRODID:-//AgendaYa//Citas//ES',
      'CALSCALE:GREGORIAN',
      'METHOD:PUBLISH',
      'BEGIN:VEVENT',
      'UID:agendaya-${appointment.id}@agendaya.app',
      'DTSTAMP:${_toUtcCalendarFormat(DateTime.now())}',
      'DTSTART:${_toUtcCalendarFormat(appointment.fechaHoraInicio)}',
      'DTEND:${_toUtcCalendarFormat(appointment.fechaHoraFin)}',
      'SUMMARY:$summary',
      'DESCRIPTION:$description',
      'LOCATION:$location',
      'END:VEVENT',
      'END:VCALENDAR',
    ].join('\r\n');

    final uri = Uri.dataFromString(
      ics,
      mimeType: 'text/calendar',
      encoding: utf8,
    );

    final launched = await launchUrl(uri, mode: LaunchMode.platformDefault);

    if (!mounted) {
      return;
    }

    setState(() {
      _isExporting = false;
    });

    if (!launched) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo exportar el archivo iCal.'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  String _toUtcCalendarFormat(DateTime value) {
    return DateFormat("yyyyMMdd'T'HHmmss'Z'").format(value.toUtc());
  }

  String _escapeIcs(String input) {
    return input
        .replaceAll(r'\\', r'\\\\')
        .replaceAll(';', r'\;')
        .replaceAll(',', r'\,')
        .replaceAll('\n', r'\n');
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'pending':
        return Colors.orange;
      case 'confirmed':
        return Colors.blue;
      case 'completed':
        return Colors.green;
      case 'cancelled':
        return Colors.red;
      case 'no_show':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('EEEE dd MMM yyyy, HH:mm', 'es');

    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle de cita'),
      ),
      body: Consumer<AppointmentProvider>(
        builder: (context, provider, child) {
          final appointment = provider.findAppointmentById(widget.appointmentId);

          if (_isRefreshing && appointment == null) {
            return const Center(child: CircularProgressIndicator());
          }

          if (appointment == null) {
            return RefreshIndicator(
              onRefresh: () => _loadAppointmentIfNeeded(forceRefresh: true),
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 180),
                  Center(
                    child: Padding(
                      padding: EdgeInsets.symmetric(horizontal: 24),
                      child: Text(
                        'No se encontro la cita solicitada. Desliza para recargar.',
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => _loadAppointmentIfNeeded(forceRefresh: true),
            child: ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Expanded(
                              child: Text(
                                appointment.serviceName ?? 'Servicio',
                                style: Theme.of(context)
                                    .textTheme
                                    .titleLarge
                                    ?.copyWith(fontWeight: FontWeight.w700),
                              ),
                            ),
                            Chip(
                              backgroundColor: _statusColor(appointment.estado),
                              label: Text(
                                appointment.estado.toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.storefront_outlined),
                          title: const Text('Negocio'),
                          subtitle: Text(appointment.businessName ?? 'Sin nombre'),
                        ),
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.schedule),
                          title: const Text('Fecha y hora'),
                          subtitle: Text(
                            '${dateFormat.format(appointment.fechaHoraInicio)}\nFin: ${DateFormat('HH:mm').format(appointment.fechaHoraFin)}',
                          ),
                        ),
                        if (appointment.employeeName != null)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.person_outline),
                            title: const Text('Profesional'),
                            subtitle: Text(appointment.employeeName!),
                          ),
                        if (appointment.notasCliente != null &&
                            appointment.notasCliente!.trim().isNotEmpty)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: const Icon(Icons.notes_outlined),
                            title: const Text('Notas'),
                            subtitle: Text(appointment.notasCliente!),
                          ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    FilledButton.icon(
                      onPressed: () => _openGoogleCalendar(appointment),
                      icon: const Icon(Icons.event_available),
                      label: const Text('Google Calendar'),
                    ),
                    OutlinedButton.icon(
                      onPressed:
                          _isExporting ? null : () => _exportIcal(appointment),
                      icon: const Icon(Icons.download_outlined),
                      label: Text(
                        _isExporting ? 'Exportando...' : 'Exportar iCal',
                      ),
                    ),
                  ],
                ),
                if (appointment.canCancel) ...[
                  const SizedBox(height: 12),
                  ElevatedButton.icon(
                    onPressed: provider.isLoading
                        ? null
                        : () => _cancelAppointment(appointment),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      foregroundColor: Colors.white,
                    ),
                    icon: const Icon(Icons.close),
                    label: const Text('Cancelar cita'),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }
}
