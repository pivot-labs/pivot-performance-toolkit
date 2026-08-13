=== Pivot Performance Toolkit ===
Contributors: pivotlabs
Tags: performance, cache, minify, lazy load, cloudflare
Requires at least: 6.9
Tested up to: 7.0
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

In `wp-content/cache/pivot-performance-toolkit/minified-assets`.

= What happens if I enable a feature and it breaks the website? =

We recommend enabling one feature at a time to validate the performance impact. If you encounter an issue, there is a the ability to disable the feature and clear the cache. Additionally there is a "Reset to Defaults" button in the Tools tab that will disable all features and clear caches if needed.

= I'm using Imagify and Cloudflare together and my images aren't showing as WebP =

Imagify's default WebP delivery method (server rewrite rules) is not reliable behind a CDN, including Cloudflare — this is a known limitation of that delivery method, not a Pivot Performance Toolkit issue. In Imagify's settings, switch WebP delivery to the "&lt;picture&gt; tag" method, which works correctly with Cloudflare. The CDN & Integrations page will show a reminder about this when both Cloudflare and Imagify are detected active.

== External services ==

This plugin connects to the Cloudflare API if you set up the optional Cloudflare integration (CDN & Integrations tab). It's only used to purge Cloudflare's cache — either when you click "Purge Cache," or automatically after you publish/update/trash content, if you've turned that option on.

Nothing is sent unless you've entered your own Cloudflare API token and Zone ID. What gets sent is your Zone ID and API token (to authenticate the request) and a purge instruction — no page content, no visitor data.

Service: Cloudflare, Inc. — https://api.cloudflare.com
Terms of Service: https://www.cloudflare.com/terms/
Privacy Policy: https://www.cloudflare.com/privacypolicy/

== Source Code ==

The stylesheet shipped in this plugin (`dist/admin.css`) is a compiled/minified build produced from human-readable source via Tailwind CSS and Vite. The source files (`src/css/admin.css`, `tailwind.config.js`, `vite.config.js`, `package.json`) are excluded from the distributed plugin package to keep it lean, but are published in full in the public GitHub repository:

https://github.com/pivot-labs/pivot-performance-toolkit

To build `dist/admin.css` (and `dist/admin-js.js`) from source:

1. Clone the repository above and check out the tag matching the plugin version you're building.
2. Install Node dependencies: `npm install`
3. Run the build: `npm run build`

This regenerates the `dist/` directory from the source files in `src/css/` and `src/js/` using the Vite config in `vite.config.js`.

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

