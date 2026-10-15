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

# Remove packages that composer.json "replace"s: the Symfony polyfills (the plugin requires
# PHP 8.2+ and ext-mbstring) and Carbon with its dependencies (nothing in the Blade rendering
# path uses them, and Illuminate guards its one Carbon call with class_exists). Strauss never
# deletes directories it has stopped copying, so remove any stale scoped copies here.
for stale in \
    symfony/polyfill-mbstring symfony/polyfill-php83 \
    nesbot carbonphp psr/clock symfony/clock \
    symfony/translation symfony/translation-contracts symfony/deprecation-contracts; do
    rm -rf "$(dirname "$0")/../includes/Vendor/$stale"
done
