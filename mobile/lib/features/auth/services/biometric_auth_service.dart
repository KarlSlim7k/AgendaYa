import 'package:flutter/foundation.dart';
import 'package:local_auth/local_auth.dart';

class BiometricAuthService {
  final LocalAuthentication _localAuth = LocalAuthentication();

  Future<bool> isBiometricAvailable() async {
    if (kIsWeb) {
      return false;
    }

    final canCheck = await _localAuth.canCheckBiometrics;
    final deviceSupported = await _localAuth.isDeviceSupported();

    return canCheck || deviceSupported;
  }

  Future<bool> authenticate({
    String reason = 'Verifica tu identidad para continuar',
  }) async {
    if (kIsWeb) {
      return false;
    }

    final available = await isBiometricAvailable();
    if (!available) {
      return false;
    }

    return _localAuth.authenticate(
      localizedReason: reason,
      options: const AuthenticationOptions(
        biometricOnly: false,
        stickyAuth: true,
        useErrorDialogs: true,
      ),
    );
  }
}
