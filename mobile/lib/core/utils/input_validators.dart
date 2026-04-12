class InputValidators {
  static final RegExp _emailRegex = RegExp(
    r'^[^@\s]+@[^@\s]+\.[^@\s]+$',
  );

  // Acepta +52XXXXXXXXXX o +52 XX XXXX XXXX
  static final RegExp _mexPhoneRegex = RegExp(
    r'^\+52(?:\s?\d{2}\s?\d{4}\s?\d{4}|\d{10})$',
  );

  static final RegExp _passwordUppercaseRegex = RegExp(r'[A-Z]');
  static final RegExp _passwordLowercaseRegex = RegExp(r'[a-z]');
  static final RegExp _passwordDigitRegex = RegExp(r'\d');
  static final RegExp _passwordSpecialRegex = RegExp(r'[^A-Za-z0-9]');

  static String? requiredField(String? value, {required String label}) {
    if (value == null || value.trim().isEmpty) {
      return 'Ingresa $label';
    }

    return null;
  }

  static String? email(String? value) {
    final required = requiredField(value, label: 'tu email');
    if (required != null) {
      return required;
    }

    if (!_emailRegex.hasMatch(value!.trim())) {
      return 'Email inválido';
    }

    return null;
  }

  static String? mexicanPhone(String? value, {bool required = false}) {
    final input = value?.trim() ?? '';

    if (input.isEmpty && !required) {
      return null;
    }

    if (input.isEmpty && required) {
      return 'Ingresa tu teléfono';
    }

    if (!_mexPhoneRegex.hasMatch(input)) {
      return 'Formato inválido. Usa +52XXXXXXXXXX';
    }

    return null;
  }

  static String normalizeMexicanPhone(String value) {
    final digits = value.replaceAll(RegExp(r'\D'), '');
    if (digits.startsWith('52') && digits.length >= 12) {
      return '+${digits.substring(0, 12)}';
    }

    if (digits.length == 10) {
      return '+52$digits';
    }

    return value.trim();
  }

  static String? strongPassword(String? value) {
    final password = value ?? '';
    if (password.isEmpty) {
      return 'Ingresa tu contraseña';
    }

    if (password.length < 8) {
      return 'Mínimo 8 caracteres';
    }

    if (!_passwordUppercaseRegex.hasMatch(password)) {
      return 'Debe incluir al menos una mayúscula';
    }

    if (!_passwordLowercaseRegex.hasMatch(password)) {
      return 'Debe incluir al menos una minúscula';
    }

    if (!_passwordDigitRegex.hasMatch(password)) {
      return 'Debe incluir al menos un número';
    }

    if (!_passwordSpecialRegex.hasMatch(password)) {
      return 'Debe incluir al menos un símbolo';
    }

    return null;
  }
}
