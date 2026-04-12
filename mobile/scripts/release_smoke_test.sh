#!/usr/bin/env bash

set -euo pipefail

if [[ ! -f "pubspec.yaml" ]]; then
  echo "Error: ejecuta este script dentro de mobile/"
  exit 1
fi

if ! command -v flutter >/dev/null 2>&1; then
  echo "Error: Flutter no está instalado en este entorno"
  exit 1
fi

echo "==> flutter pub get"
flutter pub get

echo "==> flutter analyze"
flutter analyze

echo "==> unit + widget tests"
flutter test test/

echo "==> integration tests mock API"
flutter test integration_test/phase6_booking_mock_api_test.dart
flutter test integration_test/phase6_profile_mock_api_test.dart
flutter test integration_test/phase6_notifications_mock_api_test.dart

echo "==> release APK"
flutter build apk --release --target-platform android-arm64

bash scripts/check_size_budget.sh build/app/outputs/flutter-apk/app-release.apk

echo "Smoke release OK"
