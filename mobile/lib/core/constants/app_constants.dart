class AppConstants {
  // App
  static const String appName = 'AgendaYa';
  
  // Storage Keys
  static const String authTokenKey = 'auth_token';
  static const String userDataKey = 'user_data';
  static const String rememberSessionKey = 'remember_session';
  static const String biometricEnabledKey = 'biometric_enabled';
    static const String whatsappRemindersEnabledKey =
      'whatsapp_reminders_enabled';
    static const String browserNotificationsEnabledKey =
      'browser_notifications_enabled';
    static const String reminderPlansKey = 'appointment_reminder_plans_v1';
    static const String notificationLogsKey = 'notification_delivery_logs_v1';
  
  // Date Formats
  static const String dateFormat = 'dd/MM/yyyy';
  static const String timeFormat = 'HH:mm';
  static const String dateTimeFormat = 'dd/MM/yyyy HH:mm';
  
  // Pagination
  static const int defaultPageSize = 15;
  
  // Error Messages
  static const String networkError = 'Error de conexión. Verifica tu internet.';
  static const String serverError = 'Error del servidor. Intenta más tarde.';
  static const String unauthorizedError = 'Sesión expirada. Inicia sesión nuevamente.';
}
