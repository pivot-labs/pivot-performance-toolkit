<?php
/**
 * CDN integrations admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Integrations\CloudflareIntegration;

final class CdnIntegrationsPage extends BladeAdminPage {

	private const TEST_ACTION  = 'pivot_performance_toolkit_cloudflare_test';
	private const PURGE_ACTION = 'pivot_performance_toolkit_cloudflare_purge';

	private Settings $settings;

	private CloudflareIntegration $cloudflare;

	public function __construct( Settings $settings, CloudflareIntegration $cloudflare ) {
		$this->settings   = $settings;
		$this->cloudflare = $cloudflare;

		add_action( 'admin_post_' . self::TEST_ACTION, array( $this, 'handleTestConnection' ) );
		add_action( 'admin_post_' . self::PURGE_ACTION, array( $this, 'handlePurgeCache' ) );
		add_action( 'wp_ajax_' . self::TEST_ACTION, array( $this, 'handleAjaxTestConnection' ) );
		add_action( 'wp_ajax_' . self::PURGE_ACTION, array( $this, 'handleAjaxPurgeCache' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-cdn-integrations';
	}

	public function menuTitle(): string {
		return __( 'CDN & Integrations', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit CDN & Integrations', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'dashicons-admin-site-alt3';
	}

	public function view(): string {
		return 'admin.cdn-integrations-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		return array(
			'options'          => $this->settings->all(),
			'option_key'       => $this->settings->optionKey(),
			// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- these are read-only display values from this page's own post-redirect notice / WordPress core's settings-updated param; used only in strict comparisons/sanitize_key(), never stored or output raw.
			'notice'           => isset( $_GET['pivot_performance_toolkit_cf_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['pivot_performance_toolkit_cf_notice'] ) ) : '',
			'message'          => isset( $_GET['pivot_performance_toolkit_cf_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['pivot_performance_toolkit_cf_message'] ) ) : '',
			'settings_updated' => isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true',
			// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'test_action'      => self::TEST_ACTION,
			'purge_action'     => self::PURGE_ACTION,
			'test_nonce'       => wp_create_nonce( 'pivot_performance_toolkit_cloudflare_test' ),
			'purge_nonce'      => wp_create_nonce( 'pivot_performance_toolkit_cloudflare_purge' ),
		);
	}

	public function handleTestConnection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_cloudflare_test' );

		$result = $this->cloudflare->testConnection();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handlePurgeCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) );
		}

		check_admin_referer( 'pivot_performance_toolkit_cloudflare_purge' );

		$result = $this->cloudflare->purgeCache();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handleAjaxTestConnection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) ),
				403
			);
		}

		check_ajax_referer( 'pivot_performance_toolkit_cloudflare_test' );

		$result = $this->cloudflare->testConnection();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	public function handleAjaxPurgeCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to perform this action.', 'pivot-performance-toolkit' ) ),
				403
			);
		}

		check_ajax_referer( 'pivot_performance_toolkit_cloudflare_purge' );

		$result = $this->cloudflare->purgeCache();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	private function redirectWithNotice( bool $success, string $message ): void {
		$redirect_url = add_query_arg(
			array(
				'page'                                 => 'pivot-performance-toolkit',
				'section'                              => 'caching',
				'tab'                                  => $this->slug(),
				'pivot_performance_toolkit_cf_notice'  => $success ? 'success' : 'error',
				'pivot_performance_toolkit_cf_message' => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	private function sendAjaxResult( bool $success, string $message ): void {
		if ( $success ) {
			wp_send_json_success( array( 'message' => $message ) );
		}

		wp_send_json_error( array( 'message' => $message ) );
	}
}
