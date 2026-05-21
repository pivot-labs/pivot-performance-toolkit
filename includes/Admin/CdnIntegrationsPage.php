<?php
/**
 * CDN integrations admin page.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Integrations\CloudflareIntegration;

final class CdnIntegrationsPage extends BladeAdminPage {

	private const TEST_ACTION  = 'performance_toolkit_cloudflare_test';
	private const PURGE_ACTION = 'performance_toolkit_cloudflare_purge';

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
		return 'performance-toolkit-cdn-integrations';
	}

	public function menuTitle(): string {
		return __( 'CDN & Integrations', 'performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Performance Toolkit CDN & Integrations', 'performance-toolkit' );
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
			'notice'           => isset( $_GET['ptk_cf_notice'] ) ? sanitize_key( wp_unslash( (string) $_GET['ptk_cf_notice'] ) ) : '',
			'message'          => isset( $_GET['ptk_cf_message'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['ptk_cf_message'] ) ) : '',
			'settings_updated' => isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true',
			'test_action'      => self::TEST_ACTION,
			'purge_action'     => self::PURGE_ACTION,
			'test_nonce'       => wp_create_nonce( 'ptk_cloudflare_test' ),
			'purge_nonce'      => wp_create_nonce( 'ptk_cloudflare_purge' ),
		);
	}

	public function handleTestConnection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'performance-toolkit' ) );
		}

		check_admin_referer( 'ptk_cloudflare_test' );

		$result = $this->cloudflare->testConnection();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handlePurgeCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'performance-toolkit' ) );
		}

		check_admin_referer( 'ptk_cloudflare_purge' );

		$result = $this->cloudflare->purgeCache();

		$this->redirectWithNotice( $result['success'], $result['message'] );
	}

	public function handleAjaxTestConnection(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to perform this action.', 'performance-toolkit' ) ),
				403
			);
		}

		check_ajax_referer( 'ptk_cloudflare_test' );

		$result = $this->cloudflare->testConnection();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	public function handleAjaxPurgeCache(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You are not allowed to perform this action.', 'performance-toolkit' ) ),
				403
			);
		}

		check_ajax_referer( 'ptk_cloudflare_purge' );

		$result = $this->cloudflare->purgeCache();

		$this->sendAjaxResult( $result['success'], $result['message'] );
	}

	private function redirectWithNotice( bool $success, string $message ): void {
		$redirect_url = add_query_arg(
			array(
				'page'           => 'performance-toolkit',
				'section'        => 'caching',
				'tab'            => $this->slug(),
				'ptk_cf_notice'  => $success ? 'success' : 'error',
				'ptk_cf_message' => $message,
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
