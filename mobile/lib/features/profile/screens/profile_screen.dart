import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/core/utils/input_validators.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/features/auth/providers/auth_provider.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/notifications/models/notification_delivery_log.dart';
import 'package:agenda_ya/features/notifications/providers/notification_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AuthProvider>().initializeSecurityState();
      context.read<AppointmentProvider>().loadMyAppointments();
      context.read<NotificationProvider>().initialize();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _handleLogout() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar Sesión'),
        content: const Text('¿Estás seguro que deseas cerrar sesión?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(false),
            child: const Text('Cancelar'),
          ),
          TextButton(
            onPressed: () => Navigator.of(context).pop(true),
            child: const Text('Sí, cerrar sesión'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await context.read<AuthProvider>().logout();
      if (mounted) {
        Navigator.of(context).pushReplacementNamed(AppRoutes.login);
      }
    }
  }

  Future<void> _handleEditProfile() async {
    final authProvider = context.read<AuthProvider>();
    final user = authProvider.user;
    if (user == null) {
      return;
    }

    final formKey = GlobalKey<FormState>();
    final nameController = TextEditingController(text: user.name);
    final phoneController = TextEditingController(text: user.telefono ?? '');

    final shouldRefresh = await showDialog<bool>(
      context: context,
      builder: (dialogContext) {
        return AlertDialog(
          title: const Text('Editar Perfil'),
          content: Form(
            key: formKey,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextFormField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'Nombre completo',
                    prefixIcon: Icon(Icons.person),
                  ),
                  validator: (value) =>
                      InputValidators.requiredField(value, label: 'tu nombre'),
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: phoneController,
                  keyboardType: TextInputType.phone,
                  decoration: const InputDecoration(
                    labelText: 'Teléfono (+52)',
                    hintText: '+525512345678',
                    prefixIcon: Icon(Icons.phone),
                  ),
                  validator: (value) => InputValidators.mexicanPhone(value),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(dialogContext).pop(false),
              child: const Text('Cancelar'),
            ),
            FilledButton(
              onPressed: () async {
                if (!formKey.currentState!.validate()) {
                  return;
                }

                final rawPhone = phoneController.text.trim();
                final telefono = rawPhone.isEmpty
                    ? null
                    : InputValidators.normalizeMexicanPhone(rawPhone);

                final updated = await authProvider.updateProfile(
                  name: nameController.text.trim(),
                  telefono: telefono,
                );

                if (!dialogContext.mounted) {
                  return;
                }

                if (!updated) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        authProvider.errorMessage ??
                            'No se pudo actualizar tu perfil',
                      ),
                      backgroundColor: Colors.red,
                    ),
                  );
                  return;
                }

                Navigator.of(dialogContext).pop(true);
              },
              child: const Text('Guardar'),
            ),
          ],
        );
      },
    );

    nameController.dispose();
    phoneController.dispose();

    if (shouldRefresh == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Perfil actualizado correctamente'),
        ),
      );
    }
  }

  Future<void> _toggleBiometric(bool enabled) async {
    final authProvider = context.read<AuthProvider>();
    final success = await authProvider.toggleBiometric(enabled);

    if (!mounted) {
      return;
    }

    if (!success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            authProvider.errorMessage ??
                'No se pudo actualizar la configuración biométrica',
          ),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          enabled
              ? 'Acceso biométrico activado'
              : 'Acceso biométrico desactivado',
        ),
      ),
    );
  }

  Future<bool> _handleCancelAppointment(
    int appointmentId, {
    bool skipConfirmation = false,
  }) async {
    var confirmed = skipConfirmation;

    if (!skipConfirmation) {
      confirmed = await showDialog<bool>(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('Cancelar Cita'),
              content: const Text('¿Estás seguro que deseas cancelar esta cita?'),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(false),
                  child: const Text('No'),
                ),
                TextButton(
                  onPressed: () => Navigator.of(context).pop(true),
                  style: TextButton.styleFrom(foregroundColor: Colors.red),
                  child: const Text('Sí, cancelar'),
                ),
              ],
            ),
          ) ??
          false;
    }

    if (!confirmed || !mounted) {
      return false;
    }

    final success = await context.read<AppointmentProvider>().cancelAppointment(
          appointmentId,
          motivo: 'Cancelado por el usuario',
        );

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            success ? 'Cita cancelada' : 'Error al cancelar la cita',
          ),
          backgroundColor: success ? Colors.green : Colors.red,
        ),
      );
    }

    return success;
  }

  Future<void> _refreshAppointments() async {
    await context.read<AppointmentProvider>().loadMyAppointments();
    await context.read<NotificationProvider>().refreshLogs();
  }

  Future<void> _toggleWhatsAppReminders(bool enabled) async {
    await context
        .read<NotificationProvider>()
        .setWhatsAppRemindersEnabled(enabled);

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          enabled
              ? 'Recordatorios por WhatsApp activados (beta).'
              : 'Recordatorios por WhatsApp desactivados.',
        ),
      ),
    );
  }

  Future<void> _toggleBrowserNotifications(bool enabled) async {
    await context
        .read<NotificationProvider>()
        .setBrowserNotificationsEnabled(enabled);

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          enabled
              ? 'Notificaciones del navegador activadas.'
              : 'Notificaciones del navegador desactivadas.',
        ),
      ),
    );
  }

  void _openAppointmentDetail(int appointmentId) {
    Navigator.of(context).pushNamed(
      AppRoutes.appointmentDetailDeepLink(appointmentId),
      arguments: appointmentId,
    );
  }

  Widget _buildAppointmentCard(
    Appointment appointment, {
    bool allowSwipeCancel = false,
  }) {
    final dateFormat = DateFormat('dd MMM yyyy - HH:mm', 'es');

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      child: InkWell(
        borderRadius: BorderRadius.circular(12),
        onTap: () => _openAppointmentDetail(appointment.id),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      appointment.serviceName ?? 'Servicio',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  Chip(
                    label: Text(
                      appointment.estado.toUpperCase(),
                      style: const TextStyle(fontSize: 12),
                    ),
                    backgroundColor: _getStatusColor(appointment.estado),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                appointment.businessName ?? 'Negocio',
                style: const TextStyle(fontSize: 16),
              ),
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.calendar_today, size: 16),
                  const SizedBox(width: 4),
                  Text(dateFormat.format(appointment.fechaHoraInicio)),
                ],
              ),
              if (appointment.employeeName != null) ...[
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.person, size: 16),
                    const SizedBox(width: 4),
                    Expanded(child: Text('Atendido por: ${appointment.employeeName}')),
                  ],
                ),
              ],
              const SizedBox(height: 12),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  OutlinedButton.icon(
                    onPressed: () => _openAppointmentDetail(appointment.id),
                    icon: const Icon(Icons.visibility_outlined),
                    label: const Text('Detalle'),
                  ),
                  if (appointment.canCancel)
                    ElevatedButton.icon(
                      onPressed: () => _handleCancelAppointment(appointment.id),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.red,
                        foregroundColor: Colors.white,
                      ),
                      icon: const Icon(Icons.close),
                      label: Text(
                        allowSwipeCancel ? 'Cancelar' : 'Cancelar Cita',
                      ),
                    ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _getStatusColor(String status) {
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

  Color _notificationStatusColor(String status) {
    switch (status) {
      case 'enviado':
        return Colors.green;
      case 'programado':
      case 'pendiente':
        return Colors.blue;
      case 'fallido':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  Widget _buildNotificationSettingsCard() {
    return Consumer<NotificationProvider>(
      builder: (context, notificationProvider, child) {
        return Card(
          margin: const EdgeInsets.fromLTRB(16, 8, 16, 8),
          child: Column(
            children: [
              SwitchListTile.adaptive(
                title: const Text('Recordatorios WhatsApp'),
                subtitle: const Text(
                  'Cuando esté habilitado, se intenta enviar WhatsApp y si falla aplica fallback a email.',
                ),
                value: notificationProvider.whatsAppRemindersEnabled,
                onChanged: _toggleWhatsAppReminders,
              ),
              if (kIsWeb)
                SwitchListTile.adaptive(
                  title: const Text('Notificaciones del navegador'),
                  subtitle: const Text(
                    'Mostrar confirmaciones y recordatorios en el navegador web.',
                  ),
                  value: notificationProvider.browserNotificationsEnabled,
                  onChanged: _toggleBrowserNotifications,
                ),
            ],
          ),
        );
      },
    );
  }

  void _showNotificationLogsSheet(List<NotificationDeliveryLog> logs) {
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (sheetContext) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Historial de notificaciones',
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 12),
                SizedBox(
                  height: 380,
                  child: ListView.separated(
                    itemCount: logs.length,
                    separatorBuilder: (_, __) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final log = logs[index];
                      final color = _notificationStatusColor(log.status);
                      final time = DateFormat('dd/MM HH:mm', 'es')
                          .format(log.createdAt.toLocal());

                      return ListTile(
                        title: Text('${log.channel} • ${log.event}'),
                        subtitle: Text(log.message ?? '-'),
                        trailing: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            Text(
                              log.status,
                              style: TextStyle(
                                color: color,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            Text(time, style: const TextStyle(fontSize: 12)),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildNotificationLogsPreview() {
    return Consumer<NotificationProvider>(
      builder: (context, notificationProvider, child) {
        final logs = notificationProvider.logs;
        if (logs.isEmpty) {
          return const SizedBox.shrink();
        }

        final preview = logs.take(3).toList();

        return Card(
          margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'Trazabilidad de notificaciones',
                        style: TextStyle(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                    TextButton(
                      onPressed: () => _showNotificationLogsSheet(logs),
                      child: const Text('Ver todo'),
                    ),
                  ],
                ),
                ...preview.map((log) {
                  final color = _notificationStatusColor(log.status);
                  final time = DateFormat('dd/MM HH:mm', 'es')
                      .format(log.createdAt.toLocal());

                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      children: [
                        Icon(Icons.circle, size: 10, color: color),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            '${log.channel} • ${log.event} • ${log.status}',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          time,
                          style: const TextStyle(fontSize: 12),
                        ),
                      ],
                    ),
                  );
                }),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildDismissBackground() {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.symmetric(horizontal: 20),
      alignment: Alignment.centerRight,
      decoration: BoxDecoration(
        color: Colors.red.withOpacity(0.85),
        borderRadius: BorderRadius.circular(12),
      ),
      child: const Row(
        mainAxisAlignment: MainAxisAlignment.end,
        children: [
          Icon(Icons.delete_outline, color: Colors.white),
          SizedBox(width: 8),
          Text(
            'Cancelar cita',
            style: TextStyle(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAppointmentsMobileList(
    List<Appointment> appointments, {
    required bool allowSwipeCancel,
  }) {
    return ListView.builder(
      itemCount: appointments.length,
      itemBuilder: (context, index) {
        final appointment = appointments[index];

        if (!allowSwipeCancel || !appointment.canCancel) {
          return _buildAppointmentCard(
            appointment,
            allowSwipeCancel: allowSwipeCancel,
          );
        }

        return Dismissible(
          key: ValueKey('appointment-${appointment.id}'),
          direction: DismissDirection.endToStart,
          background: _buildDismissBackground(),
          confirmDismiss: (_) async {
            final success = await _handleCancelAppointment(
              appointment.id,
              skipConfirmation: false,
            );
            return success;
          },
          child: _buildAppointmentCard(
            appointment,
            allowSwipeCancel: allowSwipeCancel,
          ),
        );
      },
    );
  }

  Widget _buildAppointmentsDataTable(
    List<Appointment> appointments, {
    required bool allowCancel,
  }) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm', 'es');

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(16),
      children: [
        SingleChildScrollView(
          scrollDirection: Axis.horizontal,
          child: DataTable(
            columns: const [
              DataColumn(label: Text('Servicio')),
              DataColumn(label: Text('Negocio')),
              DataColumn(label: Text('Fecha')),
              DataColumn(label: Text('Estado')),
              DataColumn(label: Text('Acciones')),
            ],
            rows: appointments
                .map(
                  (appointment) => DataRow(
                    cells: [
                      DataCell(Text(appointment.serviceName ?? 'Servicio')),
                      DataCell(Text(appointment.businessName ?? 'Negocio')),
                      DataCell(Text(dateFormat.format(appointment.fechaHoraInicio))),
                      DataCell(
                        Chip(
                          label: Text(appointment.estado.toUpperCase()),
                          backgroundColor: _getStatusColor(appointment.estado),
                        ),
                      ),
                      DataCell(
                        Wrap(
                          spacing: 8,
                          children: [
                            TextButton(
                              onPressed: () => _openAppointmentDetail(appointment.id),
                              child: const Text('Detalle'),
                            ),
                            if (allowCancel && appointment.canCancel)
                              TextButton(
                                onPressed: () => _handleCancelAppointment(appointment.id),
                                style: TextButton.styleFrom(
                                  foregroundColor: Colors.red,
                                ),
                                child: const Text('Cancelar'),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                )
                .toList(),
          ),
        ),
      ],
    );
  }

  Widget _buildEmptyTabState(String message) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      children: [
        const SizedBox(height: 120),
        Center(child: Text(message)),
      ],
    );
  }

  Widget _buildUserHeader(User? user) {
    final isVerified = user?.isEmailVerified ?? false;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Theme.of(context).primaryColor.withOpacity(0.1),
      ),
      child: Column(
        children: [
          const CircleAvatar(
            radius: 40,
            child: Icon(Icons.person, size: 40),
          ),
          const SizedBox(height: 16),
          Text(
            user?.name ?? 'Usuario',
            style: const TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(user?.email ?? ''),
          if (user?.telefono != null) ...[
            const SizedBox(height: 4),
            Text(user!.telefono!),
          ],
          const SizedBox(height: 10),
          Chip(
            avatar: Icon(
              isVerified ? Icons.verified : Icons.warning_amber_rounded,
              size: 16,
            ),
            label: Text(
              isVerified ? 'Correo verificado' : 'Correo no verificado',
            ),
            backgroundColor: isVerified
                ? Colors.green.withOpacity(0.18)
                : Colors.orange.withOpacity(0.18),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mi Perfil'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit_outlined),
            tooltip: 'Editar perfil',
            onPressed: _handleEditProfile,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _handleLogout,
          ),
        ],
      ),
      body: Column(
        children: [
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              return _buildUserHeader(authProvider.user);
            },
          ),
          Consumer<AuthProvider>(
            builder: (context, authProvider, child) {
              return SwitchListTile.adaptive(
                title: const Text('Acceso biométrico'),
                subtitle: Text(
                  authProvider.biometricAvailable
                      ? 'Usar biometría para desbloquear sesión'
                      : 'No disponible en este dispositivo',
                ),
                value: authProvider.biometricEnabled,
                onChanged: authProvider.biometricAvailable
                    ? (value) => _toggleBiometric(value)
                    : null,
              );
            },
          ),
          _buildNotificationSettingsCard(),
          _buildNotificationLogsPreview(),
          TabBar(
            controller: _tabController,
            tabs: const [
              Tab(text: 'Próximas'),
              Tab(text: 'Pasadas'),
            ],
          ),
          Expanded(
            child: Consumer<AppointmentProvider>(
              builder: (context, provider, child) {
                if (provider.isLoading) {
                  return const Center(child: CircularProgressIndicator());
                }

                final isDesktop = MediaQuery.sizeOf(context).width >= 1024;

                Widget buildUpcomingTab() {
                  if (provider.upcomingAppointments.isEmpty) {
                    return RefreshIndicator(
                      onRefresh: _refreshAppointments,
                      child: _buildEmptyTabState('No tienes citas próximas'),
                    );
                  }

                  final list = isDesktop
                      ? _buildAppointmentsDataTable(
                          provider.upcomingAppointments,
                          allowCancel: true,
                        )
                      : _buildAppointmentsMobileList(
                          provider.upcomingAppointments,
                          allowSwipeCancel: true,
                        );

                  return RefreshIndicator(
                    onRefresh: _refreshAppointments,
                    child: list,
                  );
                }

                Widget buildPastTab() {
                  if (provider.pastAppointments.isEmpty) {
                    return RefreshIndicator(
                      onRefresh: _refreshAppointments,
                      child: _buildEmptyTabState('No tienes citas pasadas'),
                    );
                  }

                  final list = isDesktop
                      ? _buildAppointmentsDataTable(
                          provider.pastAppointments,
                          allowCancel: false,
                        )
                      : _buildAppointmentsMobileList(
                          provider.pastAppointments,
                          allowSwipeCancel: false,
                        );

                  return RefreshIndicator(
                    onRefresh: _refreshAppointments,
                    child: list,
                  );
                }

                return TabBarView(
                  controller: _tabController,
                  children: [
                    buildUpcomingTab(),
                    buildPastTab(),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
