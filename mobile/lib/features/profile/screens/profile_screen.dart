import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/core/utils/input_validators.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/features/auth/providers/auth_provider.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';

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

  Future<void> _handleCancelAppointment(int appointmentId) async {
    final confirmed = await showDialog<bool>(
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
    );

    if (confirmed == true && mounted) {
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
    }
  }

  Widget _buildAppointmentCard(Appointment appointment) {
    final dateFormat = DateFormat('dd MMM yyyy - HH:mm', 'es');

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
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
                  Text('Atendido por: ${appointment.employeeName}'),
                ],
              ),
            ],
            if (appointment.canCancel) ...[
              const SizedBox(height: 12),
              ElevatedButton(
                onPressed: () => _handleCancelAppointment(appointment.id),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.red,
                  foregroundColor: Colors.white,
                ),
                child: const Text('Cancelar Cita'),
              ),
            ],
          ],
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

                return TabBarView(
                  controller: _tabController,
                  children: [
                    provider.upcomingAppointments.isEmpty
                        ? const Center(
                            child: Text('No tienes citas próximas'),
                          )
                        : ListView.builder(
                            itemCount: provider.upcomingAppointments.length,
                            itemBuilder: (context, index) {
                              return _buildAppointmentCard(
                                provider.upcomingAppointments[index],
                              );
                            },
                          ),
                    provider.pastAppointments.isEmpty
                        ? const Center(
                            child: Text('No tienes citas pasadas'),
                          )
                        : ListView.builder(
                            itemCount: provider.pastAppointments.length,
                            itemBuilder: (context, index) {
                              return _buildAppointmentCard(
                                provider.pastAppointments[index],
                              );
                            },
                          ),
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
