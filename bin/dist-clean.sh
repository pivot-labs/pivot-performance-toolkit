#!/usr/bin/env bash
# Remove unscoped vendor packages after Strauss has scoped them to includes/Vendor/.
# Run as part of the distribution build — not during development.
set -e
VENDOR_DIR="$(dirname "$0")/../vendor"
for dir in illuminate symfony nesbot carbonphp doctrine voku psr; do
    target="$VENDOR_DIR/$dir"
    if [ -d "$target" ]; then
        rm -rf "$target"
        echo "Removed $target"
    fi
done

# Prune dead CLI-only tooling from the scoped symfony/translation package.
# Confirmed unused: these are Symfony Console commands (translation
# lint/pull/push, a standalone status script, and the .po dumper only
# TranslationPullCommand calls) meant to run via a console app entry point
# this WordPress plugin doesn't have. Verified via a full-codebase grep
# before removing — nothing outside this subtree references them, including
# Translator.php (the core, always-loaded class). Re-verify the same way
# after any Strauss/Composer version bump, since a future release could
# start referencing one of these from elsewhere.
SCOPED_TRANSLATION_DIR="$(dirname "$0")/../includes/Vendor/symfony/translation"
if [ -d "$SCOPED_TRANSLATION_DIR" ]; then
    rm -rf "$SCOPED_TRANSLATION_DIR/Command"
    rm -f "$SCOPED_TRANSLATION_DIR/Resources/bin/translation-status.php"
    rm -f "$SCOPED_TRANSLATION_DIR/Dumper/PoFileDumper.php"
    echo "Removed unused symfony/translation CLI tooling from $SCOPED_TRANSLATION_DIR"
fi