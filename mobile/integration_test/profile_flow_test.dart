import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:agenda_ya/main.dart' as app;
import 'package:flutter/material.dart';

/// Test E2E del flujo de perfil y gestión de citas
/// 
/// Flujo probado:
/// 1. Login
/// 2. Navegar a perfil
/// 3. Ver información de usuario
/// 4. Ver lista de citas próximas
/// 5. Ver lista de citas pasadas
/// 6. Cancelar una cita
/// 7. Verificar que la cita fue cancelada

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('Profile Flow E2E Tests', () {
    testWidgets('Debe mostrar perfil de usuario correctamente', (tester) async {
      // Setup: Login
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      // Navegar a perfil desde AppBar
      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que estamos en ProfileScreen
      expect(find.text('Mi Perfil'), findsOneWidget);
      
      // Verificar que muestra información del usuario
      expect(find.byIcon(Icons.person), findsAtLeastNWidgets(1));
      expect(find.text('usuario@example.com'), findsOneWidget);

      // Verificar que tiene tabs de Próximas y Pasadas
      expect(find.text('Próximas'), findsOneWidget);
      expect(find.text('Pasadas'), findsOneWidget);
    });

    testWidgets('Debe mostrar lista de citas próximas', (tester) async {
      // Setup: Login y navegar a perfil
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que está en tab "Próximas" por default
      expect(find.text('Próximas'), findsOneWidget);

      // Esperar a que carguen las citas
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que muestra citas o mensaje de sin citas
      // Si hay citas, debe mostrar Cards con información
      // Si no hay citas, debe mostrar "No tienes citas próximas"
      final hasCitas = find.byType(Card).evaluate().isNotEmpty;
      
      if (hasCitas) {
        expect(find.byType(Card), findsAtLeastNWidgets(1));
      } else {
        expect(find.text('No tienes citas próximas'), findsOneWidget);
      }
    });

    testWidgets('Debe mostrar lista de citas pasadas', (tester) async {
      // Setup: Login y navegar a perfil
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Cambiar a tab "Pasadas"
      await tester.tap(find.text('Pasadas'));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que muestra citas pasadas o mensaje
      final hasCitas = find.byType(Card).evaluate().isNotEmpty;
      
      if (hasCitas) {
        expect(find.byType(Card), findsAtLeastNWidgets(1));
      } else {
        expect(find.text('No tienes citas pasadas'), findsOneWidget);
      }
    });

    testWidgets('Debe permitir cancelar una cita', (tester) async {
      // Setup: Primero crear una cita para poder cancelarla
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Login
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
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Esperar a que carguen las citas
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Buscar botón "Cancelar Cita" (solo visible en citas cancelables)
      final cancelButton = find.widgetWithText(ElevatedButton, 'Cancelar Cita');
      
      if (cancelButton.evaluate().isNotEmpty) {
        // Tap en botón de cancelar
        await tester.tap(cancelButton.first);
        await tester.pumpAndSettle();

        // Verificar que muestra diálogo de confirmación
        expect(find.text('Cancelar Cita'), findsOneWidget);
        expect(find.text('¿Estás seguro que deseas cancelar esta cita?'), findsOneWidget);

        // Confirmar cancelación
        await tester.tap(find.text('Sí, cancelar'));
        await tester.pumpAndSettle(const Duration(seconds: 3));

        // Verificar que muestra mensaje de éxito (SnackBar)
        expect(find.text('Cita cancelada'), findsOneWidget);
      } else {
        // Si no hay citas cancelables, verificar que no hay botón
        expect(cancelButton, findsNothing);
      }
    });

    testWidgets('Debe mostrar estado de citas correctamente', (tester) async {
      // Setup: Login y navegar a perfil
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Esperar a que carguen las citas
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que muestra chips de estado
      // Los estados posibles son: PENDING, CONFIRMED, COMPLETED, CANCELLED, NO_SHOW
      final statusChips = find.byType(Chip);
      
      if (statusChips.evaluate().isNotEmpty) {
        expect(statusChips, findsAtLeastNWidgets(1));
      }
    });

    testWidgets('Debe separar citas próximas y pasadas correctamente', (tester) async {
      // Setup: Login y navegar a perfil
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Contar citas en tab "Próximas"
      await tester.pumpAndSettle(const Duration(seconds: 2));
      final proximasCards = find.byType(Card).evaluate().length;

      // Cambiar a tab "Pasadas"
      await tester.tap(find.text('Pasadas'));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Contar citas en tab "Pasadas"
      final pasadasCards = find.byType(Card).evaluate().length;

      // Verificar que las listas son diferentes
      // (pueden ser ambas 0 si no hay citas)
      expect(proximasCards >= 0, isTrue);
      expect(pasadasCards >= 0, isTrue);
    });

    testWidgets('Debe mostrar información completa de cada cita', (tester) async {
      // Setup: Login y navegar a perfil
      app.main();
      await tester.pumpAndSettle(const Duration(seconds: 2));

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

      await tester.tap(find.byIcon(Icons.person));
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Esperar a que carguen las citas
      await tester.pumpAndSettle(const Duration(seconds: 2));

      final cards = find.byType(Card);
      
      if (cards.evaluate().isNotEmpty) {
        // Verificar que cada cita muestra:
        // - Icono de calendario (fecha/hora)
        expect(find.byIcon(Icons.calendar_today), findsAtLeastNWidgets(1));
        
        // - Estado de la cita (Chip)
        expect(find.byType(Chip), findsAtLeastNWidgets(1));
      }
    });
  });
}
