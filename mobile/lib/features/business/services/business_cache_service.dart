import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/employee.dart';
import 'package:agenda_ya/data/models/service.dart';

class BusinessSearchCacheSnapshot {
  const BusinessSearchCacheSnapshot({
    required this.businesses,
    required this.meta,
  });

  final List<Business> businesses;
  final Map<String, dynamic> meta;
}

class BusinessCacheService {
  static const String _searchPrefix = 'business_search_cache_v1_';
  static const String _detailPrefix = 'business_detail_cache_v1_';
  static const String _servicePrefix = 'business_services_cache_v1_';
  static const String _employeePrefix = 'business_employees_cache_v1_';

  Future<void> cacheSearchSnapshot({
    required String cacheKey,
    required List<Business> businesses,
    required Map<String, dynamic> meta,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    final data = {
      'businesses': businesses.map((business) => business.toJson()).toList(),
      'meta': meta,
    };

    await prefs.setString(
      '$_searchPrefix$cacheKey',
      jsonEncode(data),
    );
  }

  Future<BusinessSearchCacheSnapshot?> readSearchSnapshot(
    String cacheKey,
  ) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_searchPrefix$cacheKey');
    if (raw == null || raw.isEmpty) {
      return null;
    }

    final decoded = jsonDecode(raw) as Map<String, dynamic>;
    final businessesRaw = (decoded['businesses'] as List?) ?? const [];
    final metaRaw = decoded['meta'];

    final businesses = businessesRaw
        .whereType<Map<String, dynamic>>()
        .map(Business.fromJson)
        .toList();

    final meta = metaRaw is Map<String, dynamic>
        ? metaRaw
        : <String, dynamic>{};

    return BusinessSearchCacheSnapshot(
      businesses: businesses,
      meta: meta,
    );
  }

  Future<void> cacheBusinessDetail(Business business) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      '$_detailPrefix${business.id}',
      jsonEncode(business.toJson()),
    );
  }

  Future<Business?> readBusinessDetail(int businessId) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_detailPrefix$businessId');
    if (raw == null || raw.isEmpty) {
      return null;
    }

    final decoded = jsonDecode(raw) as Map<String, dynamic>;
    return Business.fromJson(decoded);
  }

  Future<void> cacheBusinessServices({
    required int businessId,
    required List<Service> services,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      '$_servicePrefix$businessId',
      jsonEncode(services.map((service) => service.toJson()).toList()),
    );
  }

  Future<List<Service>> readBusinessServices(int businessId) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_servicePrefix$businessId');
    if (raw == null || raw.isEmpty) {
      return const [];
    }

    final decoded = jsonDecode(raw) as List<dynamic>;
    return decoded
        .whereType<Map<String, dynamic>>()
        .map(Service.fromJson)
        .toList();
  }

  Future<void> cacheBusinessEmployees({
    required int businessId,
    required List<Employee> employees,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(
      '$_employeePrefix$businessId',
      jsonEncode(employees.map((employee) => employee.toJson()).toList()),
    );
  }

  Future<List<Employee>> readBusinessEmployees(int businessId) async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString('$_employeePrefix$businessId');
    if (raw == null || raw.isEmpty) {
      return const [];
    }

    final decoded = jsonDecode(raw) as List<dynamic>;
    return decoded
        .whereType<Map<String, dynamic>>()
        .map(Employee.fromJson)
        .toList();
  }
}
