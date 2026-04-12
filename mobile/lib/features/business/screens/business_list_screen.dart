import 'dart:async';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/data/models/appointment.dart';
import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/business/providers/business_provider.dart';
import 'package:agenda_ya/shared/widgets/app_state_view.dart';

class BusinessListScreen extends StatefulWidget {
  const BusinessListScreen({super.key});

  @override
  State<BusinessListScreen> createState() => _BusinessListScreenState();
}

class _BusinessListScreenState extends State<BusinessListScreen> {
  final _searchController = TextEditingController();
  final _locationController = TextEditingController();
  final _scrollController = ScrollController();

  Timer? _searchDebounce;
  Timer? _locationDebounce;

  @override
  void initState() {
    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<BusinessProvider>();

      _searchController.text = provider.searchQuery;
      _locationController.text = provider.locationQuery;

      provider.searchBusinesses(refresh: true);
      context.read<AppointmentProvider>().loadMyAppointments(showLoading: false);
    });

    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchDebounce?.cancel();
    _locationDebounce?.cancel();
    _searchController.dispose();
    _locationController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    final provider = context.read<BusinessProvider>();
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent * 0.88) {
      provider.loadMoreBusinesses();
    }
  }

  void _onSearchChanged() {
    _searchDebounce?.cancel();
    _searchDebounce = Timer(const Duration(milliseconds: 450), () {
      if (!mounted) {
        return;
      }

      context.read<BusinessProvider>().applyFilters(
            search: _searchController.text,
          );
    });
  }

  void _onLocationChanged() {
    _locationDebounce?.cancel();
    _locationDebounce = Timer(const Duration(milliseconds: 500), () {
      if (!mounted) {
        return;
      }

      context.read<BusinessProvider>().applyFilters(
            location: _locationController.text,
          );
    });
  }

  Future<void> _resetFilters() async {
    _searchController.clear();
    _locationController.clear();
    await context.read<BusinessProvider>().resetFilters();
  }

  void _openBusinessDetail(Business business) {
    Navigator.of(context).pushNamed(
      AppRoutes.businessDeepLink(business.id),
      arguments: business.id,
    );
  }

  Widget _buildBusinessCard(BuildContext context, Business business) {
    final theme = Theme.of(context);

    return Hero(
      tag: 'business-card-${business.id}',
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: () => _openBusinessDetail(business),
          child: Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          business.nombre,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.arrow_forward_ios, size: 14),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      Chip(
                        visualDensity: VisualDensity.compact,
                        label: Text(business.categoria),
                      ),
                      Chip(
                        visualDensity: VisualDensity.compact,
                        avatar: const Icon(Icons.store_mall_directory, size: 14),
                        label: Text('${business.totalServices ?? 0} servicios'),
                      ),
                    ],
                  ),
                  if (business.descripcion != null && business.descripcion!.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.only(top: 10),
                      child: Text(
                        business.descripcion!,
                        maxLines: 3,
                        overflow: TextOverflow.ellipsis,
                        style: theme.textTheme.bodyMedium,
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFilters({
    required BuildContext context,
    required BusinessProvider provider,
    required bool desktop,
  }) {
    final categoryItems = BusinessProvider.categoryOptions.entries.toList();

    final categoryField = DropdownButtonFormField<String>(
      value: provider.selectedCategory,
      decoration: const InputDecoration(
        labelText: 'Categoría',
        prefixIcon: Icon(Icons.category_outlined),
      ),
      items: categoryItems
          .map(
            (entry) => DropdownMenuItem<String>(
              value: entry.value,
              child: Text(entry.key),
            ),
          )
          .toList(),
      onChanged: provider.isListLoading
          ? null
          : (value) {
              if (value == null) {
                return;
              }

              provider.applyFilters(category: value);
            },
    );

    final searchField = TextField(
      controller: _searchController,
      textInputAction: TextInputAction.search,
      decoration: InputDecoration(
        labelText: 'Buscar negocio',
        hintText: 'Nombre o palabra clave',
        prefixIcon: const Icon(Icons.search),
        suffixIcon: _searchController.text.isEmpty
            ? null
            : IconButton(
                icon: const Icon(Icons.close),
                onPressed: () {
                  _searchController.clear();
                  provider.applyFilters(search: '');
                  setState(() {});
                },
              ),
      ),
      onChanged: (_) {
        setState(() {});
        _onSearchChanged();
      },
      onSubmitted: (_) => provider.applyFilters(search: _searchController.text),
    );

    final locationField = TextField(
      controller: _locationController,
      textInputAction: TextInputAction.search,
      decoration: InputDecoration(
        labelText: 'Ubicación',
        hintText: 'Ciudad, zona o colonia',
        prefixIcon: const Icon(Icons.place_outlined),
        suffixIcon: _locationController.text.isEmpty
            ? null
            : IconButton(
                icon: const Icon(Icons.close),
                onPressed: () {
                  _locationController.clear();
                  provider.applyFilters(location: '');
                  setState(() {});
                },
              ),
      ),
      onChanged: (_) {
        setState(() {});
        _onLocationChanged();
      },
      onSubmitted: (_) =>
          provider.applyFilters(location: _locationController.text),
    );

    final resetButton = Align(
      alignment: Alignment.centerRight,
      child: TextButton.icon(
        onPressed: provider.isListLoading ? null : _resetFilters,
        icon: const Icon(Icons.restart_alt),
        label: const Text('Limpiar filtros'),
      ),
    );

    final content = Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        searchField,
        const SizedBox(height: 12),
        locationField,
        const SizedBox(height: 12),
        categoryField,
        const SizedBox(height: 8),
        resetButton,
      ],
    );

    if (desktop) {
      return Card(
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: content,
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
      child: content,
    );
  }

  Widget _buildUpcomingAppointmentWidget() {
    return Consumer<AppointmentProvider>(
      builder: (context, appointmentProvider, child) {
        final upcoming = appointmentProvider.upcomingAppointments;
        if (appointmentProvider.isLoading && upcoming.isEmpty) {
          return const Padding(
            padding: EdgeInsets.fromLTRB(16, 12, 16, 4),
            child: LinearProgressIndicator(),
          );
        }

        if (upcoming.isEmpty) {
          return const SizedBox.shrink();
        }

        final Appointment next = upcoming.first;
        final dateLabel = DateFormat('EEE dd MMM, HH:mm', 'es').format(
          next.fechaHoraInicio.toLocal(),
        );

        return Card(
          margin: const EdgeInsets.fromLTRB(16, 12, 16, 8),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Próxima cita',
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  next.serviceName ?? 'Servicio',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 4),
                Text(next.businessName ?? 'Negocio'),
                const SizedBox(height: 4),
                Row(
                  children: [
                    const Icon(Icons.schedule, size: 16),
                    const SizedBox(width: 6),
                    Text(dateLabel),
                  ],
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 8,
                  children: [
                    OutlinedButton.icon(
                      onPressed: () {
                        Navigator.of(context).pushNamed(
                          AppRoutes.appointmentDetailDeepLink(next.id),
                          arguments: next.id,
                        );
                      },
                      icon: const Icon(Icons.visibility_outlined),
                      label: const Text('Detalle'),
                    ),
                    TextButton(
                      onPressed: () {
                        Navigator.of(context).pushNamed(AppRoutes.profile);
                      },
                      child: const Text('Ver todas'),
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

  int _getCrossAxisCount(double width) {
    if (width >= 1280) {
      return 3;
    }

    if (width >= 760) {
      return 2;
    }

    return 1;
  }

  double _getAspectRatio(int crossAxisCount) {
    if (crossAxisCount == 1) {
      return 2.4;
    }

    if (crossAxisCount == 2) {
      return 1.55;
    }

    return 1.45;
  }

  Widget _buildDiscoveryContent({
    required BuildContext context,
    required BusinessProvider provider,
    required bool desktop,
  }) {
    if (provider.businesses.isEmpty && provider.isListLoading) {
      return const AppLoadingView(label: 'Cargando negocios...');
    }

    if (provider.businesses.isEmpty && provider.errorMessage != null) {
      return AppErrorView(
        message: provider.errorMessage!,
        onRetry: provider.refreshBusinesses,
      );
    }

    if (provider.businesses.isEmpty) {
      return const AppEmptyView(
        title: 'No se encontraron negocios',
        message: 'Ajusta los filtros para probar otra búsqueda.',
        icon: Icons.storefront_outlined,
      );
    }

    return LayoutBuilder(
      builder: (context, constraints) {
        final crossAxisCount = _getCrossAxisCount(constraints.maxWidth);
        final childAspectRatio = _getAspectRatio(crossAxisCount);

        final grid = GridView.builder(
          controller: _scrollController,
          padding: EdgeInsets.fromLTRB(desktop ? 0 : 16, 8, 16, 20),
          physics: const AlwaysScrollableScrollPhysics(),
          gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: crossAxisCount,
            crossAxisSpacing: 12,
            mainAxisSpacing: 12,
            childAspectRatio: childAspectRatio,
          ),
          itemCount:
              provider.businesses.length + (provider.hasMorePages ? 1 : 0),
          itemBuilder: (context, index) {
            if (index >= provider.businesses.length) {
              return const Center(child: CircularProgressIndicator());
            }

            final business = provider.businesses[index];
            return _buildBusinessCard(context, business);
          },
        );

        return RefreshIndicator(
          onRefresh: provider.refreshBusinesses,
          child: grid,
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Negocios'),
        actions: [
          IconButton(
            icon: const Icon(Icons.person),
            onPressed: () {
              Navigator.of(context).pushNamed(AppRoutes.profile);
            },
          ),
        ],
      ),
      body: Consumer<BusinessProvider>(
        builder: (context, provider, child) {
          return LayoutBuilder(
            builder: (context, constraints) {
              final isDesktop = constraints.maxWidth >= 1024;

              final content = _buildDiscoveryContent(
                context: context,
                provider: provider,
                desktop: isDesktop,
              );

              final offlineBanner = provider.isUsingCachedData
                  ? Container(
                      width: double.infinity,
                      margin: const EdgeInsets.fromLTRB(16, 0, 16, 8),
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.amber.withOpacity(0.18),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Text(
                        'Modo sin conexión: mostrando datos guardados.',
                      ),
                    )
                  : const SizedBox.shrink();

              final upcomingAppointmentWidget = _buildUpcomingAppointmentWidget();

              if (isDesktop) {
                return Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      upcomingAppointmentWidget,
                      offlineBanner,
                      Expanded(
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            SizedBox(
                              width: 300,
                              child: _buildFilters(
                                context: context,
                                provider: provider,
                                desktop: true,
                              ),
                            ),
                            const SizedBox(width: 16),
                            Expanded(child: content),
                          ],
                        ),
                      ),
                    ],
                  ),
                );
              }

              return Column(
                children: [
                  upcomingAppointmentWidget,
                  _buildFilters(
                    context: context,
                    provider: provider,
                    desktop: false,
                  ),
                  offlineBanner,
                  Expanded(child: content),
                ],
              );
            },
          );
        },
      ),
    );
  }
}
