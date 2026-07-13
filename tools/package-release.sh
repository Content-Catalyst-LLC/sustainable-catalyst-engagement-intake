#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="0.1.0"
PLUGIN_SLUG="sustainable-catalyst-engagement-intake"
DIST="${ROOT}/dist"

rm -rf "${DIST}"
mkdir -p "${DIST}"

(
  cd "${ROOT}"
  zip -qr "${DIST}/${PLUGIN_SLUG}-v${VERSION}.zip" "${PLUGIN_SLUG}" \
    -x "*/.DS_Store" "*/__MACOSX/*"
)

echo "Created ${DIST}/${PLUGIN_SLUG}-v${VERSION}.zip"
