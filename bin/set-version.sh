#!/usr/bin/env bash
# Set the plugin version across every file that declares it.
#
# Usage: bin/set-version.sh <version>
#   bin/set-version.sh 1.1.0
#   bin/set-version.sh 1.1.0-dev-20260807.1
#
# Updates:
#   - pivot-performance-toolkit.php  (Version: header + PIVOT_PERFORMANCE_TOOLKIT_VERSION constant)
#   - readme.txt                     (Stable tag:)
#   - package.json                   ("version":)
#   - composer.json                  ("version":)
set -euo pipefail

VERSION="${1:-}"

if [ -z "$VERSION" ]; then
    echo "Usage: bin/set-version.sh <version>" >&2
    exit 1
fi

if ! [[ "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
    echo "Error: '$VERSION' doesn't look like a valid version (expected e.g. 1.1.0 or 1.1.0-dev-20260807.1)." >&2
    exit 1
fi

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

PLUGIN_FILE="pivot-performance-toolkit.php"
README_FILE="readme.txt"
PACKAGE_JSON="package.json"
COMPOSER_JSON="composer.json"

# Composer's "version" field is validated against a stricter schema than the
# other three files: it only recognizes a single stability suffix (dev, beta,
# alpha, rc, ...), not an arbitrary second "-something" segment after it. A
# version like "1.1.0-dev-20260807.1" fails that schema and breaks every
# composer command (install, validate, ...). Everything after "-dev-" in that
# pattern is build metadata, not a stability label, so for composer.json only,
# rewrite it using "+" (Composer's build-metadata separator) instead of the
# second "-". Versions that don't match this shape (e.g. a plain "1.1.0")
# pass through unchanged, since Composer already accepts those.
COMPOSER_VERSION="$VERSION"
if [[ "$VERSION" =~ ^([0-9]+\.[0-9]+\.[0-9]+-dev)-(.+)$ ]]; then
    COMPOSER_VERSION="${BASH_REMATCH[1]}+${BASH_REMATCH[2]}"
fi

sed -i '' -E "s/^( \* Version: ).*/\1${VERSION}/" "$PLUGIN_FILE"
sed -i '' -E "s/(define\( 'PIVOT_PERFORMANCE_TOOLKIT_VERSION', ')[^']*(' \);)/\1${VERSION}\2/" "$PLUGIN_FILE"
sed -i '' -E "s/^(Stable tag: ).*/\1${VERSION}/" "$README_FILE"
sed -i '' -E "s/(\"version\": \")[^\"]*(\")/\1${VERSION}\2/" "$PACKAGE_JSON"
sed -i '' -E "s/(\"version\": \")[^\"]*(\")/\1${COMPOSER_VERSION}\2/" "$COMPOSER_JSON"

echo "Version set to ${VERSION} in:"
echo "  - $PLUGIN_FILE (header + constant)"
echo "  - $README_FILE (Stable tag)"
echo "  - $PACKAGE_JSON"
if [ "$COMPOSER_VERSION" != "$VERSION" ]; then
    echo "  - $COMPOSER_JSON (as ${COMPOSER_VERSION} — Composer's schema doesn't allow the -dev-<build> form)"
else
    echo "  - $COMPOSER_JSON"
fi
