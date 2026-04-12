---
name: mx-validation
description: Validaciones específicas para México: teléfono +52, código postal por estado, CURP, RFC, formato de fecha y reglas de negocio locales para AgendaYa.
---

## Qué hago

Proveo reglas de validación específicas para México en backend (Laravel) y frontend (Flutter), asegurando que los datos del usuario final cumplan con formatos y reglas locales.

## Cuándo usarme

Usa este skill cuando:
- Se validen teléfonos mexicanos (registro, perfil)
- Se validen códigos postales con relación estado-municipio
- Se implemente CURP o RFC
- Se muestren fechas en formato mexicano
- Se necesiten reglas de negocio específicas para México

## Validación de teléfono mexicano

### Reglas

- Lada internacional: +52
- Lada nacional: 52
- Celulares: 10 dígitos después de +52 (lada + número)
- Formatos aceptados: +52 55 1234 5678, 5512345678, +525512345678
- No aceptar: números de 8 dígitos (fijos), números con ladas inválidas

### Backend (Laravel)

```php
class MexicoPhoneRule implements ValidationRule
{
    private const VALID_LADAS = [
        '55',  '33',  '81',
        '222', '229', '241', '243', '244', '246', '247', '248', '249',
        '281', '311', '312', '313', '314', '321', '322', '323', '324', '325',
        '331', '332', '333', '341', '342', '343', '344', '345', '346', '347',
        '348', '349', '351', '352', '353', '354', '355', '356', '357', '358',
        '361', '362', '363', '364', '365', '366', '367', '368', '369', '371',
        '372', '373', '374', '375', '376', '377', '378', '381', '382', '383',
        '384', '385', '386', '387', '388', '391', '392', '393', '394', '395',
        '411', '412', '413', '414', '415', '417', '418', '419', '421', '422',
        '423', '424', '425', '426', '427', '428', '429', '431', '432', '433',
        '434', '435', '436', '437', '438', '439', '441', '442', '443', '444',
        '445', '446', '447', '448', '449', '451', '452', '453', '454', '455',
        '456', '457', '458', '459', '461', '462', '463', '464', '465', '466',
        '467', '468', '469', '471', '472', '473', '474', '475', '476', '477',
        '478', '479', '481', '482', '483', '484', '485', '486', '487', '488',
        '489', '491', '492', '493', '494', '495', '496', '497', '498', '499',
        '551', '552', '553', '554', '555', '556', '557', '558', '559',
        '561', '562', '563', '564', '565', '566', '567', '568', '569',
        '591', '592', '593', '594', '595', '596', '597', '598', '599',
        '612', '613', '614', '621', '622', '623', '624', '625', '626',
        '627', '628', '629', '631', '632', '633', '634', '635', '636',
        '637', '638', '639', '641', '642', '643', '644', '645', '646',
        '647', '648', '649', '651', '652', '653', '654', '655', '656',
        '657', '658', '659', '661', '662', '663', '664', '665', '666',
        '667', '668', '669', '671', '672', '673', '674', '675', '676',
        '677', '678', '679', '681', '682', '683', '684', '685', '686',
        '687', '688', '689', '691', '692', '693', '694', '695', '696',
        '697', '698', '699',
        '711', '712', '713', '714', '715', '716', '717', '718', '719',
        '721', '722', '723', '724', '725', '726', '727', '728', '729',
        '731', '732', '733', '734', '735', '736', '737', '738', '739',
        '741', '742', '743', '744', '745', '746', '747', '748', '749',
        '751', '752', '753', '754', '755', '756', '757', '758', '759',
        '761', '762', '763', '764', '765', '766', '767', '768', '769',
        '771', '772', '773', '774', '775', '776', '777',
        '781', '782', '783', '784', '785', '786', '787', '788', '789',
        '811', '812', '813', '814', '815', '816', '817', '818', '819',
        '821', '822', '823', '824', '825', '826', '827', '828', '829',
        '831', '832', '833', '834', '835', '836', '837', '838', '839',
        '841', '842', '843', '844', '845', '846', '847', '848', '849',
        '861', '862', '863', '864', '865', '866', '867', '868', '869',
        '871', '872', '873', '874', '875', '876', '877', '878', '879',
        '891', '892', '893', '894', '895', '896', '897', '898', '899',
        '911', '912', '913', '914', '915', '916', '917', '918', '919',
        '921', '922', '923', '924', '925', '926', '927', '928', '929',
        '931', '932', '933', '934', '935', '936', '937', '938', '939',
        '941', '942', '943', '944', '945', '946', '947', '948', '949',
        '951', '952', '953', '954', '955', '956', '957', '958', '959',
        '961', '962', '963', '964', '965', '966', '967', '968', '969',
        '971', '972', '973', '974', '975', '976', '977',
        '981', '982', '983', '984', '985', '986', '987',
        '991', '992', '993', '994', '995', '996', '997', '998', '999',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/[^0-9]/', '', $value);

        if (str_starts_with($digits, '52') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) !== 10) {
            $fail('El teléfono debe tener 10 dígitos.');
            return;
        }

        $lada = substr($digits, 0, 2);

        if ($lada === '55' || $lada === '33' || $lada === '81') {
            return;
        }

        $fullLada = substr($digits, 0, 3);
        if (!in_array($fullLada, self::VALID_LADAS) && !in_array($lada, self::VALID_LADAS)) {
            $fail('La lada telefónica no es válida para México.');
        }
    }
}
```

### Frontend (Flutter)

```dart
class MexicoPhoneValidator {
  static const _validTwoDigitLadas = ['55', '33', '81'];
  static const _validThreeDigitLadas = [
    '222', '229', '241', '243', '244', '246', '247', '248', '249',
    '281', '311', '312', '313', '314', '321', '322', '323', '324', '325',
    '442', '443', '444', '445', '446', '447', '448', '449',
    '614', '621', '622', '623', '624', '625', '626',
    '664', '665', '667', '668', '669',
    '686', '687',
    '722', '723', '724', '728',
    '771', '772', '773', '774', '775', '776', '777',
    '811', '812', '813', '814', '815', '816', '817', '818', '819',
    '821', '822', '823', '824', '826', '827', '828', '829',
    '831', '832', '833', '834', '835', '836',
    '844', '845',
    '861', '862', '864',
    '871', '872', '873', '874', '875', '876', '877', '878',
    '891', '892', '894', '898', '899',
    '921', '922', '923', '924',
    '932', '933', '934',
    '951', '953', '954', '955',
    '961', '962', '963', '964', '965', '966', '967',
    '971', '972', '973', '974',
    '981', '982', '983', '984',
    '991', '992', '993', '994', '995', '996', '997', '998', '999',
  ];

  static String? validate(String? value) {
    if (value == null || value.isEmpty) return 'El teléfono es obligatorio';

    final digits = value.replaceAll(RegExp(r'[^0-9]'), '');

    String number = digits;
    if (digits.startsWith('52') && digits.length == 12) {
      number = digits.substring(2);
    }

    if (number.length != 10) return 'El teléfono debe tener 10 dígitos';

    final lada2 = number.substring(0, 2);
    final lada3 = number.substring(0, 3);

    final isValidLada = _validTwoDigitLadas.contains(lada2) ||
        _validThreeDigitLadas.contains(lada3);

    if (!isValidLada) return 'La lada no es válida para México';

    return null;
  }

  static String format(String digits) {
    if (digits.length != 10) return digits;
    final lada = digits.substring(0, digits.substring(0, 2) == '55' ||
        digits.substring(0, 2) == '33' ||
        digits.substring(0, 2) == '81' ? 2 : 3);
    final rest = digits.substring(lada.length);
    return '+52 $lada ${rest.substring(0, 4)} ${rest.substring(4)}';
  }
}
```

### Input formatter para Flutter

```dart
class MexicoPhoneFormatter extends TextInputFormatter {
  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final digits = newValue.text.replaceAll(RegExp(r'[^0-9]'), '');

    String formatted;
    if (digits.length <= 2) {
      formatted = '+52 $digits';
    } else if (digits.length <= 6) {
      formatted = '+52 ${digits.substring(0, 2)} ${digits.substring(2)}';
    } else {
      formatted = '+52 ${digits.substring(0, 2)} ${digits.substring(2, 6)} ${digits.substring(6, min(digits.length, 10))}';
    }

    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}
```

## Código postal mexicano

### Backend (Laravel)

```php
class MexicoPostalCodeRule implements ValidationRule
{
    private const CP_RANGES = [
        'AGU' => [['01000', '01999']],  // Aguascalientes
        'BCN' => [['21000', '22999']],  // Baja California
        'BCS' => [['23000', '23999']],  // Baja California Sur
        'CAM' => [['24000', '24999']],  // Campeche
        'CHH' => [['31000', '33999']],  // Chihuahua
        'CHP' => [['29000', '30999']],  // Chiapas
        'CMX' => [['02000', '02999'], ['03000', '16999']], // CDMX
        'COA' => [['25000', '27999']],  // Coahuila
        'COL' => [['28000', '28999']],  // Colima
        'DUR' => [['34000', '35999']],  // Durango
        'GRO' => [['40000', '41999']],  // Guerrero
        'GTO' => [['36000', '38999']],  // Guanajuato
        'HID' => [['42000', '43999']],  // Hidalgo
        'JAL' => [['44000', '49999']],  // Jalisco
        'MEX' => [['50000', '57999']],  // México (Estado)
        'MIC' => [['58000', '61999']],  // Michoacán
        'MOR' => [['62000', '62999']],  // Morelos
        'NAY' => [['63000', '63999']],  // Nayarit
        'NLE' => [['64000', '67999']],  // Nuevo León
        'OAX' => [['68000', '71999']],  // Oaxaca
        'PUE' => [['72000', '75999']],  // Puebla
        'QRO' => [['76000', '76999']],  // Querétaro
        'ROO' => [['77000', '77999']],  // Quintana Roo
        'SIN' => [['80000', '82999']],  // Sinaloa
        'SLP' => [['78000', '79999']],  // San Luis Potosí
        'SON' => [['83000', '85999']],  // Sonora
        'TAB' => [['86000', '86999']],  // Tabasco
        'TAM' => [['87000', '89999']],  // Tamaulipas
        'TLA' => [['90000', '90999']],  // Tlaxcala
        'VER' => [['91000', '97999']],  // Veracruz
        'YUC' => [['97000', '97999']],  // Yucatán
        'ZAC' => [['98000', '99999']],  // Zacatecas
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cp = preg_replace('/[^0-9]/', '', (string) $value);

        if (strlen($cp) !== 5) {
            $fail('El código postal debe tener 5 dígitos.');
            return;
        }

        $cpInt = (int) $cp;
        $valid = false;

        foreach (self::CP_RANGES as $ranges) {
            foreach ($ranges as [$min, $max]) {
                if ($cpInt >= (int) $min && $cpInt <= (int) $max) {
                    $valid = true;
                    break 2;
                }
            }
        }

        if (!$valid) {
            $fail('El código postal no es válido para México.');
        }
    }

    public static function getStateByCP(string $cp): ?string
    {
        $cpInt = (int) preg_replace('/[^0-9]/', '', $cp);

        foreach (self::CP_RANGES as $state => $ranges) {
            foreach ($ranges as [$min, $max]) {
                if ($cpInt >= (int) $min && $cpInt <= (int) $max) {
                    return $state;
                }
            }
        }

        return null;
    }
}
```

### Frontend (Flutter)

```dart
class MexicoPostalCodeValidator {
  static const _cpRanges = <String, List<List<int>>>{
    'AGU': [[1000, 1999]],
    'BCN': [[21000, 22999]],
    'BCS': [[23000, 23999]],
    'CAM': [[24000, 24999]],
    'CHH': [[31000, 33999]],
    'CHP': [[29000, 30999]],
    'CMX': [[2000, 2999], [3000, 16999]],
    'COA': [[25000, 27999]],
    'COL': [[28000, 28999]],
    'DUR': [[34000, 35999]],
    'GRO': [[40000, 41999]],
    'GTO': [[36000, 38999]],
    'HID': [[42000, 43999]],
    'JAL': [[44000, 49999]],
    'MEX': [[50000, 57999]],
    'MIC': [[58000, 61999]],
    'MOR': [[62000, 62999]],
    'NAY': [[63000, 63999]],
    'NLE': [[64000, 67999]],
    'OAX': [[68000, 71999]],
    'PUE': [[72000, 75999]],
    'QRO': [[76000, 76999]],
    'ROO': [[77000, 77999]],
    'SIN': [[80000, 82999]],
    'SLP': [[78000, 79999]],
    'SON': [[83000, 85999]],
    'TAB': [[86000, 86999]],
    'TAM': [[87000, 89999]],
    'TLA': [[90000, 90999]],
    'VER': [[91000, 97999]],
    'YUC': [[97000, 97999]],
    'ZAC': [[98000, 99999]],
  };

  static String? validate(String? value) {
    if (value == null || value.isEmpty) return 'El código postal es obligatorio';

    final digits = value.replaceAll(RegExp(r'[^0-9]'), '');
    if (digits.length != 5) return 'El código postal debe tener 5 dígitos';

    final cp = int.tryParse(digits);
    if (cp == null) return 'Código postal inválido';

    final isValid = _cpRanges.values.any(
      (ranges) => ranges.any((range) => cp >= range[0] && cp <= range[1]),
    );

    if (!isValid) return 'Código postal no válido para México';

    return null;
  }

  static String? getState(String cp) {
    final digits = cp.replaceAll(RegExp(r'[^0-9]'), '');
    final cpInt = int.tryParse(digits);
    if (cpInt == null) return null;

    for (final entry in _cpRanges.entries) {
      for (final range in entry.value) {
        if (cpInt >= range[0] && cpInt <= range[1]) {
          return entry.key;
        }
      }
    }
    return null;
  }
}
```

## CURP (Clave Única de Registro de Población)

### Backend (Laravel)

```php
class CurpRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $curp = strtoupper(trim((string) $value));

        // 18 caracteres: 4 letras + 6 números + 1 letra + 2 letras + 3 alfanuméricos + 1 dígito
        $pattern = '/^([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CLNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d)$/';

        if (!preg_match($pattern, $curp)) {
            $fail('El formato de CURP no es válido.');
            return;
        }

        if (!$this->validateCheckDigit($curp)) {
            $fail('El dígito verificador de la CURP no es válido.');
        }
    }

    private function validateCheckDigit(string $curp): bool
    {
        $chars = str_split($curp);
        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $char = $chars[$i];
            $value = is_numeric($char) ? (int) $char : (ord($char) - ord('A') + 10);
            $sum += $value * (18 - $i);
        }

        $checkDigit = 10 - ($sum % 10);
        if ($checkDigit === 10) $checkDigit = 0;

        return (int) $chars[17] === $checkDigit;
    }
}
```

### Frontend (Flutter)

```dart
class CurpValidator {
  static String? validate(String? value) {
    if (value == null || value.isEmpty) return 'La CURP es obligatoria';

    final curp = value.toUpperCase().trim();

    if (curp.length != 18) return 'La CURP debe tener 18 caracteres';

    final pattern = RegExp(
      r'^[A-Z][AEIOUX][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CLNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$',
    );

    if (!pattern.hasMatch(curp)) return 'Formato de CURP inválido';

    if (!_validateCheckDigit(curp)) return 'Dígito verificador de CURP inválido';

    return null;
  }

  static bool _validateCheckDigit(String curp) {
    final chars = curp.split('');
    int sum = 0;

    for (int i = 0; i < 17; i++) {
      final char = chars[i];
      final value = int.tryParse(char) ?? (char.codeUnitAt(0) - 'A'.codeUnitAt(0) + 10);
      sum += value * (18 - i);
    }

    int checkDigit = 10 - (sum % 10);
    if (checkDigit == 10) checkDigit = 0;

    return int.tryParse(chars[17]) == checkDigit;
  }
}
```

## Formato de fecha mexicano

### Convenciones

- Fecha corta: `DD/MM/AAAA` (ej: 12/04/2026)
- Fecha con hora: `DD/MM/AAAA HH:MM` (ej: 12/04/2026 14:30)
- Fecha larga: `12 de abril de 2026`
- Hora: formato 12h con AM/PM por default, opción 24h

### Frontend (Flutter)

```dart
extension MexicoDateFormat on DateTime {
  String toMexicoShort() {
    return '${day.toString().padLeft(2, '0')}/'
        '${month.toString().padLeft(2, '0')}/'
        '$year';
  }

  String toMexicoLong() {
    const months = [
      '', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
      'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    ];
    return '$day de ${months[month]} de $year';
  }

  String toMexicoDateTime() {
    return '${toMexicoShort()} ${toTime12h()}';
  }

  String toTime12h() {
    final hour12 = hour > 12 ? hour - 12 : (hour == 0 ? 12 : hour);
    final period = hour >= 12 ? 'PM' : 'AM';
    return '${hour12.toString().padLeft(2, '0')}:'
        '${minute.toString().padLeft(2, '0')} $period';
  }
}
```

### Backend (Laravel)

```php
class MexicoDateFormatter
{
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public static function short(Carbon $date): string
    {
        return $date->format('d/m/Y');
    }

    public static function long(Carbon $date): string
    {
        return "{$date->day} de " . self::MESES[$date->month] . " de {$date->year}";
    }

    public static function withTime(Carbon $date, bool $format12h = true): string
    {
        $time = $format12h
            ? $date->format('g:i A')
            : $date->format('H:i');

        return self::short($date) . ' ' . $time;
    }
}
```

## Estados de México (catálogo completo)

```php
class MexicoStates
{
    public const ESTADOS = [
        'AGU' => 'Aguascalientes',
        'BCN' => 'Baja California',
        'BCS' => 'Baja California Sur',
        'CAM' => 'Campeche',
        'CHH' => 'Chihuahua',
        'CHP' => 'Chiapas',
        'CMX' => 'Ciudad de México',
        'COA' => 'Coahuila de Zaragoza',
        'COL' => 'Colima',
        'DUR' => 'Durango',
        'GRO' => 'Guerrero',
        'GTO' => 'Guanajuato',
        'HID' => 'Hidalgo',
        'JAL' => 'Jalisco',
        'MEX' => 'Estado de México',
        'MIC' => 'Michoacán de Ocampo',
        'MOR' => 'Morelos',
        'NAY' => 'Nayarit',
        'NLE' => 'Nuevo León',
        'OAX' => 'Oaxaca',
        'PUE' => 'Puebla',
        'QRO' => 'Querétaro',
        'ROO' => 'Quintana Roo',
        'SIN' => 'Sinaloa',
        'SLP' => 'San Luis Potosí',
        'SON' => 'Sonora',
        'TAB' => 'Tabasco',
        'TAM' => 'Tamaulipas',
        'TLA' => 'Tlaxcala',
        'VER' => 'Veracruz de Ignacio de la Llave',
        'YUC' => 'Yucatán',
        'ZAC' => 'Zacatecas',
    ];
}
```

```dart
class MexicoStates {
  static const estados = <String, String>{
    'AGU': 'Aguascalientes',
    'BCN': 'Baja California',
    'BCS': 'Baja California Sur',
    'CAM': 'Campeche',
    'CHH': 'Chihuahua',
    'CHP': 'Chiapas',
    'CMX': 'Ciudad de México',
    'COA': 'Coahuila de Zaragoza',
    'COL': 'Colima',
    'DUR': 'Durango',
    'GRO': 'Guerrero',
    'GTO': 'Guanajuato',
    'HID': 'Hidalgo',
    'JAL': 'Jalisco',
    'MEX': 'Estado de México',
    'MIC': 'Michoacán de Ocampo',
    'MOR': 'Morelos',
    'NAY': 'Nayarit',
    'NLE': 'Nuevo León',
    'OAX': 'Oaxaca',
    'PUE': 'Puebla',
    'QRO': 'Querétaro',
    'ROO': 'Quintana Roo',
    'SIN': 'Sinaloa',
    'SLP': 'San Luis Potosí',
    'SON': 'Sonora',
    'TAB': 'Tabasco',
    'TAM': 'Tamaulipas',
    'TLA': 'Tlaxcala',
    'VER': 'Veracruz de Ignacio de la Llave',
    'YUC': 'Yucatán',
    'ZAC': 'Zacatecas',
  };
}
```

## Reglas obligatorias

1. SIEMPRE validar teléfono con lada mexicana — rechazar ladas inexistentes.
2. SIEMPRE validar código postal contra rangos oficiales por estado.
3. SIEMPRE mostrar fechas en formato `DD/MM/AAAA` para usuario final mexicano.
4. SIEMPRE usar hora 12h con AM/PM como default para UI de usuario final.
5. SIEMPRE almacenar CURP en mayúsculas sin espacios.
6. SIEMPRE usar catálogo oficial de 32 estados (incluyendo CDMX).
7. NUNCA aceptar códigos postales fuera del rango 01000-99999.
8. SIEMPRE derivar el estado del código postal cuando sea posible (autofill).