<?php
/**
 * Admin bar menu.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Views\BladeEngine;
use WP_Admin_Bar;

final class AdminBarMenu implements ModuleInterface {

	private const PURGE_ACTION = 'pivot_performance_toolkit_purge_all_cache';

	private const PURGE_PAGE_ACTION = 'pivot_performance_toolkit_purge_page_cache';

	private const PAGE_CACHE_DIR = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';

	private const MINIFIED_CACHE_DIR = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit/minified-assets';

	public function register(): void {
		add_action( 'admin_bar_menu', array( $this, 'registerMenu' ), 100 );
		add_action( 'admin_post_' . self::PURGE_ACTION, array( $this, 'handlePurgeAllCache' ) );
		add_action( 'admin_post_' . self::PURGE_PAGE_ACTION, array( $this, 'handlePurgePageCache' ) );
		add_action( 'admin_notices', array( $this, 'renderAdminNotice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminBarStyles' ) );
	}

	/**
	 * Styles the admin-bar cache-status indicator (registerMenu() below).
	 * Same scope as the original inline <style> it replaces: every admin
	 * page for a manage_options user, via a source-less style handle rather
	 * than an echoed <style> tag.
	 */
	public function enqueueAdminBarStyles(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_register_style( 'pivot-performance-toolkit-admin-bar', false );
		wp_enqueue_style( 'pivot-performance-toolkit-admin-bar' );
		wp_add_inline_style(
			'pivot-performance-toolkit-admin-bar',
			'#wpadminbar .pivot-performance-toolkit-disabled{opacity:0.5;pointer-events:none;cursor:not-allowed}'
			. '#wpadminbar .pivot-performance-toolkit-cache-status{font-weight:600}'
			. '#wpadminbar .pivot-performance-toolkit-cache-status-hit{color:#7bd88f}'
			. '#wpadminbar .pivot-performance-toolkit-cache-status-miss{color:#ffce6a}'
			. '#wpadminbar .pivot-performance-toolkit-cache-status-bypass{color:#a7aaad}'
		);
	}

	public function registerMenu( WP_Admin_Bar $admin_bar ): void {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=pivot-performance-toolkit' );
		$purge_url    = wp_nonce_url(
			admin_url( 'admin-post.php?action=' . self::PURGE_ACTION ),
			'pivot_performance_toolkit_adminbar_purge_all_cache'
		);

		$admin_bar->add_node(
			array(
				'id'    => 'pivot-performance-toolkit',
				'title' => __( 'Performance', 'pivot-performance-toolkit' ),
				'href'  => $settings_url,
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'pivot-performance-toolkit-settings',
				'parent' => 'pivot-performance-toolkit',
				'title'  => __( 'Settings', 'pivot-performance-toolkit' ),
				'href'   => $settings_url,
			)
		);

		$admin_bar->add_node(
			array(
				'id'     => 'pivot-performance-toolkit-purge-all-cache',
				'parent' => 'pivot-performance-toolkit',
				'title'  => __( 'Purge all cache', 'pivot-performance-toolkit' ),
				'href'   => $purge_url,
			)
		);

		$status = $this->getCurrentPageCacheStatus();

		$admin_bar->add_node(
			array(
				'id'     => 'pivot-performance-toolkit-page-cache-status',
				'parent' => 'pivot-performance-toolkit',
				'title'  => sprintf(
					/* translators: %s: cache status label */
					__( 'Page cache: %s', 'pivot-performance-toolkit' ),
					'<span class="pivot-performance-toolkit-cache-status ' . esc_attr( $status['class'] ) . '">' . esc_html( $status['label'] ) . '</span>'
				),
				'href'   => false,
				'meta'   => array(
					'html' => '',
				),
			)
		);

		$purge_page_url = '#';

		if ( $status['cacheable'] && is_string( $status['url'] ) && '' !== $status['url'] ) {
			$purge_page_url = wp_nonce_url(
				add_query_arg(
					array(
						'action'                           => self::PURGE_PAGE_ACTION,
						'pivot_performance_toolkit_target' => rawurlencode( $status['url'] ),
					),
					admin_url( 'admin-post.php' )
				),
				'pivot_performance_toolkit_adminbar_purge_page_cache'
			);
		}

		$admin_bar->add_node(
			array(
				'id'     => 'pivot-performance-toolkit-purge-page-cache',
				'parent' => 'pivot-performance-toolkit',
				'title'  => __( 'Purge this page', 'pivot-performance-toolkit' ),
				'href'   => $status['cacheable'] ? $purge_page_url : '#',
				'meta'   => array(
					'class' => $status['cacheable'] ? '' : 'pivot-performance-toolkit-disabled',
					'title' => $status['cacheable'] ? __( 'Purge cache for this page', 'pivot-performance-toolkit' ) : __( 'Not on a cacheable page', 'pivot-performance-toolkit' ),
				),
			)
		);
	}

	private function isCurrentPageCacheable(): bool {
		// Only on frontend.
		if ( is_admin() ) {
			return false;
		}

		// Only on singular pages/posts
		if ( ! is_singular() ) {
			return false;
		}

		// Skip previews
		if ( is_preview() ) {
			return false;
		}

		// Skip feeds
		if ( is_feed() ) {
			return false;
		}

		// Skip 404s
		if ( is_404() ) {
			return false;
		}

		// Only GET requests.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return false;
		}

		return true;
	}

	/**
	 * @return array<string, bool|string|null>
	 */
	private function getCurrentPageCacheStatus(): array {
		// Indicator should reflect file state even for logged-in/admin-bar visits.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( is_admin() || ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return array(
				'label'     => __( 'Unavailable', 'pivot-performance-toolkit' ),
				'class'     => 'pivot-performance-toolkit-cache-status-bypass',
				'cacheable' => $this->isCurrentPageCacheable(),
				'url'       => null,
			);
		}

		$url = $this->currentRequestUrl();

		if ( ! is_string( $url ) || '' === $url ) {
			return array(
				'label'     => __( 'Not cacheable', 'pivot-performance-toolkit' ),
				'class'     => 'pivot-performance-toolkit-cache-status-bypass',
				'cacheable' => false,
				'url'       => null,
			);
		}

		$cache_file = $this->cacheFilePathFromUrl( $url );

		if ( ! is_string( $cache_file ) || '' === $cache_file ) {
			return array(
				'label'     => __( 'Unavailable', 'pivot-performance-toolkit' ),
				'class'     => 'pivot-performance-toolkit-cache-status-bypass',
				'cacheable' => $this->isCurrentPageCacheable(),
				'url'       => $url,
			);
		}

		if ( ! is_file( $cache_file ) ) {
			return array(
				'label'     => __( 'File missing', 'pivot-performance-toolkit' ),
				'class'     => 'pivot-performance-toolkit-cache-status-miss',
				'cacheable' => $this->isCurrentPageCacheable(),
				'url'       => $url,
			);
		}

		$ttl      = $this->cacheTtl();
		$filetime = (int) @filemtime( $cache_file );

		if ( $filetime <= 0 || ( $filetime + $ttl ) < time() ) {
			return array(
				'label'     => __( 'File stale', 'pivot-performance-toolkit' ),
				'class'     => 'pivot-performance-toolkit-cache-status-miss',
				'cacheable' => $this->isCurrentPageCacheable(),
				'url'       => $url,
			);
		}

		return array(
			'label'     => __( 'File present', 'pivot-performance-toolkit' ),
			'class'     => 'pivot-performance-toolkit-cache-status-hit',
			'cacheable' => $this->isCurrentPageCacheable(),
			'url'       => $url,
		);
	}

	private function currentRequestUrl(): ?string {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- this URL is only ever MD5-hashed to build a cache file path (see cacheFilePathFromUrl()) or rawurlencode()'d into a query arg; raw content is never stored, echoed, or used in a filesystem/query context directly.
		$scheme      = ( ! empty( $_SERVER['HTTPS'] ) && strtolower( (string) wp_unslash( $_SERVER['HTTPS'] ) ) !== 'off' ) ? 'https' : 'http';
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? (string) wp_unslash( $_SERVER['HTTP_HOST'] ) : 'localhost';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( '' === $host ) {
			return null;
		}

		return $scheme . '://' . $host . $request_uri;
	}

	private function cacheFilePathFromUrl( string $url ): ?string {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) ) {
			return null;
		}

		$scheme = isset( $parts['scheme'] ) ? (string) $parts['scheme'] : 'http';
		$host   = isset( $parts['host'] ) ? (string) $parts['host'] : '';
		$path   = isset( $parts['path'] ) ? (string) $parts['path'] : '/';
		$query  = isset( $parts['query'] ) ? (string) $parts['query'] : '';

		if ( '' === $host ) {
			return null;
		}

		$request_uri = $path;
		if ( '' !== $query ) {
			$request_uri .= '?' . $query;
		}

		$cache_key = md5( $scheme . '://' . $host . $request_uri );

		return self::PAGE_CACHE_DIR . '/' . $cache_key . '.html';
	}

	private function cacheTtl(): int {
		$config_file = self::PAGE_CACHE_DIR . '/config.php';

		if ( ! is_file( $config_file ) ) {
			return 600;
		}

		$config = include $config_file;

		if ( ! is_array( $config ) ) {
			return 600;
		}

		$ttl = isset( $config['ttl'] ) ? (int) $config['ttl'] : 600;

		return max( 60, $ttl );
	}

	public function handlePurgeAllCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_adminbar_purge_all_cache' );

		foreach ( glob( self::PAGE_CACHE_DIR . '/*.html' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}

		foreach ( glob( self::MINIFIED_CACHE_DIR . '/*.min.css' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}

		foreach ( glob( self::MINIFIED_CACHE_DIR . '/*.min.js' ) ?: array() as $file_path ) {
			wp_delete_file( $file_path );
		}

		$redirect = wp_get_referer();

		if ( ! is_string( $redirect ) || '' === $redirect ) {
			$redirect = admin_url( 'admin.php?page=pivot-performance-toolkit' );
		}

		wp_safe_redirect( add_query_arg( 'pivot_performance_toolkit_cache_purged', '1', $redirect ) );
		exit;
	}

	public function handlePurgePageCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_adminbar_purge_page_cache' );

		$target_raw = isset( $_GET['pivot_performance_toolkit_target'] ) ? sanitize_text_field( (string) wp_unslash( $_GET['pivot_performance_toolkit_target'] ) ) : '';
		$target_url = '' !== $target_raw ? rawurldecode( $target_raw ) : '';

		if ( '' === $target_url ) {
			$target_url = (string) wp_get_referer();
		}

		if ( '' !== $target_url ) {
			$cache_file = $this->cacheFilePathFromUrl( $target_url );
			if ( is_string( $cache_file ) && is_file( $cache_file ) ) {
				wp_delete_file( $cache_file );
			}
		}

		$redirect = wp_get_referer();

		if ( ! is_string( $redirect ) || '' === $redirect ) {
			$redirect = home_url();
		}

		wp_safe_redirect( add_query_arg( 'pivot_performance_toolkit_page_cache_purged', '1', $redirect ) );
		exit;
	}

	public function renderAdminNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- BladeEngine::view() returns HTML already escaped by Blade's {{ }} at render time; re-escaping here would break the markup. The $_GET values below are read-only post-redirect display flags reduced to booleans via strict '1' === comparison, never echoed raw, not a state-changing action.
		echo BladeEngine::view(
			'admin.admin-bar-notice',
			array(
				'cache_purged'      => isset( $_GET['pivot_performance_toolkit_cache_purged'] ) && (string) '1' === wp_unslash( $_GET['pivot_performance_toolkit_cache_purged'] ),
				'page_cache_purged' => isset( $_GET['pivot_performance_toolkit_page_cache_purged'] ) && (string) '1' === wp_unslash( $_GET['pivot_performance_toolkit_page_cache_purged'] ),
			)
		);
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}
}
