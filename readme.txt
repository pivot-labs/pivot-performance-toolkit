=== Pivot Performance Toolkit ===
Contributors: pivotlabs, jeffshaikh
Tags: performance, cache, minify, lazy load, cloudflare
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A practical, safe-by-default WordPress performance plugin with page caching, minification, lazy loading, and Cloudflare integration.

== Description ==
Pivot Performance Toolkit helps speed up WordPress sites with focused optimizations that are easy to configure and safe to roll out.

Core features in this version include:

* Real-time performance scoring, runnable on demand from the dashboard: page load time, Largest Contentful Paint, Time to First Byte, First Contentful Paint, resource count, and JS/CSS/image payload size, with a live cache-hit indicator.
* Disk-based page caching for anonymous GET requests, with cache preload/warming.
* Cache TTL and max cache size controls, with automatic pruning of oldest files.
* URL exclusion rules for pages that should never be cached, including WooCommerce-aware default exclusions (cart, checkout, my account).
* One-click cache clear action.
* File-based object cache to persist WordPress object cache entries to disk between requests.
* Browser cache header controls, with ready-to-use Apache (.htaccess) and Nginx configuration snippets.
* HTML minification.
* Inline CSS and inline JavaScript minification.
* External CSS and JavaScript minification for local assets, with cached minified copies.
* Script defer support (with safe built-in exclusions).
* HTTP/1.1 file combination for CSS/JS, with automatic HTTP protocol detection so the plugin can recommend against combining files on HTTP/2+ connections.
* Minification and combination exclusion rules by handle, filename, path, or wildcard pattern.
* Native lazy-loading for content images, with automatic detection of other active image-optimization plugins to avoid conflicts.
* Asset detector: scan any page or post to see its loaded CSS and JavaScript files.
* Cloudflare integration:
  * Provider selection
  * API token + Zone ID settings
  * Test connection action
  * Purge cache action
  * Auto-purge on content update option
* Database cleanup tools:
  * Revisions
  * Auto-drafts
  * Trashed posts/comments
  * Spam comments
  * Expired transients
  * Table optimization
* Tools tab utilities:
  * Clear minified CSS/JS cache
  * Export settings to JSON
  * Import settings from JSON
  * Optional "include secret API keys" export checkbox with warning
* System status page with environment and database size details.

Homepage:
https://www.pivotlabs.dev/

Documentation:
https://docs.pivotlabs.dev/performance-toolkit


== Installation ==

Requirements: WordPress 6.9 or later, PHP 8.2 or later, and the PHP mbstring extension (enabled on most hosts; the plugin shows a notice and stays inactive if it is missing).

1. Upload the `pivot-performance-toolkit` folder to the `/wp-content/plugins/` directory, or install it through the WordPress plugin screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Pivot Performance Toolkit** in the WordPress admin menu.
4. Start with the **Cache** tab, then enable additional optimizations as needed.

== Frequently Asked Questions ==

= Is this plugin safe to enable on production sites? =

Yes. Features are designed to be modular so you can enable optimizations one at a time and validate pages.

= Does it work with Cloudflare? =

Yes. Configure your Cloudflare API token and Zone ID in **CDN & Integrations**. You can test the connection and purge cache from the plugin UI.

= Will this cache pages for logged-in users? =

No. Page caching targets anonymous visitors by default.

= Can I exclude specific pages from cache? =

Yes. Add URL patterns to the cache exclusions setting.

= Does settings export include API secrets by default? =

No. Secret keys are redacted unless you explicitly check the "include secret API keys" option during export.

= Where are minified external asset files stored? =

In your uploads folder, in `pivot-performance-toolkit/minified-assets` (combined bundles are in `pivot-performance-toolkit/combined-assets`). The location is resolved with `wp_upload_dir()`, so it follows any custom uploads setup. These are public CSS/JS files that browsers request by URL. They are safe to delete (Tools > Clear minified assets) and are regenerated on demand.

= What else does the plugin write to disk, and where? =

* Page cache files and the cache settings file: `wp-content/cache/pivot-performance-toolkit/`. The page-cache drop-in (`wp-content/advanced-cache.php`) reads them before WordPress has loaded, so they cannot live in uploads or in the plugin folder.
* Optional file object cache: `wp-content/object-cache.php` (a WordPress drop-in, installed only when you enable it) and its data in `wp-content/cache/pivot-performance-toolkit/object-cache/`.
* Compiled admin templates: `wp-content/cache/pivot-performance-toolkit-blade/`. These are PHP files, so they are kept out of the publicly served uploads folder.
* Optionally, a `WP_CACHE` line in `wp-config.php` and cache headers in `.htaccess`, both only when you use the matching setting.

The plugin never writes to its own plugin folder, to other plugins or themes, or to WordPress core directories.

= What happens if I enable a feature and it breaks the website? =

We recommend enabling one feature at a time to validate the performance impact. If you encounter an issue, there is a the ability to disable the feature and clear the cache. Additionally there is a "Reset to Defaults" button in the Tools tab that will disable all features and clear caches if needed.

= I'm using Imagify and Cloudflare together and my images aren't showing as WebP =

Imagify's default WebP delivery method (server rewrite rules) is not reliable behind a CDN, including Cloudflare — this is a known limitation of that delivery method, not a Pivot Performance Toolkit issue. In Imagify's settings, switch WebP delivery to the "&lt;picture&gt; tag" method, which works correctly with Cloudflare. The CDN & Integrations page will show a reminder about this when both Cloudflare and Imagify are detected active.

= Why does the plugin include an object-cache.php that defines WP_Object_Cache? =

The optional file-based object cache is a WordPress "drop-in". WordPress requires a drop-in to define a class named `WP_Object_Cache` and the global `wp_cache_*()` functions under exactly those names, so they cannot be prefixed. The file (`includes/Cache/object-cache.php`) is not loaded by the plugin. It is copied to `wp-content/object-cache.php` only when an administrator clicks "Enable File Object Cache" on the Cache screen, and it is removed again when the feature is disabled. The plugin never overwrites an `object-cache.php` that another plugin has installed.

== External services ==

This plugin connects to the Cloudflare API if you set up the optional Cloudflare integration (CDN & Integrations tab). It's only used to purge Cloudflare's cache — either when you click "Purge Cache," or automatically after you publish/update/trash content, if you've turned that option on.

Nothing is sent unless you've entered your own Cloudflare API token and Zone ID. What gets sent is your Zone ID and API token (to authenticate the request) and a purge instruction — no page content, no visitor data.

Service: Cloudflare, Inc. — https://api.cloudflare.com
Terms of Service: https://www.cloudflare.com/terms/
Privacy Policy: https://www.cloudflare.com/privacypolicy/

= Requests to your own site (not a third-party service) =

The plugin also makes HTTP requests whose only destination is your own website's address (the host in Settings > General). None of them leave your site's domain, no third party receives anything, and no visitor data is sent:

* Cache preload (Cache screen): requests your site's own URLs so the page cache is warmed. If an HTTPS loopback request fails (common on local development servers with self-signed certificates), the same URL is retried once over `http://` on that same host. This is the `http://` fallback you may see in the code; it never targets another domain.
* Asset detector (Assets screen): fetches one page of your own site that you pick, to list the CSS and JavaScript it loads. Only URLs on your own host are accepted.
* HTTP protocol check (File Optimization screen): sends a HEAD request to your own home page to read which HTTP version the server answered with.
* Performance test (Dashboard / Performance): runs in your browser against a page of your own site.

Apart from the Cloudflare integration above, the plugin does not contact any external server, load remote scripts or fonts, or send usage data. The admin screens also contain ordinary links (to the plugin documentation at docs.pivotlabs.dev, and to WebPageTest and GTmetrix in the Browser Cache testing help); they only open when you click them and nothing is sent in the background.

== Source Code ==

Almost everything in this plugin is plain, human-readable PHP, JavaScript and CSS. Two files are built (compiled and minified) from source that is included in this same package:

* `dist/admin-js.js` is built from `src/js/admin.js`.
* `dist/admin.css` is built from `src/css/admin.css` with Tailwind CSS. It looks for Tailwind class names in the plugin's templates in `includes/` and its scripts in `assets/js/` and `src/js/` (the `@source` lines at the top of `src/css/admin.css` list exactly where).

The build is configured by `package.json`, `package-lock.json` and `vite.config.js`, all included in the plugin folder. The same files are published, with every release tagged (for example `v1.1.1`), in the public repository:

https://github.com/pivot-labs/pivot-performance-toolkit

To rebuild `dist/` from this package (requires Node.js 22 or newer):

1. Open a terminal in the plugin folder.
2. Install the exact locked dependencies: `npm ci`
3. Build: `npm run build`

This regenerates `dist/admin.css` and `dist/admin-js.js` using Vite and Tailwind CSS. The result is identical to the files shipped in `dist/`.

Not built, and shipped exactly as written: `assets/js/delayed-js-runtime.js`, `assets/js/performance-probe.js`, `assets/js/performance-test.js`, `assets/css/menu-icon.css`, and all PHP in `includes/` (apart from `includes/Vendor/`).

= Bundled PHP libraries =

`includes/Vendor/` holds copies of these Composer packages. They are ordinary readable PHP, not minified. The only changes to their code are made by the build scripts in the public repository (the `bin/` folder and the `scripts` in `composer.json`): namespaces are prefixed (using Strauss) so they cannot conflict with other plugins, a direct-access guard (`if ( ! defined( 'ABSPATH' ) ) { exit; }`) is added to each file, and a few small patches are applied (one heredoc rewritten as a plain string, the generated autoloader class renamed, a dead PHP-version check removed, and the Blade features the plugin does not use — `@section`, `@push`, `@fragment`, `@lang` and Illuminate's component buffering — removed from illuminate/view; the plugin renders components with its own `includes/Views/ClosureComponents.php` instead). `composer install` regenerates the folder from `composer.json` (included in this plugin) and `composer.lock` (in the public repository).

* Laravel Illuminate view, support, collections, container, contracts, events, filesystem, macroable, conditionable, bus and pipeline, v11.51.0 - https://github.com/illuminate
* doctrine/inflector 2.1.0 - https://github.com/doctrine/inflector
* psr/container 2.0.2 and psr/simple-cache 2.0.0 - https://github.com/php-fig
* symfony/finder v7.4.8 - https://github.com/symfony/finder
* voku/portable-ascii 2.1.1 - https://github.com/voku/portable-ascii

Icon artwork from Lucide (ISC license) is embedded as readable SVG markup in `includes/Admin/LucideIcons.php`. Licenses for all third-party code are listed in `THIRD-PARTY-LICENSES.txt`.

== Screenshots ==

1. Dashboard and module overview.
2. Cache settings and cache usage indicator.
3. File optimization settings for defer and minification.
4. Cloudflare integration with test and purge actions.
5. Database cleanup tools.
6. Cache exclusion rules for URLs that should never be cached.

== Changelog ==

= 1.1.1 =

* Security: Hardening pass covering input sanitization, output escaping, nonce/permission checks, and file-access guards across the plugin, following a WordPress.org review.
* Change: Minimum WordPress version raised to 6.9, so page-output processing (async CSS, delayed JS, combine, minify, page cache) uses core's own output-buffer API instead of a plugin-managed one.
* Change: Several inline admin scripts moved to the standard WordPress script-enqueue system.
* Fix: Contributors and External Services info corrected in this readme.

= 1.0.1 =

* Fix: page caching could silently never serve cached pages on some installs. The cache would fill up normally, but the drop-in responsible for serving a fast cache hit had no configuration to read until the Cache settings page was saved once — so every request kept regenerating the cache instead of serving it. New installs are unaffected on activation, and existing installs self-correct automatically on the next page load.

= 1.0.0 =

* Initial public release.

== Upgrade Notice ==

= 1.1.1 =

Now requires WordPress 6.9 or later. Security hardening release — recommended update.

= 1.0.1 =

Fixes a bug where page caching would keep regenerating cache files instead of serving them. Recommended update.

= 1.0.0 =

Initial release.

