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

final class PageCache implements ModuleInterface {

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
		// writes cache files fine via live settings. Cheap to check on every request.
		if ( ! is_file( $this->cache_dir . '/config.php' ) ) {
			$this->writeConfigFile();
		}

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
			'collect_url'    => rest_url( 'ptk/v1/performance-tests/collect' ),
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export -- not debug output: this generates a PHP-file cache (`<?php return array(...);`) that advanced-cache.php includes directly for fast, opcache-friendly config reads without bootstrapping WordPress.
		$content = "<?php\nreturn " . var_export( $config, true ) . ";\n";

		$result = file_put_contents( $this->cache_dir . '/config.php', $content, LOCK_EX );

		if ( false === $result ) {
			FilesystemCheck::invalidateCache();
		}
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

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return 'method';
		}

		if ( ! $is_probe_request && $this->hasBypassCookie() ) {
			return 'cookie_bypass';
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only used for strtok()/fnmatch() pattern matching against admin-configured exclusion rules below; never stored, output, or used in a filesystem/query context.
		$request_uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$request_path = strtok( $request_uri, '?' ) ?: '/';  // strip query string for matching

		foreach ( $this->settings->getLines( 'cache_excluded_urls' ) as $pattern ) {
			if ( false !== strpos( $pattern, '*' ) ) {
				// Wildcard pattern — e.g. /my-account/*
				if ( fnmatch( $pattern, $request_path ) ) {
					return 'excluded_url';
				}
			} else {
				// Prefix match — /checkout matches /checkout, /checkout/, /checkout/step-2
				if ( strpos( $request_path, rtrim( $pattern, '/' ) ) === 0 ) {
					return 'excluded_url';
				}
			}
		}

		return null;
	}

	private function hasBypassCookie(): bool {
		$rules = $this->settings->getLines( 'cache_bypass_cookies' );

		if ( array() === $rules || ! isset( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return false;
		}

		$cookie_names = array_keys( $_COOKIE );

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
		if ( ! isset( $_COOKIE ) || ! is_array( $_COOKIE ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only the length is checked below; the token's content is never stored, output, or otherwise used.
		$token = isset( $_COOKIE['pivot_performance_toolkit_perf_probe'] ) ? trim( (string) wp_unslash( $_COOKIE['pivot_performance_toolkit_perf_probe'] ) ) : '';

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

	private function cacheFilePath(): string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- these are only ever MD5-hashed below to build the cache file name; raw content is never stored, echoed, or used directly as a filesystem path.
		$scheme      = ( ! empty( $_SERVER['HTTPS'] ) && strtolower( (string) wp_unslash( $_SERVER['HTTPS'] ) ) !== 'off' ) ? 'https' : 'http';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : 'localhost';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$cache_key = md5( $scheme . '://' . $host . $request_uri );

		return $this->cache_dir . '/' . $cache_key . '.html';
	}
}
