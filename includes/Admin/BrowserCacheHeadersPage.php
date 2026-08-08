<?php
/**
 * Browser cache headers admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Cache\HtaccessCacheHeaders;
use PivotPerformanceToolkit\Core\Settings;

final class BrowserCacheHeadersPage extends BladeAdminPage {

	private const APPLY_ACTION  = 'pivot_performance_toolkit_htaccess_apply';
	private const REMOVE_ACTION = 'pivot_performance_toolkit_htaccess_remove';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;

		add_action( 'admin_post_' . self::APPLY_ACTION, array( $this, 'handleApply' ) );
		add_action( 'admin_post_' . self::REMOVE_ACTION, array( $this, 'handleRemove' ) );
		add_action( 'wp_ajax_' . self::APPLY_ACTION, array( $this, 'handleAjaxApply' ) );
		add_action( 'wp_ajax_' . self::REMOVE_ACTION, array( $this, 'handleAjaxRemove' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-browser-cache';
	}

	public function menuTitle(): string {
		return __( 'Browser Cache', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit – Browser Cache & Compression', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'monitor-cog';
	}

	public function view(): string {
		return 'admin.browser-cache-headers-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- SERVER_SOFTWARE is set by the web server, not user input; it's only ever displayed via Blade's {{ }}, which HTML-escapes it at render time. The notice/message query args are read-only display values from this page's own post-redirect notice, used only in strict comparisons/output via wp_kses, never stored.
		$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? (string) wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) : '';

		return array(
			'htaccess_snippet' => HtaccessCacheHeaders::snippet(),
			'htaccess_applied' => HtaccessCacheHeaders::isApplied(),
			'nginx_snippet'    => $this->generateNginxSnippet(),
			'server_software'  => $server_software,
			'home_url'         => home_url(),
			'option_key'       => $this->settings->optionKey(),
			'apply_action'     => self::APPLY_ACTION,
			'remove_action'    => self::REMOVE_ACTION,
			'apply_nonce'      => wp_create_nonce( self::APPLY_ACTION ),
			'remove_nonce'     => wp_create_nonce( self::REMOVE_ACTION ),
			'notice'           => isset( $_GET['pivot_performance_toolkit_htaccess_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pivot_performance_toolkit_htaccess_notice'] ) ) : '',
			'message'          => isset( $_GET['pivot_performance_toolkit_htaccess_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pivot_performance_toolkit_htaccess_message'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	public function handleApply(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( self::APPLY_ACTION );

		$result = HtaccessCacheHeaders::apply();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handleRemove(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( self::REMOVE_ACTION );

		$result = HtaccessCacheHeaders::remove();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handleAjaxApply(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( self::APPLY_ACTION );

		$result = HtaccessCacheHeaders::apply();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	public function handleAjaxRemove(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( self::REMOVE_ACTION );

		$result = HtaccessCacheHeaders::remove();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	private function redirectWithNotice( bool $success, string $message ): void {
		$redirect_url = add_query_arg(
			array(
				'page'    => 'pivot-performance-toolkit',
				'section' => 'caching',
				'tab'     => $this->slug(),
				'pivot_performance_toolkit_htaccess_notice' => $success ? 'success' : 'error',
				'pivot_performance_toolkit_htaccess_message' => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function sendAjaxResult( bool $success, string $message ): void {
		if ( $success ) {
			wp_send_json_success(
				array(
					'message' => $message,
					'applied' => HtaccessCacheHeaders::isApplied(),
				)
			);
		}

		wp_send_json_error( array( 'message' => $message ) );
	}

	private function generateNginxSnippet(): string {
		return '# BEGIN Pivot Performance Toolkit - Browser Cache & Compression

# Gzip compression
gzip on;
gzip_vary on;
gzip_proxied any;
gzip_comp_level 6;
gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss application/rss+xml font/truetype font/opentype application/x-font-ttf image/svg+xml;

# Cache expiration map
map $sent_http_content_type $expires {
    default                    off;
    text/html                  1h;
    text/css                   1y;
    application/javascript     1y;
    text/javascript            1y;
    image/jpeg                 1M;
    image/gif                  1M;
    image/png                  1M;
    image/svg+xml              1M;
    image/webp                 1M;
    font/ttf                   1y;
    font/otf                   1y;
    font/woff                  1y;
    font/woff2                 1y;
    application/font-woff      1y;
}

expires $expires;

# Cache control headers for versioned assets
location ~ \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf)$ {
    add_header Cache-Control "max-age=31536000, immutable";
    access_log off;
}

# Cache control for HTML (revalidate frequently)
location ~ \.html$ {
    add_header Cache-Control "max-age=3600, must-revalidate";
}

# Security headers
add_header X-Content-Type-Options "nosniff";
add_header X-Frame-Options "SAMEORIGIN";

# END Pivot Performance Toolkit - Browser Cache & Compression

# Note: Add this configuration inside your server {} block in nginx.conf
# Contact your hosting provider to apply these settings for you';
	}
}
