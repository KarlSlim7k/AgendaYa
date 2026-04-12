import 'package:flutter/material.dart';

import 'package:agenda_ya/core/services/local_notification_service.dart';
import 'package:agenda_ya/data/models/user.dart';
import 'package:agenda_ya/data/providers/auth_service.dart';
import 'package:agenda_ya/features/auth/services/auth_preferences_service.dart';
import 'package:agenda_ya/features/auth/services/biometric_auth_service.dart';

class AuthProvider with ChangeNotifier {
  final AuthService _authService = AuthService();
  final AuthPreferencesService _authPreferencesService = AuthPreferencesService();
  final BiometricAuthService _biometricAuthService = BiometricAuthService();
  final LocalNotificationService _notificationService =
      LocalNotificationService.instance;

  User? _user;
  bool _isAuthenticated = false;
  bool _isLoading = false;
  String? _errorMessage;

  bool _rememberSession = true;
  bool _biometricEnabled = false;
  bool _biometricAvailable = false;
  bool _securityInitialized = false;
  Future<void>? _securityInitFuture;

  User? get user => _user;
  bool get isAuthenticated => _isAuthenticated;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  bool get rememberSession => _rememberSession;
  bool get biometricEnabled => _biometricEnabled;
  bool get biometricAvailable => _biometricAvailable;
  bool get canUseBiometricLogin => _biometricEnabled && _biometricAvailable;

  Future<void> initializeSecurityState() {
    _securityInitFuture ??= _initializeSecurityStateInternal();
    return _securityInitFuture!;
  }

  Future<void> _initializeSecurityStateInternal() async {
    _rememberSession = await _authPreferencesService.getRememberSession();
    _biometricEnabled = await _authPreferencesService.getBiometricEnabled();
    _biometricAvailable = await _biometricAuthService.isBiometricAvailable();

    if (!_biometricAvailable && _biometricEnabled) {
      _biometricEnabled = false;
      await _authPreferencesService.setBiometricEnabled(false);
    }

    _securityInitialized = true;
    notifyListeners();
  }

  Future<void> checkAuthStatus() async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      if (!_securityInitialized) {
        await initializeSecurityState();
      }

      _isAuthenticated = await _authService.isAuthenticated();
      if (_isAuthenticated) {
        _user = await _authService.getProfile();

        if (!(_user?.isEmailVerified ?? false)) {
          await _forceUnverifiedLogout();
        }
      } else {
        await _notificationService.cancelSessionReminder();
      }
    } catch (e) {
      _isAuthenticated = false;
      _user = null;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    String? telefono,
    bool? rememberSession,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final shouldRemember = rememberSession ?? _rememberSession;

      final result = await _authService.register(
        name: name,
        email: email,
        password: password,
        passwordConfirmation: passwordConfirmation,
        telefono: telefono,
        rememberSession: shouldRemember,
      );

      _user = result['user'] as User;

      if (!(_user?.isEmailVerified ?? false)) {
        await _forceUnverifiedLogout();
        _isLoading = false;
        notifyListeners();
        return false;
      }

      _rememberSession = shouldRemember;
      await _authPreferencesService.setRememberSession(_rememberSession);

      _isAuthenticated = true;
      if (_rememberSession) {
        await _notificationService.scheduleSessionReminder();
      } else {
        await _notificationService.cancelSessionReminder();
      }

      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<bool> login({
    required String email,
    required String password,
    bool? rememberSession,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final shouldRemember = rememberSession ?? _rememberSession;

      final result = await _authService.login(
        email: email,
        password: password,
        rememberSession: shouldRemember,
      );

      _user = result['user'] as User;

      if (!(_user?.isEmailVerified ?? false)) {
        await _forceUnverifiedLogout();
        _isLoading = false;
        notifyListeners();
        return false;
      }

      _rememberSession = shouldRemember;
      await _authPreferencesService.setRememberSession(_rememberSession);

      _isAuthenticated = true;
      if (_rememberSession) {
        await _notificationService.scheduleSessionReminder();
      } else {
        await _notificationService.cancelSessionReminder();
      }
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> setRememberSession(bool value) async {
    _rememberSession = value;
    await _authPreferencesService.setRememberSession(value);
    await _authService.updateSessionPersistence(value);

    if (!value) {
      await _notificationService.cancelSessionReminder();
    } else if (_isAuthenticated) {
      await _notificationService.scheduleSessionReminder();
    }

    notifyListeners();
  }

  Future<bool> toggleBiometric(bool enable) async {
    if (!_securityInitialized) {
      await initializeSecurityState();
    }

    _errorMessage = null;

    final available = await _biometricAuthService.isBiometricAvailable();
    _biometricAvailable = available;

    if (enable && !available) {
      _errorMessage = 'Tu dispositivo no soporta autenticación biométrica.';
      notifyListeners();
      return false;
    }

    if (enable) {
      final authenticated = await _biometricAuthService.authenticate(
        reason: 'Activa el acceso biométrico en AgendaYa',
      );

      if (!authenticated) {
        _errorMessage = 'No fue posible validar tu biometría.';
        notifyListeners();
        return false;
      }
    }

    _biometricEnabled = enable;
    await _authPreferencesService.setBiometricEnabled(enable);
    notifyListeners();
    return true;
  }

  Future<bool> loginWithBiometrics() async {
    if (!_securityInitialized) {
      await initializeSecurityState();
    }

    _errorMessage = null;
    notifyListeners();

    if (!canUseBiometricLogin) {
      _errorMessage =
          'La autenticación biométrica no está disponible o no está activada.';
      notifyListeners();
      return false;
    }

    final hasSession = await _authService.isAuthenticated();
    if (!hasSession) {
      _errorMessage =
          'No hay sesión persistida para acceso biométrico. Inicia sesión primero.';
      notifyListeners();
      return false;
    }

    final authenticated = await _biometricAuthService.authenticate(
      reason: 'Verifica tu identidad para ingresar a AgendaYa',
    );

    if (!authenticated) {
      _errorMessage = 'No se pudo autenticar con biometría.';
      notifyListeners();
      return false;
    }

    await checkAuthStatus();
    return _isAuthenticated;
  }

  Future<bool> requireBiometricUnlockIfNeeded() async {
    if (!canUseBiometricLogin || !_isAuthenticated) {
      return true;
    }

    final authenticated = await _biometricAuthService.authenticate(
      reason: 'Desbloquea tu sesión de AgendaYa',
    );

    if (!authenticated) {
      _errorMessage = 'No se pudo validar tu biometría.';
      notifyListeners();
      return false;
    }

    return true;
  }

  Future<bool> updateProfile({
    required String name,
    String? telefono,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final updatedUser = await _authService.updateProfile(
        name: name,
        telefono: telefono,
      );

      _user = updatedUser;
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _errorMessage = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await _authService.logout();
    } catch (e) {
      await _authService.clearLocalSession();
    } finally {
      await _notificationService.cancelSessionReminder();
      _user = null;
      _isAuthenticated = false;
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> _forceUnverifiedLogout() async {
    await _authService.clearLocalSession();
    await _notificationService.cancelSessionReminder();
    _user = null;
    _isAuthenticated = false;
    _errorMessage =
        'Debes verificar tu correo electrónico antes de continuar.';
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
