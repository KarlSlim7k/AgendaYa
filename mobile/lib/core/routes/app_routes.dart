class AppRoutes {
  static const String splash = '/';
  static const String login = '/login';
  static const String register = '/register';
  static const String home = '/home';
  static const String businessDetail = '/business-detail';
  static const String businessDeepLinkPrefix = '/business';
  static const String booking = '/booking';
  static const String profile = '/profile';
  static const String myAppointments = '/my-appointments';
  static const String appointmentDetail = '/appointment-detail';
  static const String appointmentDeepLinkPrefix = '/appointment';

  static String businessDeepLink(int businessId) =>
      '$businessDeepLinkPrefix/$businessId';

  static String bookingDeepLink({
    required int businessId,
    required int serviceId,
  }) {
    return '$booking?businessId=$businessId&serviceId=$serviceId';
  }

  static String appointmentDetailDeepLink(int appointmentId) {
    return '$appointmentDetail?appointmentId=$appointmentId';
  }

  static String appointmentShortDeepLink(int appointmentId) {
    return '$appointmentDeepLinkPrefix/$appointmentId';
  }
}
