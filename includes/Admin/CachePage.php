<?php
/**
 * Cache settings admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Cache\ObjectCacheManager;
use PivotPerformanceToolkit\Core\Settings;

final class CachePage extends BladeAdminPage {

	private const CLEAR_ACTION                     = 'pivot_performance_toolkit_clear_cache';
	private const CLEAR_MINIFIED_ACTION            = 'pivot_performance_toolkit_clear_minified_cache';
	private const AJAX_CLEAR_ACTION                = 'pivot_performance_toolkit_ajax_clear_cache';
	private const AJAX_CLEAR_MINIFIED_ACTION       = 'pivot_performance_toolkit_ajax_clear_minified_cache';
	private const AJAX_PRELOAD_CACHE_ACTION        = 'pivot_performance_toolkit_ajax_preload_cache';
	private const AJAX_ENABLE_OBJECT_CACHE_ACTION  = 'pivot_performance_toolkit_ajax_enable_object_cache';
	private const AJAX_DISABLE_OBJECT_CACHE_ACTION = 'pivot_performance_toolkit_ajax_disable_object_cache';
	private const AJAX_FLUSH_OBJECT_CACHE_ACTION   = 'pivot_performance_toolkit_ajax_flush_object_cache';

	private Settings $settings;
	private ObjectCacheManager $object_cache_manager;

	public function __construct( Settings $settings, ObjectCacheManager $object_cache_manager ) {
		$this->settings             = $settings;
		$this->object_cache_manager = $object_cache_manager;
		add_action( 'admin_post_' . self::CLEAR_ACTION, array( $this, 'handleClearCache' ) );
		add_action( 'admin_post_' . self::CLEAR_MINIFIED_ACTION, array( $this, 'handleClearMinifiedCache' ) );
		add_action( 'wp_ajax_' . self::AJAX_CLEAR_ACTION, array( $this, 'handleClearCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_CLEAR_MINIFIED_ACTION, array( $this, 'handleClearMinifiedCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_PRELOAD_CACHE_ACTION, array( $this, 'handlePreloadCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_ENABLE_OBJECT_CACHE_ACTION, array( $this, 'handleEnableObjectCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_DISABLE_OBJECT_CACHE_ACTION, array( $this, 'handleDisableObjectCacheAjax' ) );
		add_action( 'wp_ajax_' . self::AJAX_FLUSH_OBJECT_CACHE_ACTION, array( $this, 'handleFlushObjectCacheAjax' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-cache';
	}

	public function menuTitle(): string {
		return __( 'Cache', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Cache', 'pivot-performance-toolkit' );
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
		$options = $this->settings->all();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only "was this just saved" display flag from WordPress core's own settings-updated redirect param; used only in a strict === comparison against a hardcoded literal, never stored or output raw.
		$settings_updated = isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true';
		$cache_cleared    = (bool) get_transient( 'pivot_performance_toolkit_cache_cleared' );

		if ( $cache_cleared ) {
			delete_transient( 'pivot_performance_toolkit_cache_cleared' );
		}

		$usage_snapshot = $this->getCacheUsageSnapshot( $options );

		return array(
			'options'                          => $options,
			'settings_updated'                 => $settings_updated,
			'cache_cleared'                    => $cache_cleared,
			'cache_size'                       => $usage_snapshot['cache_size'],
			'cache_size_formatted'             => $usage_snapshot['cache_size_formatted'],
			'max_cache_bytes'                  => $usage_snapshot['max_cache_bytes'],
			'usage_pct'                        => $usage_snapshot['usage_pct'],
			'option_key'                       => $this->settings->optionKey(),
			'clear_action'                     => self::CLEAR_ACTION,
			'clear_minified_action'            => self::CLEAR_MINIFIED_ACTION,
			'ajax_clear_action'                => self::AJAX_CLEAR_ACTION,
			'ajax_clear_minified_action'       => self::AJAX_CLEAR_MINIFIED_ACTION,
			'ajax_preload_action'              => self::AJAX_PRELOAD_CACHE_ACTION,
			'ajax_clear_nonce'                 => wp_create_nonce( 'pivot_performance_toolkit_clear_cache_ajax' ),
			'ajax_clear_minified_nonce'        => wp_create_nonce( 'pivot_performance_toolkit_clear_minified_cache_ajax' ),
			'ajax_preload_nonce'               => wp_create_nonce( 'pivot_performance_toolkit_preload_cache_ajax' ),
			'cache_cleared_message'            => __( 'Cache cleared successfully.', 'pivot-performance-toolkit' ),
			'minified_cache_cleared_message'   => __( 'Minified CSS/JS cache cleared successfully.', 'pivot-performance-toolkit' ),
			'preload_cache_message'            => __( 'Preload started. This can take a moment.', 'pivot-performance-toolkit' ),
			'object_cache'                     => $this->getObjectCacheStatus(),
			'ajax_enable_object_cache_action'  => self::AJAX_ENABLE_OBJECT_CACHE_ACTION,
			'ajax_disable_object_cache_action' => self::AJAX_DISABLE_OBJECT_CACHE_ACTION,
			'ajax_flush_object_cache_action'   => self::AJAX_FLUSH_OBJECT_CACHE_ACTION,
			'ajax_enable_object_cache_nonce'   => wp_create_nonce( 'pivot_performance_toolkit_enable_object_cache_ajax' ),
			'ajax_disable_object_cache_nonce'  => wp_create_nonce( 'pivot_performance_toolkit_disable_object_cache_ajax' ),
			'ajax_flush_object_cache_nonce'    => wp_create_nonce( 'pivot_performance_toolkit_flush_object_cache_ajax' ),
		);
	}

	/**
	 * Get object cache status and details.
	 *
	 * @return array<string, mixed>
	 */
	private function getObjectCacheStatus(): array {
		$is_our_dropin = $this->object_cache_manager->isOurDropin();
		$has_foreign   = $this->object_cache_manager->hasForeignDropin();
		$is_installed  = $this->object_cache_manager->isDropinInstalled();
		$can_install   = $this->object_cache_manager->canInstall();
		$stats         = $is_our_dropin ? $this->object_cache_manager->getStats() : array();

		return array(
			'active'         => $is_installed,
			'is_our_dropin'  => $is_our_dropin,
			'has_foreign'    => $has_foreign,
			'can_enable'     => $can_install,
			'status_label'   => $is_installed ? __( 'Active', 'pivot-performance-toolkit' ) : __( 'Inactive', 'pivot-performance-toolkit' ),
			'provider'       => $this->object_cache_manager->detectProvider(),
			'dropin_label'   => $is_installed ? __( 'Installed', 'pivot-performance-toolkit' ) : __( 'Not installed', 'pivot-performance-toolkit' ),
			'entry_count'    => (int) ( $stats['entry_count'] ?? 0 ),
			'size_bytes'     => (int) ( $stats['size_bytes'] ?? 0 ),
			'size_formatted' => (string) ( $stats['size_formatted'] ?? '0 B' ),
		);
	}

	public function handleClearCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_clear_cache' );

		$this->clearPageCacheFiles();

		// Keep this notice to one redirect only.
		set_transient( 'pivot_performance_toolkit_cache_cleared', true, 30 );

		$redirect = $this->cacheSectionUrl();

		wp_safe_redirect( $redirect );
		exit;
	}

	public function handleClearMinifiedCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_clear_minified_cache' );

		$this->clearMinifiedCacheFiles();

		set_transient( 'pivot_performance_toolkit_cache_cleared', true, 30 );

		$redirect = $this->cacheSectionUrl();

		wp_safe_redirect( $redirect );
		exit;
	}

	public function handleClearCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_clear_cache_ajax' );

		$this->clearPageCacheFiles();

		wp_send_json_success(
			array(
				'message' => __( 'Cache cleared successfully.', 'pivot-performance-toolkit' ),
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	public function handleClearMinifiedCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_clear_minified_cache_ajax' );

		$this->clearMinifiedCacheFiles();

		wp_send_json_success(
			array(
				'message' => __( 'Minified CSS/JS cache cleared successfully.', 'pivot-performance-toolkit' ),
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	public function handlePreloadCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_preload_cache_ajax' );

		if ( ! $this->settings->getBool( 'enable_page_cache' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Enable page cache before running preload.', 'pivot-performance-toolkit' ),
					'usage'   => $this->getUsagePayload(),
				),
				400
			);
		}

		$summary = $this->preloadCache();

		if ( 0 === $summary['total'] ) {
			wp_send_json_error(
				array(
					'message' => __( 'No preloadable URLs found.', 'pivot-performance-toolkit' ),
					'usage'   => $this->getUsagePayload(),
				),
				400
			);
		}

		$message = sprintf(
			/* translators: 1: Successful preload requests, 2: Total preload requests, 3: Failed preload requests. */
			__( 'Preload complete: %1$d/%2$d URLs cached (%3$d failed).', 'pivot-performance-toolkit' ),
			(int) $summary['success'],
			(int) $summary['total'],
			(int) $summary['failed']
		);

		if ( ! empty( $summary['first_error'] ) ) {
			/* translators: %s: first preload failure reason. */
			$message .= ' ' . sprintf( __( 'First error: %s', 'pivot-performance-toolkit' ), (string) $summary['first_error'] );
		}

		wp_send_json_success(
			array(
				'message' => $message,
				'preload' => $summary,
				'usage'   => $this->getUsagePayload(),
			)
		);
	}

	public function handleEnableObjectCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_enable_object_cache_ajax' );

		$result = $this->object_cache_manager->install();

		if ( $result['ok'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ), 500 );
		}
	}

	public function handleDisableObjectCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_disable_object_cache_ajax' );

		$result = $this->object_cache_manager->remove();

		if ( $result['ok'] ) {
			wp_send_json_success( array( 'message' => $result['message'] ) );
		} else {
			wp_send_json_error( array( 'message' => $result['message'] ), 500 );
		}
	}

	public function handleFlushObjectCacheAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_flush_object_cache_ajax' );

		$result = $this->object_cache_manager->flush();

		wp_send_json_success( array( 'message' => $result['message'] ) );
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
		$max_urls = (int) apply_filters( 'pivot_performance_toolkit_preload_max_urls', 30 );
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
		$timeout = (float) apply_filters( 'pivot_performance_toolkit_preload_request_timeout', 8 );
		$args    = array(
			'timeout'     => max( 1.0, $timeout ),
			'redirection' => 3,
			'headers'     => array(
				'X-PTK-Preload' => '1',
			),
			'user-agent'  => 'Pivot Performance Toolkit Cache Preload',
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
						/* translators: 1: HTTP status code, 2: fallback URL that was requested */
						'error' => sprintf( __( 'HTTP fallback returned %1$d for %2$s', 'pivot-performance-toolkit' ), $fallback_code, $fallback_url ),
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
			/* translators: 1: HTTP status code, 2: URL that was requested */
			'error' => sprintf( __( 'HTTP %1$d for %2$s', 'pivot-performance-toolkit' ), $status_code, $url ),
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
		$cache_dir = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';

		foreach ( glob( $cache_dir . '/*.html' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}
	}

	private function cacheSectionUrl(): string {
		return add_query_arg(
			array(
				'page'    => 'pivot-performance-toolkit',
				'section' => 'caching',
				'tab'     => $this->slug(),
			),
			admin_url( 'admin.php' )
		);
	}

	private function clearMinifiedCacheFiles(): void {
		$cache_dir = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit/minified-assets';

		foreach ( glob( $cache_dir . '/*.min.css' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}

		foreach ( glob( $cache_dir . '/*.min.js' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
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
		$cache_dir         = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';
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
				__( '%1$s of %2$d MB used (%3$d%%)', 'pivot-performance-toolkit' ),
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
