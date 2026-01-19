import '../core/constants/api_constants.dart';
import '../models/business.dart';
import '../models/service.dart';
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
    
    final businesses = (data['data'] as List)
        .map((json) => Business.fromJson(json))
        .toList();
    
    return {
      'data': businesses,
      'meta': data['meta'],
    };
  }

  Future<Business> getBusinessDetail(int businessId) async {
    final response = await _apiClient.get(
      ApiConstants.businessDetail(businessId),
    );

    final data = _apiClient.handleResponse(response);
    return Business.fromJson(data['data']);
  }

  Future<List<Service>> getBusinessServices(int businessId) async {
    final response = await _apiClient.get(
      ApiConstants.businessServices(businessId),
    );

    final data = _apiClient.handleResponse(response);
    
    return (data['data'] as List)
        .map((json) => Service.fromJson(json))
        .toList();
  }
}
