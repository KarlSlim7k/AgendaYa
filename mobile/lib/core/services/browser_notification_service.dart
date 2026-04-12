import 'browser_notification_service_stub.dart'
    if (dart.library.html) 'browser_notification_service_web.dart' as impl;

abstract class BrowserNotificationService {
  Future<bool> showNotification({
    required String title,
    required String body,
    String? tag,
  });
}

BrowserNotificationService get browserNotificationService =>
    impl.createBrowserNotificationService();
