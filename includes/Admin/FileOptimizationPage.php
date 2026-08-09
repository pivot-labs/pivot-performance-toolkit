<?php
/**
 * File optimization admin page.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\HttpProtocolDetector;

final class FileOptimizationPage extends BladeAdminPage {

	private const AJAX_SAVE_QUICK_TOGGLE_ACTION = 'pivot_performance_toolkit_ajax_save_file_quick_toggle';

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
		add_action( 'wp_ajax_' . self::AJAX_SAVE_QUICK_TOGGLE_ACTION, array( $this, 'handleSaveQuickToggleAjax' ) );
	}

	public function slug(): string {
		return 'pivot-performance-toolkit-file-optimization';
	}

	public function menuTitle(): string {
		return __( 'File Optimization', 'pivot-performance-toolkit' );
	}

	public function pageTitle(): string {
		return __( 'Pivot Performance Toolkit File Optimization', 'pivot-performance-toolkit' );
	}

	public function iconKey(): string {
		return 'dashicons-media-code';
	}

	public function view(): string {
		return 'admin.file-optimization-page';
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function buildViewData(): array {
		$http_protocol = HttpProtocolDetector::detect();

		return array(
			'options'                       => $this->settings->all(),
			'option_key'                    => $this->settings->optionKey(),
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only "was this just saved" display flag from WordPress core's own settings-updated redirect param; used only in a strict === comparison against a hardcoded literal, never stored or output raw.
			'settings_updated'              => isset( $_GET['settings-updated'] ) && (string) wp_unslash( $_GET['settings-updated'] ) === 'true',
			'ajax_save_quick_toggle_action' => self::AJAX_SAVE_QUICK_TOGGLE_ACTION,
			'ajax_save_quick_toggle_nonce'  => wp_create_nonce( 'pivot_performance_toolkit_file_quick_toggle_ajax' ),
			'http_protocol_version'         => $http_protocol['version'],
			'is_http11'                     => $http_protocol['is_http11'],
			'http_protocol_source'          => $http_protocol['source'],
		);
	}

	public function handleSaveQuickToggleAjax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'pivot-performance-toolkit' ) ), 403 );
		}

		check_ajax_referer( 'pivot_performance_toolkit_file_quick_toggle_ajax' );

		$setting_key = isset( $_POST['setting_key'] ) ? sanitize_key( wp_unslash( (string) $_POST['setting_key'] ) ) : '';

		$allowed_setting_keys = array( 'defer_scripts', 'delay_js_execution', 'async_css_loading', 'minify_html', 'minify_css', 'minify_external_css', 'minify_external_js', 'minify_js' );

		if ( ! in_array( $setting_key, $allowed_setting_keys, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid setting.', 'pivot-performance-toolkit' ) ), 400 );
		}

		$value = ! empty( $_POST['setting_value'] );

		// Only submit the changed key — sanitize() merges everything else in
		// from a freshly-read $base. Writing back a full snapshot here would
		// race with any concurrent save (e.g. the CDN integrations form) and
		// silently clobber it with stale values for every other field.
		update_option( $this->settings->optionKey(), array( $setting_key => $value ) );

		wp_send_json_success(
			array(
				'message' => __( 'Quick optimization saved.', 'pivot-performance-toolkit' ),
				'setting' => $setting_key,
				'value'   => $value,
			)
		);
	}
}
