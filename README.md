# Pivot Performance Toolkit

A modular WordPress plugin focused on practical, safe-by-default performance optimizations.

## Current MVP Features

- Dedicated top-level admin menu (`Pivot Performance Toolkit`), positioned under `Settings`
- Disk-based page cache for anonymous GET requests
- Script defer optimization (with safe handle exclusions)
- Lazy-loading for post content images missing a `loading` attribute

## Roadmap for MVP Feature set
1. Page Cache - Static HTML cache for anonymous visitors. This is the foundation of W3 Total Cache, FlyingPress, WP Rocket, and NitroPack. W3TC specifically advertises page, object, fragment, database, minify, and CDN caching support.
2. Cache preload / warming - Build cache when posts/pages change, and optionally crawl the sitemap. FlyingPress highlights automatic cache preloading as a core feature.
3. Asset optimization -  Start with:
   minify CSS
   minify JS
   defer JS
   delay selected JS
   remove query strings optionally
   preload critical assets
4. Image/lazy-load optimization -  Lazy load images, iframes, videos, and background images. FlyingPress v5 specifically focuses on above-the-fold detection, smarter lazy loading, and background image preloading.
5. CSS delivery - Start with “load CSS async” and “critical CSS placeholder” before attempting full remove unused CSS. WP Rocket’s Remove Unused CSS feature keeps only used CSS per page, but that requires per-URL analysis and is much more complex.
6. Database cleanup - Revisions, transients, spam comments, expired options, post trash, autoloaded option analysis.
7. CDN/Cloudflare integration - Start with CDN rewrite support and Cloudflare purge. Full edge caching/CDN like NitroPack is a much bigger SaaS-style system. NitroPack positions itself as an all-in-one service with caching, image optimization, lazy loading, code optimization, and built-in CDN.

## Structure

- `pivot-performance-toolkit.php` - plugin bootstrap and lifecycle hooks
- `includes/Core` - plugin bootstrap, settings, lifecycle
- `includes/Admin` - menu router, shared shell, and dedicated admin page classes
- `includes/Cache` - page cache module
- `includes/Optimization` - frontend asset optimization module
- `includes/Media` - media/content optimization module
- `tests/smoke-settings.php` - lightweight smoke test for settings sanitization

## Local Development

```bash
cd /Users/jeffshaikh/Herd/wp-pivot-performance-toolkit/wp-content/plugins/pivot-performance-toolkit
composer install
composer dump-autoload
composer lint
composer test:smoke
```

## UI References (Local Only)

- Place paid Tailwind Plus design files in `references/ui/`.
- `references/ui/` is gitignored and intended for local design references only.
- Promote only approved, production-ready markup/styles into shipping plugin paths.


## Menu UI Structure

* Dashboard
* Cache
* File Optimization
* Media Optimization
* Database
* CDN & Integrations
* Advanced Rules
* Tools
* Documentation
* System Status

## Next Milestones

1. Add cache invalidation controls and admin purge action.
2. Add metrics panel (cache hit/miss counters and module statuses).
3. Add integration tests with a WordPress test bootstrap.


# Vendor Bundles

### For Production
```aiignore
composer install --no-dev --optimize-autoloader
```

### For Development 
```aiignore
composer install --optimize-autoloader
```
* Make sure to run `composer dump-autoload` after any changes to class files or namespaces to keep the autoloader up to date.
* Make sure to add any dev bundles to the .distignore file in the root of the project.






