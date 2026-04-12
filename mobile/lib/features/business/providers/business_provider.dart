import 'package:flutter/material.dart';

import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/service.dart';
import 'package:agenda_ya/data/providers/business_service.dart';

class BusinessProvider with ChangeNotifier {
  final BusinessService _businessService = BusinessService();
  
  List<Business> _businesses = [];
  Business? _selectedBusiness;
  List<Service> _services = [];
  bool _isLoading = false;
  String? _errorMessage;
  
  // Pagination
  int _currentPage = 1;
  bool _hasMorePages = true;

  List<Business> get businesses => _businesses;
  Business? get selectedBusiness => _selectedBusiness;
  List<Service> get services => _services;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  bool get hasMorePages => _hasMorePages;

  Future<void> searchBusinesses({
    String? category,
    String? search,
    String? location,
    bool refresh = false,
  }) async {
    if (refresh) {
      _currentPage = 1;
      _businesses = [];
      _hasMorePages = true;
    }

    if (!_hasMorePages || _isLoading) return;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await _businessService.searchBusinesses(
        category: category,
        search: search,
        location: location,
        page: _currentPage,
      );

      final newBusinesses = result['data'] as List<Business>;
      _businesses.addAll(newBusinesses);
      
      final meta = result['meta'];
      _hasMorePages = _currentPage < (meta['last_page'] ?? 1);
      _currentPage++;
      
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> getBusinessDetail(int businessId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _selectedBusiness = await _businessService.getBusinessDetail(businessId);
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> getBusinessServices(int businessId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      _services = await _businessService.getBusinessServices(businessId);
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  void clearSelectedBusiness() {
    _selectedBusiness = null;
    _services = [];
    notifyListeners();
  }
}
