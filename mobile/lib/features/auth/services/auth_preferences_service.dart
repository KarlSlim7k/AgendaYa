import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/core/constants/app_constants.dart';

class AuthPreferencesService {
  Future<bool> getRememberSession() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(AppConstants.rememberSessionKey) ?? true;
  }

  Future<void> setRememberSession(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.rememberSessionKey, value);
  }

  Future<bool> getBiometricEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(AppConstants.biometricEnabledKey) ?? false;
  }

  Future<void> setBiometricEnabled(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.biometricEnabledKey, value);
  }
}
