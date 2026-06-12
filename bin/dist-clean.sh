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