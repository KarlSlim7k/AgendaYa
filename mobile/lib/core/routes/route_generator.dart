import 'package:flutter/material.dart';
import '../business/screens/business_list_screen.dart';
import '../business/screens/business_detail_screen.dart';
import '../auth/screens/login_screen.dart';
import '../auth/screens/register_screen.dart';
import '../booking/screens/booking_screen.dart';
import '../profile/screens/profile_screen.dart';
import 'app_routes.dart';

class RouteGenerator {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    final args = settings.arguments;

    switch (settings.name) {
      case AppRoutes.login:
        return MaterialPageRoute(builder: (_) => const LoginScreen());

      case AppRoutes.register:
        return MaterialPageRoute(builder: (_) => const RegisterScreen());

      case AppRoutes.home:
        return MaterialPageRoute(builder: (_) => const BusinessListScreen());

      case AppRoutes.businessDetail:
        if (args is int) {
          return MaterialPageRoute(
            builder: (_) => BusinessDetailScreen(businessId: args),
          );
        }
        return _errorRoute('Invalid business ID');

      case AppRoutes.booking:
        if (args is Map<String, int>) {
          final businessId = args['businessId'];
          final serviceId = args['serviceId'];
          if (businessId != null && serviceId != null) {
            return MaterialPageRoute(
              builder: (_) => BookingScreen(
                businessId: businessId,
                serviceId: serviceId,
              ),
            );
          }
        }
        return _errorRoute('Invalid booking arguments');

      case AppRoutes.profile:
        return MaterialPageRoute(builder: (_) => const ProfileScreen());

      default:
        return _errorRoute('Ruta no encontrada');
    }
  }

  static Route<dynamic> _errorRoute(String message) {
    return MaterialPageRoute(
      builder: (_) => Scaffold(
        appBar: AppBar(title: const Text('Error')),
        body: Center(child: Text(message)),
      ),
    );
  }
}
