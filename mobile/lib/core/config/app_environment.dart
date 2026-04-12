import 'package:flutter/foundation.dart';

enum AppEnvironment {
  development,
  staging,
  production,
}

class AppEnvironmentConfig {
  static const String _environment =
      String.fromEnvironment('APP_ENV', defaultValue: 'development');

  static const String _apiBaseUrlOverride =
      String.fromEnvironment('API_BASE_URL', defaultValue: '');

  static const String _apiBaseUrlDevelopment = String.fromEnvironment(
    'API_BASE_URL_DEVELOPMENT',
    defaultValue: '',
  );

  static const String _apiBaseUrlStaging = String.fromEnvironment(
    'API_BASE_URL_STAGING',
    defaultValue: '',
  );

  static const String _apiBaseUrlProduction = String.fromEnvironment(
    'API_BASE_URL_PRODUCTION',
    defaultValue: '',
  );

  static AppEnvironment get current {
    switch (_environment.toLowerCase()) {
      case 'production':
      case 'prod':
        return AppEnvironment.production;
      case 'staging':
      case 'stage':
        return AppEnvironment.staging;
      default:
        return AppEnvironment.development;
    }
  }

  static bool get isDevelopment => current == AppEnvironment.development;
  static bool get isStaging => current == AppEnvironment.staging;
  static bool get isProduction => current == AppEnvironment.production;

  static String get name => current.name;

  static String get apiBaseUrl {
    if (_apiBaseUrlOverride.isNotEmpty) {
      return _apiBaseUrlOverride;
    }

    switch (current) {
      case AppEnvironment.development:
        return _apiBaseUrlDevelopment.isNotEmpty
            ? _apiBaseUrlDevelopment
            : _defaultDevelopmentBaseUrl;
      case AppEnvironment.staging:
        return _apiBaseUrlStaging.isNotEmpty
            ? _apiBaseUrlStaging
            : _defaultDevelopmentBaseUrl;
      case AppEnvironment.production:
        return _apiBaseUrlProduction.isNotEmpty
            ? _apiBaseUrlProduction
            : _defaultDevelopmentBaseUrl;
    }
  }

  static String get _defaultDevelopmentBaseUrl {
    if (kIsWeb) {
      return 'http://127.0.0.1:8000/api/v1';
    }

    return 'http://10.0.2.2:8000/api/v1';
  }
}
