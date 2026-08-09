#!/usr/bin/env bash
# Build the distributable plugin zip.
#
# Usage: bin/build-zip.sh
#
# Reads the version from pivot-performance-toolkit.php (single source of
# truth — run bin/set-version.sh first if you want a different version),
# rebuilds dist/admin.css + dist/admin-js.js from source, then builds the
# zip from an ISOLATED copy of the working tree (including any uncommitted
# changes) rather than the live checkout.
#
# This isolation matters: the zip must ship a Composer autoloader generated
# with `--no-dev` (no dev-only packages like wp-cli/dist-archive-command),
# because .distignore strips those dev vendor directories out of the
# archive — if the autoloader manifest still referenced them, the shipped
# plugin would fatal on activation. But `wp dist-archive` itself is a
# dev-only command, so it needs the live checkout's dev-mode vendor/bin/wp
# to run. Building `--no-dev` directly in the live tree would remove the
# very tool needed to run dist-archive. Building in an isolated copy avoids
# that conflict, and also leaves the live checkout's dev environment
# (vendor/bin/phpcs etc.) untouched for ongoing development.
set -euo pipefail

# -P: resolve symlinks to the physical path. wp-content/plugins/<slug> here
# is a symlink into ~/Sites/, and wp-cli's own internal __DIR__-based
# requires resolve the physical path — if ROOT_DIR used the logical
# (symlinked) path instead, the same Composer autoloader file could get
# `require`d under two different path strings and fatal on class redeclaration.
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd -P)"
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

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

echo "==> snapshotting working tree (including uncommitted and untracked files)"
# A git-based snapshot (stash/archive) silently drops untracked files, which
# would ship a zip missing any not-yet-`git add`ed file (e.g. a brand new
# class file) while still referencing it elsewhere — a broken build with no
# warning. Copying the working tree directly guarantees the zip matches
# what's actually on disk.
mkdir -p "$TMP_DIR/pivot-performance-toolkit"
rsync -a \
    --exclude='.git/' \
    --exclude='node_modules/' \
    --exclude='vendor/' \
    --exclude='includes/Vendor/' \
    --exclude='dist-builds/' \
    "$ROOT_DIR/" "$TMP_DIR/pivot-performance-toolkit/"

echo "==> composer install --no-dev (clean, distributable autoloader)"
(cd "$TMP_DIR/pivot-performance-toolkit" && composer install --no-dev --prefer-dist --optimize-autoloader)

echo "==> removing unscoped vendor packages now that Strauss has scoped them"
(cd "$TMP_DIR/pivot-performance-toolkit" && bash bin/dist-clean.sh)

echo "==> regenerating autoloader (dist-clean.sh removed files the prior manifest still referenced)"
(cd "$TMP_DIR/pivot-performance-toolkit" && composer dump-autoload --no-dev --optimize)

echo "==> wp dist-archive"
vendor/bin/wp dist-archive "$TMP_DIR/pivot-performance-toolkit" "$OUT_ZIP" --allow-root

echo ""
echo "Built: $OUT_ZIP"
