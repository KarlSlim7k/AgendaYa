# Flutter Mobile App - README

## Instalación y Ejecución

### Requisitos
- Flutter SDK 3.5.0+
- Dart 3.5.0+
- Android Studio / VS Code con extensiones de Flutter
- Emulador Android/iOS o dispositivo físico

### Setup Inicial

```bash
# 1. Instalar dependencias
cd mobile
flutter pub get

# 2. Verificar instalación de Flutter
flutter doctor

# 3. Listar dispositivos disponibles
flutter devices

# 4. Ejecutar en emulador/dispositivo
flutter run

# 5. Ejecutar en modo debug con hot reload
flutter run -d <device_id>
```

### Configuración de API

Editar `lib/core/constants/api_constants.dart`:

```dart
static const String baseUrl = 'http://127.0.0.1:8000/api/v1'; // Desarrollo
// static const String baseUrl = 'https://api.example.com/api/v1'; // Producción
```

Para Android emulator usar `10.0.2.2`:
```dart
static const String baseUrl = 'http://10.0.2.2:8000/api/v1';
```

## Arquitectura

```
lib/
├── core/               # Configuración base
│   ├── constants/      # URLs API, constantes app, rutas
│   └── routes/         # RouteGenerator con navegación
├── data/               # Capa de datos
│   ├── models/         # User, Business, Service, Appointment
│   └── providers/      # ApiClient, AuthService, BusinessService, AppointmentService
├── features/           # Módulos por característica
│   ├── auth/
│   │   ├── providers/  # AuthProvider (state)
│   │   └── screens/    # LoginScreen, RegisterScreen
│   ├── business/
│   │   ├── providers/  # BusinessProvider (state)
│   │   └── screens/    # BusinessListScreen, BusinessDetailScreen
│   ├── booking/
│   │   ├── providers/  # AppointmentProvider (state)
│   │   └── screens/    # BookingScreen
│   └── profile/
│       └── screens/    # ProfileScreen
└── shared/             # Widgets compartidos
    └── screens/        # SplashScreen

main.dart               # Entry point con MultiProvider
```

## Flujo de Usuario

1. **SplashScreen** → Verifica token → Navega a Login o Home
2. **LoginScreen** / **RegisterScreen** → Autenticación → Home
3. **BusinessListScreen** → Búsqueda/filtros → Selecciona negocio
4. **BusinessDetailScreen** → Ve servicios → Selecciona servicio
5. **BookingScreen** → Selecciona fecha/hora → Confirma cita
6. **ProfileScreen** → Ve mis citas → Cancela citas futuras

## State Management

Provider pattern con ChangeNotifier:

- **AuthProvider**: Sesión de usuario, login/logout
- **BusinessProvider**: Lista de negocios, búsqueda, paginación
- **AppointmentProvider**: Mis citas, slots disponibles, crear/cancelar

## Endpoints Integrados

### Auth
- `POST /auth/register` - Registro de usuario
- `POST /auth/login` - Login con email/password
- `POST /auth/logout` - Cerrar sesión
- `GET /user/profile` - Perfil del usuario

### Business
- `GET /businesses` - Lista con filtros (categoria, search, location)
- `GET /businesses/{id}` - Detalle con sucursales
- `GET /businesses/{id}/services` - Servicios disponibles

### Appointments
- `GET /appointments` - Mis citas
- `POST /appointments` - Crear cita
- `PATCH /appointments/{id}/cancel` - Cancelar cita
- `GET /availability/slots` - Slots disponibles

## Tokens de Autenticación

Almacenamiento seguro con `flutter_secure_storage`:

```dart
// Guardar token después de login
await storage.write(key: 'auth_token', value: token);

// Leer token (automático en ApiClient)
String? token = await storage.read(key: 'auth_token');

// Eliminar token en logout
await storage.delete(key: 'auth_token');
```

## Build para Producción

```bash
# Android APK
flutter build apk --release

# Android App Bundle (para Google Play)
flutter build appbundle --release

# iOS (requiere Mac)
flutter build ios --release
```

## Testing

```bash
# Ejecutar tests
flutter test

# Cobertura
flutter test --coverage
```

## Troubleshooting

### Error: Connection refused
- Backend no corriendo: `php artisan serve`
- URL incorrecta en api_constants.dart
- Emulator Android: usar `10.0.2.2` en vez de `127.0.0.1`

### Error: Token invalid/expired
- Verificar que Sanctum esté configurado en backend
- Token puede expirar (default 24h en backend)
- Re-login para obtener nuevo token

### Hot reload no funciona
- Reiniciar: `r` en terminal
- Hot restart: `R` en terminal
- Detener y volver a ejecutar `flutter run`
