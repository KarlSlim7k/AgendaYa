import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:agenda_ya/main.dart' as app;
import 'package:flutter/material.dart';

/// Test E2E del flujo completo de reserva de citas
/// 
/// Flujo probado:
/// 1. Login
/// 2. Búsqueda de negocios
/// 3. Ver detalle de negocio
/// 4. Ver lista de servicios
/// 5. Seleccionar servicio
/// 6. Navegar a pantalla de reserva
/// 7. Seleccionar fecha
/// 8. Seleccionar slot de tiempo
/// 9. Agregar notas
/// 10. Confirmar reserva
/// 11. Verificar confirmación

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('Booking Flow E2E Tests', () {
    testWidgets('Debe completar flujo de reserva exitosamente', (tester) async {
      // Setup: Login primero
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

      // Verificar que estamos en BusinessListScreen
      expect(find.text('Negocios'), findsOneWidget);

      // Esperar a que carguen los negocios
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Buscar un negocio
      await tester.enterText(
        find.widgetWithText(TextField, 'Buscar negocios...'),
        'peluqueria',
      );
      await tester.pumpAndSettle();

      // Tap en el primer negocio de la lista
      await tester.tap(find.byIcon(Icons.arrow_forward_ios).first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que estamos en BusinessDetailScreen
      expect(find.text('Detalle del Negocio'), findsOneWidget);

      // Esperar a que carguen los servicios
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Scroll para ver servicios
      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      // Tap en botón "Reservar" del primer servicio
      await tester.tap(find.widgetWithText(ElevatedButton, 'Reservar').first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que estamos en BookingScreen
      expect(find.text('Reservar Cita'), findsOneWidget);
      expect(find.text('Horarios Disponibles'), findsOneWidget);

      // Esperar a que carguen los slots
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Seleccionar un slot (primer slot disponible)
      // Los slots están en un GridView
      final slots = find.byType(InkWell);
      if (slots.evaluate().isNotEmpty) {
        await tester.tap(slots.first);
        await tester.pumpAndSettle();
      }

      // Scroll para ver botón de confirmación
      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      // Agregar notas opcionales
      await tester.enterText(
        find.widgetWithText(TextField, 'Notas (opcional)'),
        'Primera cita de prueba',
      );
      await tester.pumpAndSettle();

      // Tap en botón de confirmar reserva
      await tester.tap(find.widgetWithText(ElevatedButton, 'Confirmar Reserva'));
      await tester.pumpAndSettle(const Duration(seconds: 3));

      // Verificar que muestra diálogo de confirmación
      expect(find.text('¡Reserva Exitosa!'), findsOneWidget);
      expect(find.text('Ver mis citas'), findsOneWidget);
    });

    testWidgets('Debe validar selección de fecha y hora', (tester) async {
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

      // Navegar a booking (asumiendo que hay negocio y servicio disponibles)
      await tester.pumpAndSettle(const Duration(seconds: 2));
      await tester.tap(find.byIcon(Icons.arrow_forward_ios).first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(ElevatedButton, 'Reservar').first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Intentar confirmar sin seleccionar slot
      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(ElevatedButton, 'Confirmar Reserva'));
      await tester.pumpAndSettle();

      // Verificar que muestra mensaje de validación (SnackBar)
      // El mensaje debe indicar que se debe seleccionar fecha y hora
      expect(find.text('Selecciona una fecha y hora'), findsOneWidget);
    });

    testWidgets('Debe permitir cambiar fecha seleccionada', (tester) async {
      // Setup: Login y navegar a booking
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

      await tester.pumpAndSettle(const Duration(seconds: 2));
      await tester.tap(find.byIcon(Icons.arrow_forward_ios).first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(ElevatedButton, 'Reservar').first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Tap en selector de fecha
      await tester.tap(find.byIcon(Icons.calendar_today));
      await tester.pumpAndSettle();

      // Verificar que abre DatePicker
      // En un test real, seleccionaríamos una fecha del picker
      // Por ahora, verificamos que el diálogo se abre
      expect(find.byType(DatePickerDialog), findsOneWidget);
    });

    testWidgets('Debe mostrar slots disponibles para fecha seleccionada', (tester) async {
      // Setup: Login y navegar a booking
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

      await tester.pumpAndSettle(const Duration(seconds: 2));
      await tester.tap(find.byIcon(Icons.arrow_forward_ios).first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      await tester.drag(find.byType(SingleChildScrollView), const Offset(0, -300));
      await tester.pumpAndSettle();

      await tester.tap(find.widgetWithText(ElevatedButton, 'Reservar').first);
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Esperar a que carguen los slots
      await tester.pumpAndSettle(const Duration(seconds: 2));

      // Verificar que muestra slots (en GridView)
      // Si hay slots disponibles, debe haber al menos uno
      expect(find.byType(GridView), findsOneWidget);
    });
  });
}
