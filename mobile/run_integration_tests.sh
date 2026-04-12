#!/bin/bash

# Script para ejecutar tests de integración Flutter
# Requiere dispositivo/emulador activo

set -e

echo "=========================================="
echo "  Flutter Integration Tests"
echo "=========================================="
echo ""

# Verificar que estamos en directorio mobile
if [ ! -f "pubspec.yaml" ]; then
    echo "Error: Este script debe ejecutarse desde mobile/"
    exit 1
fi

# Verificar Flutter
if ! command -v flutter &> /dev/null; then
    echo "Error: Flutter no está instalado"
    exit 1
fi

# Verificar dispositivos disponibles
echo "Checking available devices..."
flutter devices

DEVICE_COUNT=$(flutter devices | grep -c "•" || true)
if [ $DEVICE_COUNT -eq 0 ]; then
    echo ""
    echo "Error: No hay dispositivos/emuladores disponibles"
    echo "Inicia un emulador o conecta un dispositivo físico"
    exit 1
fi

echo ""
echo "Installing dependencies..."
flutter pub get

echo ""
echo "Running integration tests..."
echo ""

# Ejecutar suite estable de integración con mock API (Fase 6)
flutter test integration_test/phase6_booking_mock_api_test.dart
flutter test integration_test/phase6_profile_mock_api_test.dart
flutter test integration_test/phase6_notifications_mock_api_test.dart

echo ""
echo "✓ Integration tests completed!"
