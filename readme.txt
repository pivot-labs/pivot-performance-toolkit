=== WP Performance Toolkit ===
Contributors: jeffshaikh
Tags: performance, cache, page cache, minify, lazy load, cloudflare
Requires at least: 6.5
Tested up to: 6.5
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A practical, safe-by-default WordPress performance plugin with page caching, minification, lazy loading, and Cloudflare integration.

== Description ==
Performance Toolkit helps speed up WordPress sites with focused optimizations that are easy to configure and safe to roll out.

Core features in this version include:

* Disk-based page caching for anonymous GET requests.
* Cache TTL and max cache size controls.
* URL exclusion rules for pages that should never be cached.
* One-click cache clear action.
* HTML minification.
* Inline CSS and inline JavaScript minification.
* External CSS and JavaScript minification for local assets.
* Script defer support (with safe built-in exclusions).
* Native image lazy-loading support.
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
https://www.wpperformancetoolkit.com/

Documentation:
http://docs.wpperformancetoolkit.com/


== Installation ==

1. Upload the `performance-toolkit` folder to the `/wp-content/plugins/` directory, or install it through the WordPress plugin screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Go to **Performance Toolkit** in the WordPress admin menu.
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

In `wp-content/cache/performance-toolkit/minified-assets`.

= What happens if I enable a feature and it breaks the website? =

We recommend enabling one feature at a time to validate the performance impact. If you encounter an issue, there is a the ability to disable the feature and clear the cache. Additionally there is a "Reset to Defaults" button in the Tools tab that will disable all features and clear caches if needed.

== Screenshots ==

1. Dashboard and module overview.
2. Cache settings and cache usage indicator.
3. File optimization settings for defer and minification.
4. Cloudflare integration with test and purge actions.
5. Database cleanup tools.
6. Tools tab with minified asset clear and import/export utilities.

== Changelog ==

= 1.0.0 =

* Initial public release.

== Upgrade Notice ==

= 0.1.0 =

Initial release.

