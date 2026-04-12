import 'package:shared_preferences/shared_preferences.dart';

import 'package:agenda_ya/core/constants/app_constants.dart';

class NotificationPreferencesService {
  Future<bool> getWhatsAppRemindersEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(AppConstants.whatsappRemindersEnabledKey) ?? false;
  }

  Future<void> setWhatsAppRemindersEnabled(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.whatsappRemindersEnabledKey, value);
  }

  Future<bool> getBrowserNotificationsEnabled() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getBool(AppConstants.browserNotificationsEnabledKey) ?? true;
  }

  Future<void> setBrowserNotificationsEnabled(bool value) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(AppConstants.browserNotificationsEnabledKey, value);
  }
}
