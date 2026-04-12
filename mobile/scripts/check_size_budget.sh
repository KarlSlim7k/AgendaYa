#!/usr/bin/env bash

set -euo pipefail

TARGET_FILE="${1:-build/app/outputs/flutter-apk/app-release.apk}"
SIZE_LIMIT_MB="${SIZE_LIMIT_MB:-30}"

if [[ ! -f "$TARGET_FILE" ]]; then
  echo "Error: no se encontró el artefacto en $TARGET_FILE"
  exit 1
fi

SIZE_BYTES=$(wc -c < "$TARGET_FILE")
SIZE_MB=$(awk "BEGIN {printf \"%.2f\", $SIZE_BYTES/1024/1024}")

printf "Artifact: %s\n" "$TARGET_FILE"
printf "Size: %s MB\n" "$SIZE_MB"
printf "Budget: %s MB\n" "$SIZE_LIMIT_MB"

EXCEEDS=$(awk "BEGIN {print ($SIZE_MB > $SIZE_LIMIT_MB) ? 1 : 0}")
if [[ "$EXCEEDS" -eq 1 ]]; then
  echo "Error: tamaño excede el presupuesto de $SIZE_LIMIT_MB MB"
  exit 1
fi

echo "OK: tamaño dentro del presupuesto"
