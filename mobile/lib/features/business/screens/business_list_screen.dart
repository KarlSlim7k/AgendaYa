import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/features/business/providers/business_provider.dart';
import 'package:agenda_ya/shared/widgets/app_state_view.dart';

class BusinessListScreen extends StatefulWidget {
  const BusinessListScreen({super.key});

  @override
  State<BusinessListScreen> createState() => _BusinessListScreenState();
}

class _BusinessListScreenState extends State<BusinessListScreen> {
  final _searchController = TextEditingController();
  final _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BusinessProvider>().searchBusinesses(refresh: true);
    });

    _scrollController.addListener(_onScroll);
  }

  @override
  void dispose() {
    _searchController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= _scrollController.position.maxScrollExtent * 0.9) {
      context.read<BusinessProvider>().searchBusinesses();
    }
  }

  void _handleSearch() {
    context.read<BusinessProvider>().searchBusinesses(
          search: _searchController.text.trim(),
          refresh: true,
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
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16.0),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: 'Buscar negocios...',
                prefixIcon: const Icon(Icons.search),
                border: const OutlineInputBorder(),
                suffixIcon: IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () {
                    _searchController.clear();
                    _handleSearch();
                  },
                ),
              ),
              onSubmitted: (_) => _handleSearch(),
            ),
          ),
          Expanded(
            child: Consumer<BusinessProvider>(
              builder: (context, provider, child) {
                if (provider.businesses.isEmpty && provider.isLoading) {
                  return const AppLoadingView(label: 'Cargando negocios...');
                }

                if (provider.businesses.isEmpty && provider.errorMessage != null) {
                  return AppErrorView(
                    message: provider.errorMessage!,
                    onRetry: () {
                      provider.searchBusinesses(
                        search: _searchController.text.trim().isNotEmpty
                            ? _searchController.text.trim()
                            : null,
                        refresh: true,
                      );
                    },
                  );
                }

                if (provider.businesses.isEmpty) {
                  return const AppEmptyView(
                    title: 'No se encontraron negocios',
                    message: 'Intenta con otro término de búsqueda o categoría.',
                    icon: Icons.storefront_outlined,
                  );
                }

                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: provider.businesses.length + (provider.hasMorePages ? 1 : 0),
                  itemBuilder: (context, index) {
                    if (index >= provider.businesses.length) {
                      return const Center(
                        child: Padding(
                          padding: EdgeInsets.all(16.0),
                          child: CircularProgressIndicator(),
                        ),
                      );
                    }

                    final business = provider.businesses[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        contentPadding: const EdgeInsets.all(16),
                        title: Text(
                          business.nombre,
                          style: const TextStyle(fontWeight: FontWeight.bold),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 4),
                            Text(business.categoria),
                            if (business.descripcion != null) ...[
                              const SizedBox(height: 4),
                              Text(
                                business.descripcion!,
                                maxLines: 2,
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                const Icon(Icons.store, size: 16),
                                const SizedBox(width: 4),
                                Text('${business.totalServices ?? 0} servicios'),
                              ],
                            ),
                          ],
                        ),
                        trailing: const Icon(Icons.arrow_forward_ios),
                        onTap: () {
                          Navigator.of(context).pushNamed(
                            AppRoutes.businessDetail,
                            arguments: business.id,
                          );
                        },
                      ),
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
