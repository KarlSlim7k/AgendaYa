import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/business_location.dart';
import 'package:agenda_ya/data/models/employee.dart';
import 'package:agenda_ya/data/models/service.dart';
import 'package:agenda_ya/features/business/providers/business_provider.dart';
import 'package:agenda_ya/shared/widgets/app_state_view.dart';

class BusinessDetailScreen extends StatefulWidget {
  const BusinessDetailScreen({
    super.key,
    required this.businessId,
  });

  final int businessId;

  @override
  State<BusinessDetailScreen> createState() => _BusinessDetailScreenState();
}

class _BusinessDetailScreenState extends State<BusinessDetailScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BusinessProvider>().loadBusinessDetail(widget.businessId);
    });
  }

  Future<void> _openMap(BusinessLocation location) async {
    final hasCoordinates = location.latitud != null && location.longitud != null;

    final query = hasCoordinates
        ? '${location.latitud},${location.longitud}'
        : Uri.encodeComponent(location.direccionCompleta);

    final uri = Uri.parse(
      'https://www.google.com/maps/search/?api=1&query=$query',
    );

    final launched = await launchUrl(
      uri,
      mode: LaunchMode.externalApplication,
    );

    if (!launched && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('No se pudo abrir la ubicación en el mapa.'),
        ),
      );
    }
  }

  Widget _buildBusinessHeader(Business business) {
    return Hero(
      tag: 'business-card-${business.id}',
      child: Material(
        color: Colors.transparent,
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Theme.of(context).primaryColor.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                business.nombre,
                style: const TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  Chip(label: Text(business.categoria)),
                  Chip(
                    avatar: const Icon(Icons.storefront_outlined, size: 14),
                    label: Text('${business.totalServices ?? 0} servicios'),
                  ),
                  Chip(
                    avatar: const Icon(Icons.people_alt_outlined, size: 14),
                    label: Text('${business.totalEmployees ?? 0} empleados'),
                  ),
                ],
              ),
              if (business.descripcion != null && business.descripcion!.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(business.descripcion!),
              ],
              const SizedBox(height: 16),
              Row(
                children: [
                  const Icon(Icons.phone, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(business.telefono)),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.email_outlined, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(business.email)),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLocations(List<BusinessLocation> locations) {
    if (locations.isEmpty) {
      return const AppEmptyView(
        title: 'No hay sucursales registradas',
        icon: Icons.location_off_outlined,
      );
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: locations.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final location = locations[index];
        return Card(
          margin: EdgeInsets.zero,
          child: ListTile(
            leading: const Icon(Icons.location_on_outlined),
            title: Text(location.nombre),
            subtitle: Text(location.direccionCompleta),
            trailing: IconButton(
              tooltip: 'Abrir mapa',
              icon: const Icon(Icons.map_outlined),
              onPressed: () => _openMap(location),
            ),
          ),
        );
      },
    );
  }

  Widget _buildEmployees(List<Employee> employees) {
    if (employees.isEmpty) {
      return const AppEmptyView(
        title: 'No hay empleados visibles por ahora',
        icon: Icons.group_off_outlined,
      );
    }

    return SizedBox(
      height: 110,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: employees.length,
        separatorBuilder: (_, __) => const SizedBox(width: 10),
        itemBuilder: (context, index) {
          final employee = employees[index];

          return SizedBox(
            width: 140,
            child: Card(
              margin: EdgeInsets.zero,
              child: Padding(
                padding: const EdgeInsets.all(10),
                child: Column(
                  children: [
                    CircleAvatar(
                      radius: 20,
                      backgroundImage: (employee.fotoUrl != null &&
                              employee.fotoUrl!.isNotEmpty)
                          ? NetworkImage(employee.fotoUrl!)
                          : null,
                      child: (employee.fotoUrl == null || employee.fotoUrl!.isEmpty)
                          ? const Icon(Icons.person)
                          : null,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      employee.nombre,
                      maxLines: 2,
                      textAlign: TextAlign.center,
                      overflow: TextOverflow.ellipsis,
                      style: Theme.of(context).textTheme.bodyMedium,
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildServices(Business business, List<Service> services) {
    if (services.isEmpty) {
      return const AppEmptyView(
        title: 'No hay servicios disponibles',
        icon: Icons.design_services_outlined,
      );
    }

    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: services.length,
      separatorBuilder: (_, __) => const SizedBox(height: 8),
      itemBuilder: (context, index) {
        final service = services[index];

        return Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  service.nombre,
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 16,
                  ),
                ),
                if (service.descripcion != null && service.descripcion!.isNotEmpty) ...[
                  const SizedBox(height: 6),
                  Text(service.descripcion!),
                ],
                const SizedBox(height: 10),
                Row(
                  children: [
                    Text(
                      service.precioFormateado,
                      style: const TextStyle(fontWeight: FontWeight.w700),
                    ),
                    const SizedBox(width: 12),
                    Text(service.duracionFormateada),
                    const Spacer(),
                    FilledButton(
                      onPressed: () {
                        Navigator.of(context).pushNamed(
                          AppRoutes.booking,
                          arguments: {
                            'businessId': business.id,
                            'serviceId': service.id,
                          },
                        );
                      },
                      child: const Text('Reservar'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Detalle del Negocio'),
      ),
      body: Consumer<BusinessProvider>(
        builder: (context, provider, child) {
          if (provider.isDetailLoading && provider.selectedBusiness == null) {
            return const AppLoadingView(label: 'Cargando negocio...');
          }

          final business = provider.selectedBusiness;
          if (business == null) {
            return AppErrorView(
              message: provider.detailErrorMessage ??
                  'No se pudo cargar el negocio solicitado.',
              onRetry: () => provider.loadBusinessDetail(widget.businessId),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.loadBusinessDetail(widget.businessId),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (provider.isDetailUsingCachedData)
                  Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.amber.withOpacity(0.18),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Text(
                      'Mostrando información guardada localmente.',
                    ),
                  ),
                _buildBusinessHeader(business),
                const SizedBox(height: 20),
                Text(
                  'Sucursales',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 10),
                _buildLocations(business.locations),
                const SizedBox(height: 20),
                Text(
                  'Equipo',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 10),
                _buildEmployees(provider.employees),
                const SizedBox(height: 20),
                Text(
                  'Servicios Disponibles',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 10),
                _buildServices(business, provider.services),
              ],
            ),
          );
        },
      ),
    );
  }
}
