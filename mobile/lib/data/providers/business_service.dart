import 'package:agenda_ya/core/constants/api_constants.dart';
import 'package:agenda_ya/data/models/business.dart';
import 'package:agenda_ya/data/models/service.dart';

import 'api_client.dart';

class BusinessService {
  final ApiClient _apiClient = ApiClient();

  Future<Map<String, dynamic>> searchBusinesses({
    String? category,
    String? search,
    String? location,
    int page = 1,
  }) async {
    final queryParams = <String, String>{
      'page': page.toString(),
    };

    if (category != null) queryParams['category'] = category;
    if (search != null) queryParams['search'] = search;
    if (location != null) queryParams['location'] = location;

    final response = await _apiClient.get(
      ApiConstants.businesses,
      queryParams: queryParams,
    );

    final data = _apiClient.handleResponse(response);

    final collection = _extractList(data);
    final businesses = collection
        .map((json) => Business.fromJson(json))
        .toList();

    return {
      'data': businesses,
      'meta': _extractMeta(data),
    };
  }

  Future<Business> getBusinessDetail(int businessId) async {
    final response = await _apiClient.get(
      ApiConstants.businessDetail(businessId),
    );

    final data = _apiClient.handleResponse(response);
    final payload = _extractPayload(data);
    return Business.fromJson(payload);
  }

  Future<List<Service>> getBusinessServices(int businessId) async {
    final response = await _apiClient.get(
      ApiConstants.businessServices(businessId),
    );

    final data = _apiClient.handleResponse(response);

    return _extractList(data)
        .map((json) => Service.fromJson(json))
        .toList();
  }

  List<Map<String, dynamic>> _extractList(Map<String, dynamic> data) {
    if (data['data'] is List) {
      return (data['data'] as List)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    final payload = data['data'];
    if (payload is Map<String, dynamic> && payload['data'] is List) {
      return (payload['data'] as List)
          .whereType<Map<String, dynamic>>()
          .toList();
    }

    return <Map<String, dynamic>>[];
  }

  Map<String, dynamic> _extractMeta(Map<String, dynamic> data) {
    if (data['meta'] is Map<String, dynamic>) {
      return data['meta'] as Map<String, dynamic>;
    }

    final payload = data['data'];
    if (payload is Map<String, dynamic> && payload['meta'] is Map<String, dynamic>) {
      return payload['meta'] as Map<String, dynamic>;
    }

    return <String, dynamic>{};
  }

  Map<String, dynamic> _extractPayload(Map<String, dynamic> data) {
    final payload = data['data'];
    if (payload is Map<String, dynamic>) {
      return payload;
    }

    return data;
  }
}
