# Pivot Performance Toolkit — WordPress.org Launch Checklist
_Audit date: June 12, 2026. Plugin: pivot-performance-toolkit v1.0.0_

## Blockers — fix before submission

### 1. Scope Composer dependencies with Strauss
**Problem:** vendor/ ships illuminate/view + symfony, nesbot/carbon, doctrine, voku, psr unprefixed. If another active plugin loads a different version of these (common), the site fatals. Reviewers reject for this.

- [x] Add Strauss (brianhenryie/strauss) as a dev dependency / build step
- [x] Configure target namespace, e.g. `PivotPerformanceToolkit\Vendor\`, output to `includes/Vendor/`
- [x] Handle Illuminate global helper functions (`collect()`, `e()`, `view()`, etc.) — these live in unprefixed helper files Strauss does NOT namespace. Exclude or alias them (known Strauss + Laravel issue; check Strauss docs `exclude_from_copy` / `override_autoload`)
- [x] Update autoloader references in pivot-performance-toolkit.php
- [x] Verify Blade compiled-view cache path is writable and outside the plugin dir
- [ ] Smoke-test every admin page renders after scoping
- [ ] Decision noted: keep Blade for launch; consider gradual migration to plain PHP templates post-launch (would cut ~10MB of admin-side vendor weight)

### 2. readme.txt fixes
- [ ] Tags: max 5 used by the directory — currently 6 (`performance, cache, page cache, minify, lazy load, cloudflare`). Drop one.
- [ ] `Tested up to: 6.5` → test on and bump to `7.0` (WP 7.0 "Armstrong" released May 20, 2026; 7.1 due August 19, 2026). Outdated value shows an "untested" warning and hurts directory ranking.
- [ ] Add a "Source code" / build section: dist/admin-js.js and admin.css ship compiled while src/ is dist-ignored. Guideline 4 (human-readable code) requires linking the public source repo + build instructions (e.g., GitHub link, `npm install && npm run build`).
- [ ] Add an FAQ section (helps review and directory SEO)
- [ ] Add screenshots section + actual screenshots

### 3. wp-config.php + advanced-cache.php drop-in review
This is the code path reviewers read line-by-line for caching plugins.

- [ ] wp-config.php modification (WP_CACHE define): only on explicit user action, via WP_Filesystem, with clear failure messaging if not writable
- [ ] advanced-cache.php drop-in: install/remove cleanly on activate/deactivate; never overwrite another plugin's drop-in without warning
- [ ] Uninstall: verify uninstall.php fully reverts wp-config.php edits and removes the drop-in (it references both — confirm the revert logic is robust, including when the file was manually edited)

## Recommended — reviewers commonly request

- [ ] Add ABSPATH guards (`if ( ! defined( 'ABSPATH' ) ) exit;`) to all PHP files in includes/ — currently missing from class files (Plugin.php, Settings.php, PageCache.php, DatabaseOptimizer.php, all Admin/*.php, etc.)
- [ ] Review the 4 files making external HTTP calls (CachePage.php, AssetsPage.php, HttpProtocolDetector.php, CloudflareIntegration.php) — all calls must use wp_remote_*, be user-initiated or clearly disclosed, and never phone home
- [ ] Confirm no tracking/analytics without opt-in consent
- [ ] PHP 8.2 minimum: allowed, but excludes hosts still on 8.1 — confirm this is intentional (smaller addressable installs)

## Verified clean (no action needed)
- Nonces (wp_verify_nonce/check_admin_referer) + current_user_can across all admin pages
- No unescaped `echo $var` output found; Blade `{{ }}` auto-escapes
- Opt-in uninstall data removal (off by default)
- Prefixing: `ptk_` functions, `PivotPerformanceToolkit\` namespace, prefixed options
- .distignore correctly excludes dev junk (tests, docs, node_modules, build configs, dev-only vendor)
- GPL-2.0+ license, license.txt, THIRD-PARTY-LICENSES.txt present
- index.php silence files throughout
- Text domain matches slug (`pivot-performance-toolkit`), Domain Path set

## Submission process (after fixes)
- [ ] Build the dist zip (respecting .distignore — `wp dist-archive` is already in require-dev)
- [ ] Final pass: `Stable tag` matches version, changelog entry for 1.0.0
- [ ] Prepare .wordpress-org assets: banner 1544×500 (+772×250), icon 256×256 (+128×128), screenshots matching readme captions
- [ ] Submit zip at wordpress.org/plugins/developers/add/
- [ ] Expect initial response ~10 days, full approval typically 2–3 weeks (queue is ~4,800 plugins as of June 2026)
- [ ] On approval: commit to SVN (trunk + assets/ folder for banners/screenshots), tag 1.0.0
- [ ] Use review wait time to: finish pivotlabs.dev one-pager with email capture, plan Pro infrastructure

## Slug note
Plugin folder is `pivot-performance-toolkit` but the brand is "Pivot Performance Toolkit." The wordpress.org slug is assigned at submission from your requested name and is permanent — decide before submitting whether you want `pivot-performance-toolkit` or `wp-pivot-performance-toolkit`. ("WP" prefix is allowed; "WordPress" is not.)
