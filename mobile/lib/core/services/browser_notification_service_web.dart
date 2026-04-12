import 'dart:html' as html;

import 'browser_notification_service.dart';

class WebBrowserNotificationService implements BrowserNotificationService {
  @override
  Future<bool> showNotification({
    required String title,
    required String body,
    String? tag,
  }) async {
    if (!html.Notification.supported) {
      return false;
    }

    var permission = html.Notification.permission;
    if (permission != 'granted') {
      permission = await html.Notification.requestPermission();
    }

    if (permission != 'granted') {
      return false;
    }

    html.Notification(
      title,
      body: body,
      tag: tag,
    );

    return true;
  }
}

BrowserNotificationService createBrowserNotificationService() {
  return WebBrowserNotificationService();
}
