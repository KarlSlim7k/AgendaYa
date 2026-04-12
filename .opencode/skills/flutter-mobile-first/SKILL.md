---
name: flutter-mobile-first
description: Patrones Flutter para desarrollo mobile-first: adaptive layouts, offline-first, secure storage, deep linking, y responsive design con Flutter Web/PWA.
---

## Qué hago

Guío la implementación de features Flutter priorizando la experiencia móvil (iOS/Android) con soporte adaptativo para web/desktop.

## Cuándo usarme

Usa este skill cuando:
- Se desarrollen features Flutter para la app móvil de AgendaYa
- Se necesite UI adaptativa (móvil primero, web después)
- Se implemente offline-first, almacenamiento seguro, o deep linking
- Se diseñe navegación o layouts responsivos

## Principios

### 1. Mobile-first UI

Siempre diseñar para 360px de ancho primero, luego escalar:

```dart
// Usar LayoutBuilder para breakpoints adaptativos
LayoutBuilder(
  builder: (context, constraints) {
    if (constraints.maxWidth >= 1024) {
      return _DesktopLayout();
    } else if (constraints.maxWidth >= 600) {
      return _TabletLayout();
    }
    return _MobileLayout();
  },
)
```

### 2. Adaptive widgets

```dart
// Preferir Adaptive widgets sobre platform checks
import 'package:flutter_adaptive_scaffold/flutter_adaptive_scaffold.dart';

// Navigation: BottomNavigationBar en móvil, NavigationRail en tablet, NavigationDrawer en desktop
AdaptiveScaffold(
  selectedIndex: selectedIndex,
  onSelectedIndexChange: (index) => setState(() => selectedIndex = index),
  destinations: const [
    AdaptiveDestination(icon: Icons.home, title: 'Inicio'),
    AdaptiveDestination(icon: Icons.search, title: 'Buscar'),
    AdaptiveDestination(icon: Icons.calendar_today, title: 'Citas'),
    AdaptiveDestination(icon: Icons.person, title: 'Perfil'),
  ],
  body: _buildPage(selectedIndex),
)
```

### 3. Offline-first con Hive/Isar

```dart
// Capa de datos offline-first
abstract class OfflineFirstRepository<T> {
  final Box<T> _localBox;
  final ApiClient _api;

  Future<List<T>> getAll() async {
    final local = _localBox.values.toList();
    if (local.isNotEmpty) return local;

    final remote = await _api.fetchAll<T>();
    await _localBox.addAll(remote);
    return remote;
  }

  Future<void> sync() async {
    try {
      final remote = await _api.fetchAll<T>();
      await _localBox.clear();
      await _localBox.addAll(remote);
    } catch (e) {
      // Mantener datos locales, reintentar después
      _scheduleRetry();
    }
  }

  void _scheduleRetry() {
    // Exponential backoff: 1s, 2s, 4s, 8s... max 5min
  }
}
```

### 4. Almacenamiento seguro de tokens

```dart
// Usar flutter_secure_storage para tokens
class SecureTokenStorage {
  static const _accessTokenKey = 'auth_access_token';
  static const _refreshTokenKey = 'auth_refresh_token';

  final FlutterSecureStorage _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );

  Future<String?> getAccessToken() => _storage.read(key: _accessTokenKey);
  Future<String?> getRefreshToken() => _storage.read(key: _refreshTokenKey);

  Future<void> saveTokens({required String access, required String refresh}) async {
    await _storage.write(key: _accessTokenKey, value: access);
    await _storage.write(key: _refreshTokenKey, value: refresh);
  }

  Future<void> clear() async {
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _refreshTokenKey);
  }
}
```

### 5. Deep linking

```dart
// Configuración en Android: android/app/src/main/AndroidManifest.xml
// <intent-filter>
//   <action android:name="android.intent.action.VIEW"/>
//   <category android:name="android.intent.category.DEFAULT"/>
//   <category android:name="android.intent.category.BROWSABLE"/>
//   <data android:scheme="agendaya" android:host="booking"/>
// </intent-filter>

// Configuración en iOS: ios/Runner/Info.plist
// <key>CFBundleURLTypes</key>
// <array>
//   <dict><key>CFBundleURLSchemes</key><array><string>agendaya</string></array></dict>
// </array>

// GoRouter con deep links
final router = GoRouter(
  routes: [
    GoRoute(path: '/', builder: (_, __) => const HomeScreen()),
    GoRoute(
      path: '/booking/:id',
      builder: (_, state) => BookingDetailScreen(
        bookingId: state.pathParameters['id']!,
      ),
    ),
    GoRoute(
      path: '/business/:id',
      builder: (_, state) => BusinessDetailScreen(
        businessId: state.pathParameters['id']!,
      ),
    ),
  ],
);
```

### 6. Navegación móvil optimizada

```dart
// Patrones de navegación para móvil
class AppNavigator {
  // Push con animaciones nativas
  static Future<T?> push<T>(BuildContext context, Widget page) {
    return Navigator.of(context).push<T>(
      PageRouteBuilder(
        pageBuilder: (_, animation, __) => page,
        transitionsBuilder: (_, animation, __, child) =>
            FadeTransition(opacity: animation, child: child),
        transitionDuration: const Duration(milliseconds: 200),
      ),
    );
  }

  // Replace para flujos de auth (no volver atrás)
  static void replaceAll(BuildContext context, Widget page) {
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => page),
      (route) => false,
    );
  }
}
```

### 7. Responsive grid para negocios

```dart
// Grid adaptativo para listado de negocios
GridView.builder(
  padding: const EdgeInsets.all(16),
  gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
    crossAxisCount: _calculateColumns(context),
    childAspectRatio: 0.85,
    crossAxisSpacing: 16,
    mainAxisSpacing: 16,
  ),
  itemCount: businesses.length,
  itemBuilder: (context, index) => BusinessCard(business: businesses[index]),
);

int _calculateColumns(BuildContext context) {
  final width = MediaQuery.of(context).size.width;
  if (width >= 1200) return 4;
  if (width >= 900) return 3;
  if (width >= 600) return 2;
  return 1;
}
```

### 8. Pull-to-refresh + paginación infinita

```dart
class PaginatedBusinessList extends StatefulWidget {
  const PaginatedBusinessList({super.key});
  @override
  State<PaginatedBusinessList> createState() => _State();
}

class _State extends State<PaginatedBusinessList> {
  final _scrollController = ScrollController();
  List<Business> _items = [];
  bool _hasMore = true;
  bool _isLoading = false;
  int _page = 1;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    _loadMore();
  }

  void _onScroll() {
    if (_scrollController.position.pixels >=
        _scrollController.position.maxScrollExtent * 0.8) {
      _loadMore();
    }
  }

  Future<void> _loadMore() async {
    if (_isLoading || !_hasMore) return;
    setState(() => _isLoading = true);
    try {
      final newItems = await api.getBusinesses(page: _page);
      setState(() {
        _items.addAll(newItems);
        _page++;
        _hasMore = newItems.length >= 20;
      });
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _refresh() async {
    setState(() { _page = 1; _items = []; _hasMore = true; });
    await _loadMore();
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.builder(
        controller: _scrollController,
        itemCount: _items.length + (_hasMore ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == _items.length) return const LoadingIndicator();
          return BusinessCard(business: _items[index]);
        },
      ),
    );
  }
}
```

### 9. Connectivity-aware UX

```dart
// Monitorear conectividad para UX adaptativa
class ConnectivityAware extends StatelessWidget {
  final Widget online;
  final Widget offline;

  const ConnectivityAware({super.key, required this.online, required this.offline});

  @override
  Widget build(BuildContext context) {
    return StreamBuilder<ConnectivityResult>(
      stream: Connectivity().onConnectivityChanged,
      builder: (context, snapshot) {
        final isOnline = snapshot.data != ConnectivityResult.none;
        return Stack(
          children: [
            isOnline ? online : offline,
            if (!isOnline) const OfflineBanner(),
          ],
        );
      },
    );
  }
}

// Banner offline persistente
class OfflineBanner extends StatelessWidget {
  const OfflineBanner({super.key});
  @override
  Widget build(BuildContext context) {
    return Positioned(
      top: 0,
      left: 0,
      right: 0,
      child: Container(
        padding: const EdgeInsets.all(8),
        color: Colors.orange.shade700,
        child: const Text(
          'Sin conexión — mostrando datos guardados',
          style: TextStyle(color: Colors.white, fontSize: 12),
          textAlign: TextAlign.center,
        ),
      ),
    );
  }
}
```

## Estructura de directorios recomendada

```
lib/
├── core/
│   ├── theme/
│   │   ├── app_theme.dart
│   │   └── adaptive_theme.dart
│   ├── network/
│   │   ├── api_client.dart
│   │   ├── connectivity_aware.dart
│   │   └── token_storage.dart
│   └── navigation/
│       ├── app_router.dart
│       └── deep_links.dart
├── features/
│   ├── auth/
│   ├── businesses/
│   ├── booking/
│   ├── availability/
│   └── profile/
├── platform/
│   ├── mobile/
│   └── web/
└── widgets/
    ├── adaptive_scaffold.dart
    ├── offline_banner.dart
    ├── loading_states.dart
    └── empty_states.dart
```

## Performance budget

- Cold start móvil: < 2s
- Time to Interactive móvil: < 3s
- First Contentful Paint web: < 1.5s
- App bundle: < 30MB
- Animations: 60fps (16ms per frame)

## Reglas obligatorias

1. NUNCA usar `Platform.isAndroid`/`Platform.isIOS` para UI — usar `LayoutBuilder` o `AdaptiveScaffold`.
2. SIEMPRE manejar estados: loading, empty, error, success para cada feature.
3. SIEMPRE usar `FlutterSecureStorage` para tokens — nunca `SharedPreferences`.
4. SIEMPRE implementar offline-first para features de lectura (negocios, citas).
5. NUNCA hardcodear dimensiones — usar `MediaQuery` o `LayoutBuilder`.
6. SIEMPRE soportar pull-to-refresh en listas.
7. SIEMPRE usar deep linking para navegación desde notificaciones.