#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="0.8.0"
SLUG="sustainable-catalyst-engagement-intake"
rm -rf "${ROOT}/dist"
mkdir -p "${ROOT}/dist"
(cd "${ROOT}" && zip -qr "${ROOT}/dist/${SLUG}-v${VERSION}.zip" "${SLUG}" -x "*/.DS_Store" "*/__MACOSX/*")
echo "Created ${ROOT}/dist/${SLUG}-v${VERSION}.zip"
