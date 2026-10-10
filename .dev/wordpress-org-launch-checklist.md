# Pivot Performance Toolkit — WordPress.org Launch Checklist
_Audit date: June 12, 2026. Plugin: pivot-performance-toolkit v1.0.0_
_Revisited: October 8, 2026, against v1.1.1 — the plugin has since gone live on wordpress.org (readme's own changelog confirms "Initial public release" for 1.0.0) and this is now a post-review update release, not a first submission. Everything below was re-checked against the current code and `.dev/wordpress-org-review-feedback.md`'s audit; items are marked resolved only where that was actually verified, not assumed._

## Blockers — fix before submission

### 1. Scope Composer dependencies with Strauss
**Problem:** vendor/ ships illuminate/view + symfony, nesbot/carbon, doctrine, voku, psr unprefixed. If another active plugin loads a different version of these (common), the site fatals. Reviewers reject for this.

- [x] Add Strauss (brianhenryie/strauss) as a dev dependency / build step
- [x] Configure target namespace, e.g. `PivotPerformanceToolkit\Vendor\`, output to `includes/Vendor/`
- [x] Handle Illuminate global helper functions (`collect()`, `e()`, `view()`, etc.) — these live in unprefixed helper files Strauss does NOT namespace. Exclude or alias them (known Strauss + Laravel issue; check Strauss docs `exclude_from_copy` / `override_autoload`)
- [x] Update autoloader references in pivot-performance-toolkit.php
- [x] Verify Blade compiled-view cache path is writable and outside the plugin dir
- [x] Smoke-test every admin page renders after scoping — **done** (server-side pass, Oct 8 2026): authenticated via a wp-cli-generated session/auth cookie (no password reset) and curled all 16 registered admin pages (Dashboard, Cache, File Optimization, Assets, Media Optimization, Performance, Database, Database Table, Settings, Browser Cache Headers, CDN Integrations, Advanced Rules, Tools×2, System Status, Documentation) via `admin.php?page=pivot-performance-toolkit&tab=<slug>`. All returned HTTP 200 with no `Fatal error`/`Uncaught`/`Parse error`/PHP `Warning:`/`Notice:`/`Deprecated:` in the output; the two literal "Warning:" hits were UI copy (exclusion-rules caution text, export-secrets notice), not PHP errors. Caveat: this verifies server-side rendering only, not JS/console errors or visual layout — a real browser pass is still worth doing if you get Chrome's cert trusted for the automation profile, but this clears the blocker for cutting the release
- [x] Decision noted: keep Blade for launch — confirmed still the case in v1.1.1 (no migration to plain PHP templates happened; Blade templates are used throughout `includes/Views/`)

### 2. readme.txt fixes
- [x] Tags: max 5 — now `performance, cache, minify, lazy load, cloudflare` (5)
- [x] `Tested up to: 6.5` → now `7.1` (also `Requires at least: 6.9`, `Requires PHP: 8.2`, `Stable tag: 1.1.1` — all consistent with the plugin header)
- [x] Add a "Source code" / build section — `== Source Code ==` exists, links `https://github.com/pivot-labs/pivot-performance-toolkit`, documents `npm install && npm run build`
- [x] Add an FAQ section — `== Frequently Asked Questions ==` exists
- [x] Add screenshots section + actual screenshots — `== Screenshots ==` has 6 numbered captions, and `.wordpress-org/screenshot-1.png` through `screenshot-6.png` all exist on disk, plus both banner sizes and both icon sizes

### 3. wp-config.php + advanced-cache.php drop-in review
This is the code path reviewers read line-by-line for caching plugins.

- [x] wp-config.php modification (WP_CACHE define): `includes/Core/Lifecycle.php` only touches it via `WP_Filesystem()`, checks `exists()`/`is_writable()` first, and surfaces a specific admin notice for every failure mode (filesystem unavailable, not writable, unreadable, can't find insertion point) rather than failing silently
- [x] advanced-cache.php drop-in: `dropinIsOurs()` guards every install/update/remove path; `installDropin()` explicitly refuses to overwrite a drop-in it doesn't own and shows a notice instead
- [x] Uninstall: `uninstall.php` removes the drop-in only if `dropinIsOurs`-equivalent content check passes, and strips the `WP_CACHE` define only when it carries the plugin's own `// Added by Pivot Performance Toolkit` marker comment — safe even if the file was hand-edited

## Recommended — reviewers commonly request

- [x] Add ABSPATH guards to all PHP files in includes/ — audited in the review-feedback pass; one real gap found (`Lifecycle.php`) and fixed, everything else already had it
- [x] Review the 4 files making external HTTP calls — `CachePage.php`, `AssetsPage.php`, `HttpProtocolDetector.php`, `includes/Integrations/CloudflareIntegration.php` (renamed from the original audit's `CloudflareIntegration.php` reference, same file) all go through `wp_remote_get()`/`wp_remote_head()`/`wp_remote_request()`, never raw `curl`/sockets
- [x] Confirm no tracking/analytics without opt-in consent — only outbound call is the optional, user-configured Cloudflare integration; readme's `== External services ==` section documents exactly what's sent and when, and only on explicit action or an opt-in toggle
- [x] PHP 8.2 minimum: confirmed intentional — consistently declared in both the plugin header and readme.txt, not an oversight

## Verified clean (no action needed)
- Nonces (wp_verify_nonce/check_admin_referer) + current_user_can across all admin pages
- No unescaped `echo $var` output found; Blade `{{ }}` auto-escapes
- Opt-in uninstall data removal (off by default)
- Prefixing: `pivot_performance_toolkit_` functions/options, `PivotPerformanceToolkit\` namespace
- .distignore correctly excludes dev junk (tests, docs, node_modules, build configs, dev-only vendor, src/css, src/js, package.json, vite/tailwind configs)
- GPL-2.0+ license, license.txt present
- index.php silence files throughout
- Text domain matches slug (`pivot-performance-toolkit`)

## Final review pass (October 9, 2026, v1.1.1 — immediately before build/submission)
Re-checked inline CSS/JS (already covered above — still clean) and i18n completeness, since neither had been specifically re-verified since the earlier passes.

- [x] `$wpdb` queries without `->prepare()` — checked `DatabaseOptimizer.php`/`SystemStatusPage.php`; every static-SQL query already carries a `phpcs:ignore WordPress.DB.DirectDatabaseQuery.*` with a specific justification, and the one dynamic `ORDER BY` has its own `PreparedSQL.InterpolatedNotPrepared` ignore (whitelist-derived). No gap.
- [x] `languages/pivot-performance-toolkit.pot` — **real gap found and fixed**: the shipped `.pot` was stale (generated Aug 9 2026 against a pre-1.1.1 dev build) and, more importantly, structurally incomplete — `wp i18n make-pot` parses source as plain PHP, so Blade template syntax (`{{ __('...') }}`, `<x-card :title="__('...')">`) silently broke its tokenizer partway through most `.blade.php` files, dropping the majority of actual admin-UI strings (only 563 of the plugin's ~650 unique translatable strings were ever captured, every one of them from plain `.php` class files — the shipped `.pot` had *never* represented the UI copy, not just gone stale). Fixed with `bin/make-pot.php`: regex-extracts every `__()`/`_e()`/`esc_html__()`/`esc_html_e()`/`esc_attr__()`/`esc_attr_e()` call with a literal argument from `.blade.php` files, feeds them through a throwaway stub `.php` file so `wp i18n make-pot`'s normal parser/merger picks them up, then patches the stub's `#:` source references back to the real blade file:line. Verified zero real gaps remain (645 entries; the only `_n`/`_x`/`_nx`/`_ex` plural/context calls in the codebase are in plain `.php` files, already handled natively). Wired into the release pipeline as `composer make-pot`, called from `bin/build-zip.sh` right after `npm run build`, so this can't go stale again. Needed `wp-cli/i18n-command` added as a require-dev dependency (`vendor/bin/wp` didn't have it before — only `dist-archive-command` was installed).

## Submission process (this is an SVN update, not a first submission — v1.0.0 is already live)
- [ ] Build the dist zip (respecting .distignore)
- [x] Final pass: `Stable tag` (1.1.1) matches plugin header `Version:` (1.1.1); changelog entry for 1.1.1 exists and describes the security hardening pass
- [x] `.wordpress-org` assets already present (banners, icons, screenshots) — no new assets needed unless screenshots are now stale against the current UI
- [ ] Commit the new version to SVN trunk, tag 1.1.1
- [ ] Double-check the SVN `assets/` folder still matches `.wordpress-org/` if anything changed there

## Slug note
Already decided and in use: `pivot-performance-toolkit` (not the `wp-` prefixed alternative) — this is permanent now that the plugin is live, so no action needed here.
