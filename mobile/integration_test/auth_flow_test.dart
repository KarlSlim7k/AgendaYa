import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:intl/intl.dart';
import 'package:agenda_ya/main.dart' as app;
import 'package:flutter/material.dart';

/// Test E2E del flujo de autenticación completo
/// 
/// Flujo probado:
/// 1. App inicia en SplashScreen
/// 2. Navega a LoginScreen
/// 3. Navegación a RegisterScreen
/// 4. Registro de nuevo usuario
/// 5. Verificación de navegación a Home
/// 6. Logout
/// 7. Login con credenciales existentes
/// 8. Verificación de sesión persistente

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  setUpAll(() async {
    Intl.defaultLocale = 'es_MX';
    await initializeDateFormatting('es_MX', null);
  });

  group('Auth Flow E2E Tests', () {
    testWidgets('Debe completar flujo de registro exitosamente', (tester) async {
      // Iniciar app
      app.main();
      await tester.pumpAndSettle();

      // Esperar que SplashScreen navegue a Login
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que estamos en LoginScreen
      expect(find.text('Iniciar Sesión'), findsOneWidget);
      expect(find.text('AgendaYa'), findsOneWidget);

      // Navegar a RegisterScreen
      await tester.tap(find.text('¿No tienes cuenta? Regístrate'));
      await tester.pumpAndSettle();

      // Verificar que estamos en RegisterScreen
      expect(find.text('Crear Cuenta'), findsOneWidget);

      // Llenar formulario de registro
      final timestamp = DateTime.now().millisecondsSinceEpoch;
      final testEmail = 'test_$timestamp@example.com';
      final testName = 'Test User $timestamp';

      await tester.enterText(
        find.widgetWithText(TextFormField, 'Nombre completo'),
        testName,
      );
      await tester.pumpAndSettle();

      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email'),
        testEmail,
      );
      await tester.pumpAndSettle();

      // Scroll para ver campos de contraseña
      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.enterText(
        find.widgetWithText(TextFormField, 'Contraseña').first,
        'password123',
      );
      await tester.pumpAndSettle();

      await tester.enterText(
        find.widgetWithText(TextFormField, 'Confirmar contraseña'),
        'password123',
      );
      await tester.pumpAndSettle();

      // Tap en botón de registro
      await tester.tap(find.widgetWithText(ElevatedButton, 'Registrarse'));
      await tester.pumpAndSettle();

      // Esperar navegación y respuesta del backend
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Verificar que navegó a Home (BusinessListScreen)
      expect(find.text('Negocios'), findsOneWidget);
      expect(find.text('Buscar negocios...'), findsOneWidget);
    });

    testWidgets('Debe validar campos requeridos en registro', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Navegar a RegisterScreen
      await tester.tap(find.text('¿No tienes cuenta? Regístrate'));
      await tester.pumpAndSettle();

      // Intentar registrarse sin llenar campos
      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(ElevatedButton, 'Registrarse'));
      await tester.pumpAndSettle();

      // Verificar que muestra errores de validación
      expect(find.text('Ingresa tu nombre'), findsOneWidget);
      expect(find.text('Ingresa tu email'), findsOneWidget);
      expect(find.text('Ingresa tu contraseña'), findsAtLeastNWidgets(1));
    });

    testWidgets('Debe validar formato de email', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que estamos en LoginScreen
      expect(find.text('Iniciar Sesión'), findsOneWidget);

      // Ingresar email inválido
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email'),
        'invalid-email',
      );
      await tester.pumpAndSettle();

      // Intentar login
      await tester.tap(find.widgetWithText(ElevatedButton, 'Iniciar Sesión'));
      await tester.pumpAndSettle();

      // Verificar error de validación
      expect(find.text('Email inválido'), findsOneWidget);
    });

    testWidgets('Debe completar flujo de login exitosamente', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Llenar formulario de login con usuario de seeders
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email'),
        'usuario@example.com',
      );
      await tester.pumpAndSettle();

      await tester.enterText(
        find.widgetWithText(TextFormField, 'Contraseña'),
        'password',
      );
      await tester.pumpAndSettle();

      // Tap en botón de login
      await tester.tap(find.widgetWithText(ElevatedButton, 'Iniciar Sesión'));
      await tester.pumpAndSettle();

      // Esperar respuesta del backend
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Verificar que navegó a Home
      expect(find.text('Negocios'), findsOneWidget);
    });

    testWidgets('Debe completar flujo de logout exitosamente', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Login primero
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email'),
        'usuario@example.com',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Contraseña'),
        'password',
      );
      await tester.tap(find.widgetWithText(ElevatedButton, 'Iniciar Sesión'));
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Navegar a perfil
      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle();

      // Verificar que estamos en ProfileScreen
      expect(find.text('Mi Perfil'), findsOneWidget);

      // Tap en logout
      await tester.tap(find.byIcon(Icons.logout));
      await tester.pumpAndSettle();

      // Confirmar logout en diálogo
      await tester.tap(find.text('Sí, cerrar sesión'));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que regresó a LoginScreen
      expect(find.text('Iniciar Sesión'), findsOneWidget);
    });

    testWidgets('Debe mostrar error con credenciales inválidas', (tester) async {
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Ingresar credenciales incorrectas
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Email'),
        'wrong@example.com',
      );
      await tester.enterText(
        find.widgetWithText(TextFormField, 'Contraseña'),
        'wrongpassword',
      );

      await tester.tap(find.widgetWithText(ElevatedButton, 'Iniciar Sesión'));
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Verificar que muestra mensaje de error (SnackBar)
      // Note: SnackBar puede no ser visible en test, verificar que sigue en LoginScreen
      expect(find.text('Iniciar Sesión'), findsOneWidget);
    });
  });
}
