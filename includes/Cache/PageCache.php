<?php
/**
 * Frontend full-page cache handler.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\FilesystemCheck;
use PivotPerformanceToolkit\Utils\RequestInput;

final class PageCache implements ModuleInterface {

	private const CONFIG_VERSION_OPTION = 'pivot_performance_toolkit_cache_config_version';

	private Settings $settings;

	private string $cache_dir;

	public function __construct( Settings $settings ) {
		$this->settings  = $settings;
		$this->cache_dir = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';
	}

	public function register(): void {
		// Self-heal installs activated before Lifecycle::activate() started writing
		// this file (or where it was otherwise deleted): without it, advanced-cache.php
		// has nothing to read and silently never serves a HIT, even though this class
		// writes cache files fine via live settings. It is also rewritten after an update,
		// because new releases add keys to it. Done on `init`, not here: building the
		// config calls rest_url(), which needs the rewrite rules that exist only from then.
		add_action( 'init', array( $this, 'refreshStaleConfig' ) );

		// Cache writing — fires once the full page HTML is finalized, right
		// before it's sent to the browser (core's docs describe this action
		// as "complimentary to send_headers": headers may still be sent
		// here). Registered as an action, not a filter, since this class
		// only observes/stores the final output — it never needs to modify
		// it — so it always sees whatever Combine/AsyncCss/DelayedJs/Assets
		// (if active) already transformed, same as when this ran as the
		// outermost of five nested ob_start() calls.
		add_action( 'wp_finalized_template_enhancement_output_buffer', array( $this, 'maybeCacheOutput' ) );

		// Invalidate cache when content changes.
		add_action( 'save_post', array( $this, 'purgeAll' ) );
		add_action( 'deleted_post', array( $this, 'purgeAll' ) );

		// Refresh the flat config file whenever settings are saved.
		add_action( 'update_option_' . $this->settings->optionKey(), array( $this, 'writeConfigFile' ) );
	}

	public function refreshStaleConfig(): void {
		if ( ! is_file( $this->cache_dir . '/config.php' ) || PIVOT_PERFORMANCE_TOOLKIT_VERSION !== get_option( self::CONFIG_VERSION_OPTION ) ) {
			$this->writeConfigFile();
		}
	}

	public function maybeCacheOutput( string $html ): void {
		if ( ! $this->settings->getBool( 'enable_page_cache' ) ) {
			$this->sendDebugHeaders( 'BYPASS', 'disabled' );
			return;
		}

		$bypass_reason = $this->bypassReason();

		if ( null !== $bypass_reason ) {
			$this->sendDebugHeaders( 'BYPASS', $bypass_reason );
			return;
		}

		// Probe requests (from the performance test) must never write to the shared
		// page cache. The wp_footer metrics-collection script it renders is specific
		// to this one test run; if captured into the cache file, that script would
		// re-execute on every future serve of the file and race against (and often
		// overwrite) the correct cache-hit result injected fresh by the advanced-cache
		// drop-in. Real anonymous visits are unaffected and still populate the cache.
		if ( $this->isProbeRequest() ) {
			$this->sendDebugHeaders( 'MISS', 'probe_no_write' );
			return;
		}

		if ( ! file_exists( $this->cache_dir ) ) {
			wp_mkdir_p( $this->cache_dir );
		}

		// Check if cache directory is writable; if not, skip caching but don't break the site
		if ( ! FilesystemCheck::isDirectoryWritable( $this->cache_dir ) ) {
			$this->sendDebugHeaders( 'BYPASS', 'fs_readonly' );
			FilesystemCheck::invalidateCache();
			return;
		}

		if ( '' === $html ) {
			return;
		}

		$cache_file = $this->cacheFilePath();
		$written    = file_put_contents( $cache_file, $html, LOCK_EX );

		if ( false === $written ) {
			$this->sendDebugHeaders( 'BYPASS', 'fs_write_failed' );
			FilesystemCheck::invalidateCache();
		} else {
			$this->sendDebugHeaders( 'MISS' );
		}
	}

	public function purgeAll(): void {
		if ( ! is_dir( $this->cache_dir ) ) {
			return;
		}

		foreach ( glob( $this->cache_dir . '/*.html' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Write a flat PHP config file that the advanced-cache.php drop-in
	 * can read before WordPress is fully loaded.
	 */
	public function writeConfigFile(): void {
		if ( ! file_exists( $this->cache_dir ) ) {
			wp_mkdir_p( $this->cache_dir );
		}

		// Check writeability before attempting write
		if ( ! FilesystemCheck::isDirectoryWritable( $this->cache_dir ) ) {
			FilesystemCheck::invalidateCache();
			return;
		}

		$config = array(
			'enabled'        => $this->settings->getBool( 'enable_page_cache' ),
			'ttl'            => $this->settings->getInt( 'cache_ttl' ),
			'bypass_cookies' => $this->settings->getLines( 'cache_bypass_cookies' ),
			'collect_url'    => rest_url( 'pivot-performance-toolkit/v1/performance-tests/collect' ),
			'probe_script'   => set_url_scheme( PIVOT_PERFORMANCE_TOOLKIT_URL . 'assets/js/performance-probe.js?ver=' . PIVOT_PERFORMANCE_TOOLKIT_VERSION, wp_parse_url( home_url(), PHP_URL_SCHEME ) ), // The site's own scheme: this may run from WP-CLI, where is_ssl() is false.
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- not debug output: this generates a PHP-file cache (`<?php return array(...);`) that advanced-cache.php includes directly for fast, opcache-friendly config reads without bootstrapping WordPress.
		$content = "<?php\nreturn " . var_export( $config, true ) . ";\n";

		$result = file_put_contents( $this->cache_dir . '/config.php', $content, LOCK_EX );

		if ( false === $result ) {
			FilesystemCheck::invalidateCache();
			return;
		}

		update_option( self::CONFIG_VERSION_OPTION, PIVOT_PERFORMANCE_TOOLKIT_VERSION, false );
	}

	private function bypassReason(): ?string {
		$is_probe_request = $this->isProbeRequest();

		if ( is_admin() || ( is_user_logged_in() && ! $is_probe_request ) || is_preview() || is_feed() || is_404() ) {
			if ( is_admin() ) {
				return 'admin';
			}

			if ( is_user_logged_in() && ! $is_probe_request ) {
				return 'logged_in';
			}

			if ( is_preview() ) {
				return 'preview';
			}

			if ( is_feed() ) {
				return 'feed';
			}

			return '404';
		}

		if ( ! RequestInput::isGetRequest() ) {
			return 'method';
		}

		if ( ! $is_probe_request && $this->hasBypassCookie() ) {
			return 'cookie_bypass';
		}

		$request_uri  = RequestInput::keyPart( 'REQUEST_URI', '/' );
		$request_path = strtok( $request_uri, '?' ) ?: '/';  // strip query string for matching

		if ( $this->isExcludedPath( $request_path ) ) {
			return 'excluded_url';
		}

		return null;
	}

	/**
	 * Whether a URL path matches one of the "never cache" rules.
	 */
	private function isExcludedPath( string $request_path ): bool {
		foreach ( $this->settings->getLines( 'cache_excluded_urls' ) as $pattern ) {
			if ( false !== strpos( $pattern, '*' ) ) {
				// Wildcard pattern — e.g. /my-account/*
				if ( fnmatch( $pattern, $request_path ) ) {
					return true;
				}
			} elseif ( strpos( $request_path, rtrim( $pattern, '/' ) ) === 0 ) {
				// Prefix match — /checkout matches /checkout, /checkout/, /checkout/step-2
				return true;
			}
		}

		return false;
	}

	private function hasBypassCookie(): bool {
		$rules = $this->settings->getLines( 'cache_bypass_cookies' );

		$cookie_names = RequestInput::cookieNames();

		if ( array() === $rules || array() === $cookie_names ) {
			return false;
		}

		foreach ( $rules as $rule ) {
			$rule = trim( $rule );

			if ( '' === $rule ) {
				continue;
			}

			foreach ( $cookie_names as $cookie_name ) {
				if ( ! is_string( $cookie_name ) || '' === $cookie_name ) {
					continue;
				}

				if ( str_contains( $rule, '*' ) ) {
					if ( fnmatch( $rule, $cookie_name ) ) {
						return true;
					}

					continue;
				}

				if ( 0 === strcasecmp( $rule, $cookie_name ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private function isProbeRequest(): bool {
		$token = trim( RequestInput::cookie( 'pivot_performance_toolkit_perf_probe' ) );

		return strlen( $token ) >= 20;
	}

	private function sendDebugHeaders( string $status, ?string $reason = null ): void {
		if ( headers_sent() ) {
			return;
		}

		header( 'X-Pivot-Cache: ' . $status );

		if ( null !== $reason && '' !== $reason ) {
			header( 'X-Pivot-Cache-Reason: ' . $reason );
		}
	}

	/**
	 * Make sure a page of this site is in the page cache, for the performance test.
	 *
	 * A test request never writes to the cache (see maybeCacheOutput()), so after the
	 * cache is cleared the test would report a miss and keep doing so until a visitor
	 * or the preload fills it. When the cache is on and the page is cacheable, this
	 * requests the page once, anonymously, which stores it exactly as a visitor's
	 * request would. The caller has already checked that $url is on this site's host.
	 *
	 * @return string 'disabled' (page cache off), 'excluded' (the page is never cached),
	 *                'cached' (already in the cache), 'warmed' (cached by this call) or
	 *                'failed' (the request did not fill the cache).
	 */
	public function warmUrl( string $url ): string {
		if ( ! $this->settings->getBool( 'enable_page_cache' ) ) {
			return 'disabled';
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );

		if ( $this->isExcludedPath( '' !== $path ? $path : '/' ) ) {
			return 'excluded';
		}

		if ( $this->hasFreshCacheFile( $url ) ) {
			return 'cached';
		}

		LoopbackRequest::fetch( $url );

		return $this->hasFreshCacheFile( $url ) ? 'warmed' : 'failed';
	}

	private function hasFreshCacheFile( string $url ): bool {
		$file = $this->cacheFilePathForUrl( $url );

		if ( ! is_file( $file ) ) {
			return false;
		}

		return ( (int) filemtime( $file ) + $this->settings->getInt( 'cache_ttl' ) ) >= time();
	}

	/**
	 * The cache file a request for $url would use. Same key as cacheFilePath() and the
	 * advanced-cache.php drop-in: scheme://host[:port]request-uri, filtered the same way.
	 */
	private function cacheFilePathForUrl( string $url ): string {
		$parts  = wp_parse_url( $url );
		$scheme = isset( $parts['scheme'] ) && 'https' === strtolower( (string) $parts['scheme'] ) ? 'https' : 'http';
		$host   = isset( $parts['host'] ) ? (string) $parts['host'] : 'localhost';

		if ( isset( $parts['port'] ) ) {
			$host .= ':' . (int) $parts['port'];
		}

		$uri = ( isset( $parts['path'] ) && '' !== $parts['path'] ? (string) $parts['path'] : '/' )
			. ( isset( $parts['query'] ) && '' !== $parts['query'] ? '?' . $parts['query'] : '' );

		$cache_key = md5( $scheme . '://' . RequestInput::printableAscii( $host ) . RequestInput::printableAscii( $uri ) );

		return $this->cache_dir . '/' . $cache_key . '.html';
	}

	private function cacheFilePath(): string {
		$scheme      = RequestInput::isHttps() ? 'https' : 'http';
		$host        = RequestInput::keyPart( 'HTTP_HOST', 'localhost' );
		$request_uri = RequestInput::keyPart( 'REQUEST_URI', '/' );
		$cache_key   = md5( $scheme . '://' . $host . $request_uri );

		return $this->cache_dir . '/' . $cache_key . '.html';
	}
}
