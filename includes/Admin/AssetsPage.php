<?php
/**
 * Assets optimization admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;

final class AssetsPage extends BladeAdminPage {
	private const AJAX_DETECT_ASSETS_ACTION = 'pivot_performance_toolkit_ajax_detect_assets';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		add_action( 'wp_ajax_' . self::AJAX_DETECT_ASSETS_ACTION, array( $this, 'handleDetectAssetsAjax' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-assets';
	}

	public function menuTitle(): string {
		return __( 'Assets', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit Assets', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'file-code';
	}

	public function view(): string {
		return 'admin.assets-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		return array(
			'settings_updated'        => isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true',
			'content_options'         => self::getDetectableContentOptions(),
			'ajax_detect_action'      => self::AJAX_DETECT_ASSETS_ACTION,
			'ajax_detect_nonce'       => wp_create_nonce( 'pivot_performance_toolkit_assets_detect_ajax' ),
			'assets_detector_message' => __( 'Select a page or post, then run detection to list loaded assets.', 'pivot-performance-toolkit' ),
		);
	}

	public function handleDetectAssetsAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_assets_detect_ajax' );

		$target_url = isset( $_POST['target_url'] ) ? esc_url_raw( wp_unslash( (string) $_POST['target_url'] ) ) : '';

		if ( '' === $target_url ) {
			wp_send_json_error( array( 'message' => __( 'Please select a valid URL.', 'pivot-performance-toolkit' ) ), 400 );
		}

		if ( ! $this->isAllowedTargetUrl( $target_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Only URLs from this site are allowed.', 'pivot-performance-toolkit' ) ), 400 );
		}

		$response = wp_remote_get(
			$target_url,
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'sslverify'   => ! $this->isLocalEnvironment(),
				'headers'     => array(
					'X-PTK-Asset-Detector' => '1',
				),
				'user-agent'  => 'Pivot Performance Toolkit Assets Detector',
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ), 500 );
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 400 ) {
			wp_send_json_error(
				array(
					/* translators: %d: HTTP status code. */
					'message' => sprintf( __( 'Page request failed with HTTP %d.', 'pivot-performance-toolkit' ), $status_code ),
				),
				500
			);
		}

		$html = (string) wp_remote_retrieve_body( $response );

		if ( '' === trim( $html ) ) {
			wp_send_json_error( array( 'message' => __( 'No HTML was returned for this URL.', 'pivot-performance-toolkit' ) ), 500 );
		}

		$rows         = $this->extractAssetRows( $html, $target_url );
		$total_assets = count( $rows );
		$local_assets = count( array_filter( $rows, static fn( array $row ): bool => 'Local' === $row['source'] ) );
		$external     = $total_assets - $local_assets;

		wp_send_json_success(
			array(
				'rows'         => $rows,
				'summary_text' => sprintf(
					/* translators: 1: total assets, 2: local assets, 3: external assets. */
					__( 'Detected %1$d assets (%2$d local, %3$d external).', 'pivot-performance-toolkit' ),
					$total_assets,
					$local_assets,
					$external
				),
			)
		);
	}

	private function isAllowedTargetUrl( string $url ): bool {
		$target_host = wp_parse_url( $url, PHP_URL_HOST );
		$home_host   = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		return is_string( $target_host )
			&& is_string( $home_host )
			&& '' !== $target_host
			&& strtolower( $target_host ) === strtolower( $home_host );
	}

	private function isLocalEnvironment(): bool {
		$environment = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';

		if ( 'production' !== $environment ) {
			return true;
		}

		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return in_array( $host, array( 'localhost', '127.0.0.1', '::1' ), true )
			|| str_ends_with( $host, '.test' )
			|| str_ends_with( $host, '.local' );
	}

	/**
	 * @return array<int, array{type:string,url:string,source:string}>
	 */
	private function extractAssetRows( string $html, string $base_url ): array {
		$site_host = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$rows      = array();

		$script_urls = $this->extractUrlsByPattern( $html, '/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $base_url );
		$style_urls  = $this->extractUrlsByPattern( $html, '/<link[^>]+rel=["\'][^"\']*stylesheet[^"\']*["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/i', $base_url );
		$image_urls  = $this->extractUrlsByPattern( $html, '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $base_url );

		$rows = array_merge(
			$rows,
			$this->buildRowsForType( $script_urls, 'Script', $site_host ),
			$this->buildRowsForType( $style_urls, 'Stylesheet', $site_host ),
			$this->buildRowsForType( $image_urls, 'Image', $site_host )
		);

		return $rows;
	}

	/**
	 * @return string[]
	 */
	private function extractUrlsByPattern( string $html, string $pattern, string $base_url ): array {
		$matches = array();
		$urls    = array();

		if ( preg_match_all( $pattern, $html, $matches ) && isset( $matches[1] ) && is_array( $matches[1] ) ) {
			foreach ( $matches[1] as $raw_url ) {
				$normalized = $this->normalizeAssetUrl( (string) $raw_url, $base_url );

				if ( '' !== $normalized ) {
					$urls[] = $normalized;
				}
			}
		}

		return array_values( array_unique( $urls ) );
	}

	private function normalizeAssetUrl( string $url, string $base_url ): string {
		$url = trim( $url );

		if ( '' === $url || str_starts_with( $url, 'data:' ) || str_starts_with( $url, '#' ) ) {
			return '';
		}

		if ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) ) {
			return esc_url_raw( $url );
		}

		$base_parts = wp_parse_url( $base_url );

		if ( ! is_array( $base_parts ) || empty( $base_parts['host'] ) ) {
			return '';
		}

		$scheme = isset( $base_parts['scheme'] ) ? (string) $base_parts['scheme'] : 'https';
		$host   = (string) $base_parts['host'];

		if ( str_starts_with( $url, '//' ) ) {
			return esc_url_raw( $scheme . ':' . $url );
		}

		if ( str_starts_with( $url, '/' ) ) {
			return esc_url_raw( $scheme . '://' . $host . $url );
		}

		$base_path = isset( $base_parts['path'] ) ? (string) $base_parts['path'] : '/';
		$base_dir  = trailingslashit( (string) dirname( $base_path ) );

		return esc_url_raw( $scheme . '://' . $host . $base_dir . ltrim( $url, '/' ) );
	}

	/**
	 * @param string[] $urls
	 * @return array<int, array{type:string,url:string,source:string,category:string}>
	 */
	private function buildRowsForType( array $urls, string $type, string $site_host ): array {
		$rows = array();

		foreach ( $urls as $url ) {
			$host     = (string) wp_parse_url( $url, PHP_URL_HOST );
			$is_local = '' !== $host && strtolower( $host ) === strtolower( $site_host );
			$source   = $is_local ? 'Local' : 'External';
			$category = $is_local
				? $this->categorizeLocalUrl( (string) wp_parse_url( $url, PHP_URL_PATH ) )
				: 'External';

			$rows[] = array(
				'type'     => $type,
				'url'      => $url,
				'source'   => $source,
				'category' => $category,
			);
		}

		return $rows;
	}

	private function categorizeLocalUrl( string $path ): string {
		if ( str_contains( $path, '/wp-includes/' ) ) {
			return 'Core';
		}

		if ( str_contains( $path, '/wp-content/themes/' ) ) {
			return 'Theme';
		}

		if ( str_contains( $path, '/wp-content/plugins/' ) ) {
			return 'Plugin';
		}

		if ( str_contains( $path, '/wp-admin/' ) ) {
			return 'Admin';
		}

		return 'Other';
	}

	/**
	 * @return array{pages:array<int,array{label:string,url:string}>,posts:array<int,array{label:string,url:string}>}
	 */
	private static function getDetectableContentOptions(): array {
		$types  = array(
			'pages' => 'page',
			'posts' => 'post',
		);
		$result = array(
			'pages' => array(),
			'posts' => array(),
		);

		$front_page_id = 0;
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );
		}

		foreach ( $types as $bucket => $post_type ) {
			$exclude = ( 'pages' === $bucket && $front_page_id > 0 ) ? array( $front_page_id ) : array();
			$items   = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'exclude'        => $exclude,
				)
			);

			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}

			foreach ( $items as $post_id ) {
				$url = get_permalink( (int) $post_id );

				if ( ! is_string( $url ) || '' === $url ) {
					continue;
				}

				$title = get_the_title( (int) $post_id );

				$result[ $bucket ][] = array(
					/* translators: %d: post ID. */
					'label' => is_string( $title ) && '' !== trim( $title ) ? $title : sprintf( __( 'Untitled #%d', 'pivot-performance-toolkit' ), (int) $post_id ),
					'url'   => $url,
				);
			}
		}

		return $result;
	}
}
