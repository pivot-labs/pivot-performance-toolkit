<p align="center">
  <img src=".wordpress-org/banner-1544x500.png" alt="Pivot Performance Toolkit — safe-by-default WordPress performance: page caching, minification, lazy loading, and Cloudflare integration." width="100%">
</p>





# Pivot Performance Toolkit

A modular WordPress plugin focused on practical, safe-by-default performance optimizations: page caching, file optimization, media/lazy-loading, database cleanup, and Cloudflare integration.

## Features

- Disk-based page cache for anonymous GET requests, with TTL and max-size controls
- HTML minification, and inline/external CSS and JavaScript minification
- Script defer support, with safe built-in exclusions
- Native image lazy-loading
- Cloudflare integration: connection test, cache purge, and auto-purge on content update
- Database cleanup tools: revisions, auto-drafts, trashed posts/comments, spam comments, expired transients, table optimization
- Import/export and cache-clearing utilities

See [readme.txt](readme.txt) for the full WordPress.org plugin description, FAQ, and changelog.

## Requirements

- WordPress 6.5+
- PHP 8.2+

## Structure

- `pivot-performance-toolkit.php` — plugin bootstrap and lifecycle hooks
- `includes/Core` — plugin bootstrap, settings, lifecycle
- `includes/Admin` — menu router, shared shell, and dedicated admin page classes
- `includes/Cache` — page cache module
- `includes/Optimization` — frontend asset optimization module
- `includes/Media` — media/content optimization module
- `includes/Database` — database cleanup module
- `includes/Integrations` — Cloudflare integration
- `tests/smoke-settings.php` — lightweight smoke test for settings sanitization

## Building from Source

The shipped plugin includes compiled admin assets (`dist/admin.css`, `dist/admin-js.js`) and Composer-vendored, Strauss-prefixed PHP dependencies (`includes/Vendor/`). To build both from source:

```bash
composer install
npm install
npm run build
```

Re-run `composer dump-autoload` after adding or renaming classes.

## Development

```bash
composer lint
composer test:smoke
```

## License

GPL-2.0-or-later. See [license.txt](license.txt).
