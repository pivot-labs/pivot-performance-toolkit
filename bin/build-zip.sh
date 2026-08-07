#!/usr/bin/env bash
# Build the distributable plugin zip from the current working tree.
#
# Usage: bin/build-zip.sh
#
# Reads the version from pivot-performance-toolkit.php (single source of
# truth — run bin/set-version.sh first if you want a different version),
# rebuilds dist/admin.css + dist/admin-js.js from source, and produces
# dist-builds/pivot-performance-toolkit-<version>.zip via wp dist-archive,
# respecting .distignore.
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

VERSION="$(grep -m1 '^ \* Version:' pivot-performance-toolkit.php | sed -E 's/^ \* Version: *//')"

if [ -z "$VERSION" ]; then
    echo "Error: could not read Version from pivot-performance-toolkit.php" >&2
    exit 1
fi

echo "Building version: ${VERSION}"

echo "==> npm run build"
npm run build

OUT_DIR="$ROOT_DIR/dist-builds"
mkdir -p "$OUT_DIR"
OUT_ZIP="$OUT_DIR/pivot-performance-toolkit-${VERSION}.zip"

rm -f "$OUT_ZIP"

echo "==> wp dist-archive"
vendor/bin/wp dist-archive "$ROOT_DIR" "$OUT_ZIP" --allow-root

echo ""
echo "Built: $OUT_ZIP"
