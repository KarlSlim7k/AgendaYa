import 'package:flutter/foundation.dart';

import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/employee.dart';
import 'package:agenda_ya/data/models/service.dart';
import 'package:agenda_ya/data/providers/business_service.dart';
import 'package:agenda_ya/features/business/services/business_cache_service.dart';

class BusinessProvider extends ChangeNotifier {
  final BusinessService _businessService;
  final BusinessCacheService _businessCacheService;

  BusinessProvider({
    BusinessService? businessService,
    BusinessCacheService? businessCacheService,
  })  : _businessService = businessService ?? BusinessService(),
        _businessCacheService = businessCacheService ?? BusinessCacheService();

  static const String allCategoriesValue = 'all';

  static const Map<String, String> categoryOptions = {
    'Todas': allCategoriesValue,
    'Peluquería': 'peluqueria',
    'Barbería': 'barberia',
    'Spa': 'spa',
    'Clínica': 'clinica',
    'Taller': 'taller',
    'Otro': 'otro',
  };

  List<Business> _businesses = [];
  Business? _selectedBusiness;
  List<Service> _services = [];
  List<Employee> _employees = [];

  bool _isListLoading = false;
  bool _isLoadingMore = false;
  bool _isDetailLoading = false;

  bool _isUsingCachedData = false;
  bool _isDetailUsingCachedData = false;

  String? _errorMessage;
  String? _detailErrorMessage;

  int _currentPage = 1;
  bool _hasMorePages = true;

  String _selectedCategory = allCategoriesValue;
  String _searchQuery = '';
  String _locationQuery = '';

  List<Business> get businesses => _businesses;
  Business? get selectedBusiness => _selectedBusiness;
  List<Service> get services => _services;
  List<Employee> get employees => _employees;

  bool get isListLoading => _isListLoading;
  bool get isLoadingMore => _isLoadingMore;
  bool get isDetailLoading => _isDetailLoading;

  bool get isUsingCachedData => _isUsingCachedData;
  bool get isDetailUsingCachedData => _isDetailUsingCachedData;

  String? get errorMessage => _errorMessage;
  String? get detailErrorMessage => _detailErrorMessage;

  bool get hasMorePages => _hasMorePages;

  String get selectedCategory => _selectedCategory;
  String get searchQuery => _searchQuery;
  String get locationQuery => _locationQuery;

  Future<void> applyFilters({
    String? category,
    String? search,
    String? location,
  }) async {
    if (category != null) {
      _selectedCategory = category;
    }

    if (search != null) {
      _searchQuery = search.trim();
    }

    if (location != null) {
      _locationQuery = location.trim();
    }

    await searchBusinesses(refresh: true);
  }

  Future<void> resetFilters() async {
    _selectedCategory = allCategoriesValue;
    _searchQuery = '';
    _locationQuery = '';

    await searchBusinesses(refresh: true);
  }

  Future<void> refreshBusinesses() {
    return searchBusinesses(refresh: true);
  }

  Future<void> loadMoreBusinesses() {
    return searchBusinesses(refresh: false);
  }

  Future<void> searchBusinesses({
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _hasMorePages = true;
      _businesses = [];
    }

    if (refresh) {
      if (_isListLoading) {
        return;
      }
      _isListLoading = true;
    } else {
      if (_isListLoading || _isLoadingMore || !_hasMorePages) {
        return;
      }
      _isLoadingMore = true;
    }

    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _businessService.searchBusinesses(
        category: _selectedCategory == allCategoriesValue
            ? null
            : _selectedCategory,
        search: _searchQuery.isEmpty ? null : _searchQuery,
        location: _locationQuery.isEmpty ? null : _locationQuery,
        page: _currentPage,
      );

      final newBusinesses = result['data'] as List<Business>;
      final meta = result['meta'] is Map<String, dynamic>
          ? result['meta'] as Map<String, dynamic>
          : <String, dynamic>{};

      if (refresh) {
        _businesses = newBusinesses;
      } else {
        _businesses = [..._businesses, ...newBusinesses];
      }

      final parsedLastPage = (meta['last_page'] as num?)?.toInt();
      if (parsedLastPage != null) {
        _hasMorePages = _currentPage < parsedLastPage;
      } else {
        _hasMorePages = newBusinesses.isNotEmpty;
      }

      _currentPage++;
      _isUsingCachedData = false;

      await _businessCacheService.cacheSearchSnapshot(
        cacheKey: _searchCacheKey,
        businesses: _businesses,
        meta: meta,
      );
    } catch (e) {
      if (refresh) {
        final cached = await _businessCacheService.readSearchSnapshot(
          _searchCacheKey,
        );

        if (cached != null && cached.businesses.isNotEmpty) {
          _businesses = cached.businesses;
          _hasMorePages = false;
          _isUsingCachedData = true;
          _errorMessage =
              'Sin conexión. Mostrando resultados guardados localmente.';
        } else {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
        }
      } else {
        _errorMessage = e.toString().replaceAll('Exception: ', '');
      }
    } finally {
      _isListLoading = false;
      _isLoadingMore = false;
      notifyListeners();
    }
  }

  Future<void> loadBusinessDetail(int businessId) async {
    _selectedBusiness = null;
    _services = [];
    _employees = [];

    _isDetailLoading = true;
    _detailErrorMessage = null;
    _isDetailUsingCachedData = false;
    notifyListeners();

    Business? business;
    List<Service> services = const [];
    List<Employee> employees = const [];
    bool usedCache = false;

    try {
      business = await _businessService.getBusinessDetail(businessId);
      await _businessCacheService.cacheBusinessDetail(business);
    } catch (e) {
      business = await _businessCacheService.readBusinessDetail(businessId);
      usedCache = business != null;
      if (business == null) {
        _detailErrorMessage = e.toString().replaceAll('Exception: ', '');
      }
    }

    try {
      services = await _businessService.getBusinessServices(businessId);
      await _businessCacheService.cacheBusinessServices(
        businessId: businessId,
        services: services,
      );
    } catch (_) {
      services = await _businessCacheService.readBusinessServices(businessId);
      usedCache = usedCache || services.isNotEmpty;
    }

    try {
      employees = await _businessService.getBusinessEmployees(businessId);
      await _businessCacheService.cacheBusinessEmployees(
        businessId: businessId,
        employees: employees,
      );
    } catch (_) {
      employees = await _businessCacheService.readBusinessEmployees(businessId);
      usedCache = usedCache || employees.isNotEmpty;
    }

    _selectedBusiness = business;
    _services = services;
    _employees = employees;
    _isDetailUsingCachedData = usedCache;

    if (_selectedBusiness == null && _detailErrorMessage == null) {
      _detailErrorMessage = 'No se pudo cargar el negocio solicitado.';
    }

    _isDetailLoading = false;
    notifyListeners();
  }

  Future<void> getBusinessDetail(int businessId) {
    return loadBusinessDetail(businessId);
  }

  Future<void> getBusinessServices(int businessId) async {
    try {
      _services = await _businessService.getBusinessServices(businessId);
      await _businessCacheService.cacheBusinessServices(
        businessId: businessId,
        services: _services,
      );
      notifyListeners();
    } catch (e) {
      _detailErrorMessage = e.toString().replaceAll('Exception: ', '');
      notifyListeners();
    }
  }

  void clearError() {
    _errorMessage = null;
    _detailErrorMessage = null;
    notifyListeners();
  }

  void clearSelectedBusiness() {
    _selectedBusiness = null;
    _services = [];
    _employees = [];
    _detailErrorMessage = null;
    _isDetailUsingCachedData = false;
    notifyListeners();
  }

  String get _searchCacheKey {
    return '${_selectedCategory.toLowerCase()}|'
        '${_searchQuery.toLowerCase()}|'
        '${_locationQuery.toLowerCase()}';
  }
}
