<?php
/**
 * Cache settings admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;

final class CachePage extends BladeAdminPage {

	private const CLEAR_ACTION               = 'performance_toolkit_clear_cache';
	private const CLEAR_MINIFIED_ACTION      = 'performance_toolkit_clear_minified_cache';
	private const AJAX_CLEAR_ACTION          = 'performance_toolkit_ajax_clear_cache';
	private const AJAX_CLEAR_MINIFIED_ACTION = 'performance_toolkit_ajax_clear_minified_cache';
	private const AJAX_REFRESH_USAGE_ACTION  = 'performance_toolkit_ajax_refresh_cache_usage';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		add_action( 'admin_post_' . self::CLEAR_ACTION, array( $this, 'handleClearCache' ) );
		add_action( 'admin_post_' . self::CLEAR_MINIFIED_ACTION, array( $this, 'handleClearMinifiedCache' ) );
		add_action( 'wp_ajax_' . self::AJAX_CLEAR_ACTION, array( $this, 'handleClearCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_CLEAR_MINIFIED_ACTION, array( $this, 'handleClearMinifiedCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_REFRESH_USAGE_ACTION, array( $this, 'handleRefreshCacheUsageAjax' ) );
	}

	public function slug(): string {
		return 'performance-toolkit-cache';
	}

	public function menuTitle(): string {
		return __( 'Cache', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit Cache', 'performance-toolkit' );
	}

	public function iconKey(): string {
		return 'rocket';
	}

	public function view(): string {
		return 'admin.cache-page';
	}

	/**
	 * Gather all data needed for the view.
	 *
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$options          = $this->settings->all();
		$settings_updated = isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true';
		$cache_cleared    = (bool) get_transient( 'performance_toolkit_cache_cleared' );

		if ( $cache_cleared ) {
			delete_transient( 'performance_toolkit_cache_cleared' );
		}

		$usage_snapshot = $this->getCacheUsageSnapshot( $options );
		$object_cache   = $this->getObjectCacheStatus();

		return array(
			'options'                         => $options,
			'settings_updated'                => $settings_updated,
			'cache_cleared'                   => $cache_cleared,
			'cache_size'                      => $usage_snapshot['cache_size'],
			'cache_size_formatted'            => $usage_snapshot['cache_size_formatted'],
			'max_cache_bytes'                 => $usage_snapshot['max_cache_bytes'],
			'usage_pct'                       => $usage_snapshot['usage_pct'],
			'option_key'                      => $this->settings->optionKey(),
			'clear_action'                    => self::CLEAR_ACTION,
			'clear_minified_action'           => self::CLEAR_MINIFIED_ACTION,
			'ajax_clear_action'               => self::AJAX_CLEAR_ACTION,
			'ajax_clear_minified_action'      => self::AJAX_CLEAR_MINIFIED_ACTION,
			'ajax_refresh_usage_action'       => self::AJAX_REFRESH_USAGE_ACTION,
			'ajax_clear_nonce'                => wp_create_nonce( 'ptk_clear_cache_ajax' ),
			'ajax_clear_minified_nonce'       => wp_create_nonce( 'ptk_clear_minified_cache_ajax' ),
			'ajax_refresh_usage_nonce'        => wp_create_nonce( 'ptk_refresh_cache_usage_ajax' ),
			'cache_cleared_message'           => __( 'Cache cleared successfully.', 'performance-toolkit' ),
			'minified_cache_cleared_message'  => __( 'Minified CSS/JS cache cleared successfully.', 'performance-toolkit' ),
			'preload_not_implemented_message' => __( 'Preload started. This can take a moment.', 'performance-toolkit' ),
			'object_cache'                    => $object_cache,
		);
	}

	/**
	 * Get object cache status and details.
	 *
	 * @return array<string, mixed>
	 */
	private function getObjectCacheStatus(): array {
		$dropin_path = WP_CONTENT_DIR . '/object-cache.php';

		return array(
			'active'         => file_exists( $dropin_path ),
			'status_label'   => file_exists( $dropin_path ) ? __( 'Active', 'performance-toolkit' ) : __( 'Inactive', 'performance-toolkit' ),
			'provider'       => defined( 'WP_REDIS_CLUSTER' ) ? 'Redis Cluster' : ( defined( 'WP_REDIS_HOST' ) ? 'Redis' : __( 'Unknown', 'performance-toolkit' ) ),
			'dropin_label'   => file_exists( $dropin_path ) ? __( 'Installed', 'performance-toolkit' ) : __( 'Not installed', 'performance-toolkit' ),
			'size_bytes'     => wp_cache_get( '_stats', '' )['bytes'] ?? 0,
			'size_formatted' => self::formatBytes( wp_cache_get( '_stats', '' )['bytes'] ?? 0 ),
		);
	}

	public function handleClearCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'performance-toolkit' ) );
		}

		check_admin_referer( 'ptk_clear_cache' );

		$this->clearPageCacheFiles();

		// Keep this notice to one redirect only.
		set_transient( 'performance_toolkit_cache_cleared', true, 30 );

		$redirect = $this->cacheSectionUrl();

		wp_safe_redirect( $redirect );
		exit;
	}

	public function handleClearMinifiedCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'performance-toolkit' ) );
		}

		check_admin_referer( 'ptk_clear_minified_cache' );

		$this->clearMinifiedCacheFiles();

		set_transient( 'performance_toolkit_cache_cleared', true, 30 );

		$redirect = $this->cacheSectionUrl();

		wp_safe_redirect( $redirect );
		exit;
	}

	public function handleClearCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'ptk_clear_cache_ajax' );

		$this->clearPageCacheFiles();

		wp_send_json_success(
			array(
				'message' => __( 'Cache cleared successfully.', 'performance-toolkit' ),
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	public function handleClearMinifiedCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'ptk_clear_minified_cache_ajax' );

		$this->clearMinifiedCacheFiles();

		wp_send_json_success(
			array(
				'message' => __( 'Minified CSS/JS cache cleared successfully.', 'performance-toolkit' ),
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	public function handleRefreshCacheUsageAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'ptk_refresh_cache_usage_ajax' );

		if ( ! $this->settings->getBool( 'enable_page_cache' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Enable page cache before running preload.', 'performance-toolkit' ),
					'usage'   => $this->getUsagePayload(),
				),
				400
			);
		}

		$summary = $this->preloadCache();

		if ( 0 === $summary['total'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'No preloadable URLs found.', 'performance-toolkit' ),
					'usage'   => $this->getUsagePayload(),
				),
				400
			);
		}

		$message = sprintf(
			/* translators: 1: Successful preload requests, 2: Total preload requests, 3: Failed preload requests. */
			__( 'Preload complete: %1$d/%2$d URLs cached (%3$d failed).', 'performance-toolkit' ),
			(int) $summary['success'],
			(int) $summary['total'],
			(int) $summary['failed']
		);

		if ( ! empty( $summary['first_error'] ) ) {
			/* translators: %s: first preload failure reason. */
			$message .= ' ' . sprintf( __( 'First error: %s', 'performance-toolkit' ), (string) $summary['first_error'] );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'preload' => $summary,
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	/**
	 * Warm cache files by requesting a list of internal URLs.
	 *
	 * @return array<string, int|string>
	 */
	private function preloadCache(): array {
		$urls        = $this->buildPreloadUrls();
		$success     = 0;
		$failed      = 0;
		$first_error = '';

		foreach ( $urls as $url ) {
			$result = $this->preloadUrl( $url );

			if ( ! empty( $result['ok'] ) ) {
				++$success;
				continue;
			}

			++$failed;

			if ( '' === $first_error && ! empty( $result['error'] ) ) {
				$first_error = (string) $result['error'];
			}
		}

		return array(
			'total'       => count( $urls ),
			'success'     => $success,
			'failed'      => $failed,
			'first_error' => $first_error,
		);
	}

	/**
	 * Build a deterministic list of internal URLs to warm.
	 *
	 * @return string[]
	 */
	private function buildPreloadUrls(): array {
		$max_urls = (int) apply_filters( 'performance_toolkit_preload_max_urls', 30 );
		$max_urls = max( 1, min( 200, $max_urls ) );

		$urls = array( home_url( '/' ) );

		$front_page_id = (int) get_option( 'page_on_front' );
		$posts_page_id = (int) get_option( 'page_for_posts' );

		if ( $front_page_id > 0 ) {
			$front_url = get_permalink( $front_page_id );
			if ( is_string( $front_url ) && '' !== $front_url ) {
				$urls[] = $front_url;
			}
		}

		if ( $posts_page_id > 0 ) {
			$posts_url = get_permalink( $posts_page_id );
			if ( is_string( $posts_url ) && '' !== $posts_url ) {
				$urls[] = $posts_url;
			}
		}

		$post_ids = get_posts(
			array(
				'post_type'           => array( 'page', 'post' ),
				'post_status'         => 'publish',
				'fields'              => 'ids',
				'posts_per_page'      => $max_urls,
				'orderby'             => 'modified',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		if ( is_array( $post_ids ) ) {
			foreach ( $post_ids as $post_id ) {
				$post_url = get_permalink( (int) $post_id );

				if ( is_string( $post_url ) && '' !== $post_url ) {
					$urls[] = $post_url;
				}
			}
		}

		$urls = array_slice( array_values( array_unique( $urls ) ), 0, $max_urls );

		return array_values(
			array_filter(
				$urls,
				static function ( $url ): bool {
					if ( ! is_string( $url ) || '' === $url ) {
						return false;
					}

					$host = wp_parse_url( $url, PHP_URL_HOST );
					return is_string( $host ) && wp_parse_url( home_url( '/' ), PHP_URL_HOST ) === $host;
				}
			)
		);
	}

	/**
	 * @return array{ok: bool, error: string}
	 */
	private function preloadUrl( string $url ): array {
		$timeout = (float) apply_filters( 'performance_toolkit_preload_request_timeout', 8 );
		$args    = array(
			'timeout'     => max( 1.0, $timeout ),
			'redirection' => 3,
			'headers'     => array(
				'X-PTK-Preload' => '1',
			),
			'user-agent'  => 'Performance Toolkit Cache Preload',
			'sslverify'   => ! $this->isLocalOrNonProductionSite( $url ),
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();

			// Local HTTPS setups commonly fail on loopback cert verification.
			if ( str_starts_with( $url, 'https://' ) ) {
				$fallback_url      = 'http://' . substr( $url, 8 );
				$fallback_response = wp_remote_get( $fallback_url, $args );

				if ( ! is_wp_error( $fallback_response ) ) {
					$fallback_code = (int) wp_remote_retrieve_response_code( $fallback_response );

					if ( $fallback_code >= 200 && $fallback_code < 400 ) {
						return array(
							'ok'    => true,
							'error' => '',
						);
					}

					return array(
						'ok'    => false,
						'error' => sprintf( 'HTTP fallback returned %d for %s', $fallback_code, $fallback_url ),
					);
				}
			}

			return array(
				'ok'    => false,
				'error' => sprintf( '%s (%s)', (string) $error_message, $url ),
			);
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code >= 200 && $status_code < 400 ) {
			return array(
				'ok'    => true,
				'error' => '',
			);
		}

		return array(
			'ok'    => false,
			'error' => sprintf( 'HTTP %d for %s', $status_code, $url ),
		);
	}

	private function isLocalOrNonProductionSite( string $url ): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' !== $environment ) {
			return true;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true )
			|| str_ends_with( $host, '.test' )
			|| str_ends_with( $host, '.local' );
	}

	private function clearPageCacheFiles(): void {
		$cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit';

		foreach ( glob( $cache_dir . '/*.html' ) ?: array() as $file_path ) {
			@unlink( $file_path );
		}
	}

	private function cacheSectionUrl(): string {
		return add_query_arg(
			array(
				'page'    => 'performance-toolkit',
				'section' => 'caching',
				'tab'     => $this->slug(),
			),
			admin_url( 'admin.php' )
		);
	}

	private function clearMinifiedCacheFiles(): void {
		$cache_dir = WP_CONTENT_DIR . '/cache/performance-toolkit/minified-assets';

		foreach ( glob( $cache_dir . '/*.min.css' ) ?: array() as $file_path ) {
			@unlink( $file_path );
		}

		foreach ( glob( $cache_dir . '/*.min.js' ) ?: array() as $file_path ) {
			@unlink( $file_path );
		}
	}

	/**
	 * Get the total size of a directory in bytes.
	 */
	private function getCacheDirSize( string $dir ): int {
		$total = 0;

		foreach ( glob( $dir . '/*.html' ) ?: array() as $file ) {
			$total += (int) @filesize( $file );
		}

		return $total;
	}

	/**
	 * @param array<string, mixed> $options
	 * @return array<string, int|string>
	 */
	private function getCacheUsageSnapshot( array $options ): array {
		$cache_dir         = WP_CONTENT_DIR . '/cache/performance-toolkit';
		$cache_size        = $this->getCacheDirSize( $cache_dir );
		$max_cache_size_mb = (int) ( $options['max_cache_size_mb'] ?? 0 );
		$max_cache_bytes   = $max_cache_size_mb * 1048576;
		$usage_pct         = $max_cache_bytes > 0 ? min( 100, (int) round( $cache_size / $max_cache_bytes * 100 ) ) : 0;

		return array(
			'cache_size'           => $cache_size,
			'cache_size_formatted' => self::formatBytes( $cache_size ),
			'max_cache_bytes'      => $max_cache_bytes,
			'max_cache_size_mb'    => $max_cache_size_mb,
			'usage_pct'            => $usage_pct,
			'cache_usage_label'    => sprintf(
				/* translators: 1: Used cache size in human-readable units, 2: Configured max cache size in MB, 3: Percentage of cache usage. */
				__( '%1$s of %2$d MB used (%3$d%%)', 'performance-toolkit' ),
				self::formatBytes( $cache_size ),
				$max_cache_size_mb,
				$usage_pct
			),
		);
	}

	/**
	 * @return array<string, int|string>
	 */
	private function getUsagePayload(): array {
		$snapshot = $this->getCacheUsageSnapshot( $this->settings->all() );

		return array(
			'usage_pct'         => (int) $snapshot['usage_pct'],
			'cache_usage_label' => (string) $snapshot['cache_usage_label'],
		);
	}

	/**
	 * Format bytes into human-readable format.
	 */
	public static function formatBytes( int $bytes ): string {
		if ( $bytes >= 1048576 ) {
			return number_format( $bytes / 1048576, 2 ) . ' MB';
		}

		if ( $bytes >= 1024 ) {
			return number_format( $bytes / 1024, 2 ) . ' KB';
		}

		return $bytes . ' B';
	}
}
