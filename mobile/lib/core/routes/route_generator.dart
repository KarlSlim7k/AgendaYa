import 'package:flutter/material.dart';

import 'package:agenda_ya/features/auth/screens/login_screen.dart';
import 'package:agenda_ya/features/auth/screens/register_screen.dart';
import 'package:agenda_ya/features/booking/screens/appointment_detail_screen.dart';
import 'package:agenda_ya/features/booking/screens/booking_screen.dart';
import 'package:agenda_ya/features/business/screens/business_detail_screen.dart';
import 'package:agenda_ya/features/business/screens/business_list_screen.dart';
import 'package:agenda_ya/features/profile/screens/profile_screen.dart';
import 'package:agenda_ya/shared/screens/splash_screen.dart';

import 'app_routes.dart';

class RouteGenerator {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    final rawRouteName = settings.name ?? AppRoutes.splash;
    final routeUri = Uri.parse(rawRouteName);

    switch (routeUri.path) {
      case AppRoutes.splash:
        return _buildRoute(settings, const SplashScreen());

      case AppRoutes.login:
        return _buildRoute(settings, const LoginScreen());

      case AppRoutes.register:
        return _buildRoute(settings, const RegisterScreen());

      case AppRoutes.home:
        return _buildRoute(settings, const BusinessListScreen());

      case AppRoutes.profile:
        return _buildRoute(settings, const ProfileScreen());

      case AppRoutes.myAppointments:
        return _buildRoute(settings, const ProfileScreen());

      case AppRoutes.appointmentDetail:
        final appointmentId = _resolveAppointmentId(settings.arguments, routeUri);
        if (appointmentId != null) {
          return _buildRoute(
            settings,
            AppointmentDetailScreen(appointmentId: appointmentId),
          );
        }
        return _errorRoute('Invalid appointment ID');

      case AppRoutes.businessDetail:
        final businessId = _resolveBusinessId(settings.arguments, routeUri);
        if (businessId != null) {
          return _buildRoute(
            settings,
            BusinessDetailScreen(businessId: businessId),
          );
        }
        return _errorRoute('Invalid business ID');

      case AppRoutes.booking:
        final bookingArgs = _resolveBookingArgs(settings.arguments, routeUri);
        if (bookingArgs != null) {
          return _buildRoute(
            settings,
            BookingScreen(
              businessId: bookingArgs.businessId,
              serviceId: bookingArgs.serviceId,
            ),
          );
        }
        return _errorRoute('Invalid booking arguments');
    }

    final businessDeepLink = _resolveBusinessDeepLink(routeUri, settings);
    if (businessDeepLink != null) {
      return businessDeepLink;
    }

    final appointmentDeepLink = _resolveAppointmentDeepLink(routeUri, settings);
    if (appointmentDeepLink != null) {
      return appointmentDeepLink;
    }

    return _errorRoute('Ruta no encontrada');
  }

  static Route<dynamic>? _resolveBusinessDeepLink(
    Uri routeUri,
    RouteSettings settings,
  ) {
    if (routeUri.pathSegments.length == 2 &&
        routeUri.pathSegments.first == 'business') {
      final businessId = int.tryParse(routeUri.pathSegments[1]);
      if (businessId != null) {
        return _buildRoute(
          settings,
          BusinessDetailScreen(businessId: businessId),
        );
      }
    }

    // Supports links like: agendaya://business/42
    if (routeUri.host == 'business' && routeUri.pathSegments.length == 1) {
      final businessId = int.tryParse(routeUri.pathSegments.first);
      if (businessId != null) {
        return _buildRoute(
          settings,
          BusinessDetailScreen(businessId: businessId),
        );
      }
    }

    return null;
  }

  static int? _resolveBusinessId(dynamic args, Uri routeUri) {
    if (args is int) {
      return args;
    }

    final businessIdFromQuery = routeUri.queryParameters['businessId'];
    if (businessIdFromQuery != null) {
      return int.tryParse(businessIdFromQuery);
    }

    return null;
  }

  static int? _resolveAppointmentId(dynamic args, Uri routeUri) {
    if (args is int) {
      return args;
    }

    if (args is Map<String, int>) {
      return args['appointmentId'];
    }

    final appointmentIdFromQuery = routeUri.queryParameters['appointmentId'];
    if (appointmentIdFromQuery != null) {
      return int.tryParse(appointmentIdFromQuery);
    }

    return null;
  }

  static _BookingArgs? _resolveBookingArgs(dynamic args, Uri routeUri) {
    if (args is Map<String, int>) {
      final businessId = args['businessId'];
      final serviceId = args['serviceId'];
      if (businessId != null && serviceId != null) {
        return _BookingArgs(businessId: businessId, serviceId: serviceId);
      }
    }

    final businessIdQuery = routeUri.queryParameters['businessId'];
    final serviceIdQuery = routeUri.queryParameters['serviceId'];

    if (businessIdQuery != null && serviceIdQuery != null) {
      final businessId = int.tryParse(businessIdQuery);
      final serviceId = int.tryParse(serviceIdQuery);
      if (businessId != null && serviceId != null) {
        return _BookingArgs(businessId: businessId, serviceId: serviceId);
      }
    }

    // Supports links like: agendaya://booking?businessId=1&serviceId=2
    if (routeUri.host == 'booking') {
      final hostBusinessId = routeUri.queryParameters['businessId'];
      final hostServiceId = routeUri.queryParameters['serviceId'];
      if (hostBusinessId != null && hostServiceId != null) {
        final businessId = int.tryParse(hostBusinessId);
        final serviceId = int.tryParse(hostServiceId);
        if (businessId != null && serviceId != null) {
          return _BookingArgs(businessId: businessId, serviceId: serviceId);
        }
      }
    }

    return null;
  }

  static Route<dynamic>? _resolveAppointmentDeepLink(
    Uri routeUri,
    RouteSettings settings,
  ) {
    if (routeUri.pathSegments.length == 2 &&
        routeUri.pathSegments.first == 'appointment') {
      final appointmentId = int.tryParse(routeUri.pathSegments[1]);
      if (appointmentId != null) {
        return _buildRoute(
          settings,
          AppointmentDetailScreen(appointmentId: appointmentId),
        );
      }
    }

    // Supports links like: agendaya://appointment/42
    if (routeUri.host == 'appointment' && routeUri.pathSegments.length == 1) {
      final appointmentId = int.tryParse(routeUri.pathSegments.first);
      if (appointmentId != null) {
        return _buildRoute(
          settings,
          AppointmentDetailScreen(appointmentId: appointmentId),
        );
      }
    }

    return null;
  }

  static Route<dynamic> _buildRoute(RouteSettings settings, Widget page) {
    return MaterialPageRoute(
      settings: settings,
      builder: (_) => page,
    );
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

class _BookingArgs {
  const _BookingArgs({
    required this.businessId,
    required this.serviceId,
  });

  final int businessId;
  final int serviceId;
}
