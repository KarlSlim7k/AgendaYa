import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:provider/provider.dart';

import 'package:agenda_ya/core/config/app_environment.dart';
import 'package:agenda_ya/core/routes/app_routes.dart';
import 'package:agenda_ya/core/routes/route_generator.dart';
import 'package:agenda_ya/core/theme/app_theme.dart';
import 'package:agenda_ya/core/widgets/responsive_frame.dart';
import 'package:agenda_ya/features/auth/providers/auth_provider.dart';
import 'package:agenda_ya/features/booking/providers/appointment_provider.dart';
import 'package:agenda_ya/features/business/providers/business_provider.dart';

final GlobalKey<NavigatorState> appNavigatorKey = GlobalKey<NavigatorState>();

class AgendaYaApp extends StatelessWidget {
  const AgendaYaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => BusinessProvider()),
        ChangeNotifierProvider(create: (_) => AppointmentProvider()),
      ],
      child: MaterialApp(
        navigatorKey: appNavigatorKey,
        title: _buildTitle(),
        debugShowCheckedModeBanner: false,
        theme: AppTheme.lightTheme,
        darkTheme: AppTheme.darkTheme,
        themeMode: ThemeMode.system,
        initialRoute: AppRoutes.splash,
        onGenerateRoute: RouteGenerator.generateRoute,
        locale: const Locale('es', 'MX'),
        supportedLocales: const [
          Locale('es', 'MX'),
          Locale('es'),
        ],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (context, child) {
          if (child == null) {
            return const SizedBox.shrink();
          }

          return ResponsiveFrame(child: child);
        },
      ),
    );
  }

  String _buildTitle() {
    if (AppEnvironmentConfig.isDevelopment) {
      return 'AgendaYa DEV';
    }

    if (AppEnvironmentConfig.isStaging) {
      return 'AgendaYa STG';
    }

    return 'AgendaYa';
  }
}
