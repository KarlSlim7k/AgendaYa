import 'browser_notification_service.dart';

class UnsupportedBrowserNotificationService implements BrowserNotificationService {
  @override
  Future<bool> showNotification({
    required String title,
    required String body,
    String? tag,
  }) async {
    return false;
  }
}

BrowserNotificationService createBrowserNotificationService() {
  return UnsupportedBrowserNotificationService();
}
