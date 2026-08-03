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

		$allowed_setting_keys = array( 'defer_scripts', 'minify_html', 'minify_css', 'minify_external_css', 'minify_external_js', 'minify_js' );

		if ( ! in_array( $setting_key, $allowed_setting_keys, true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid setting.', 'pivot-performance-toolkit' ) ), 400 );
		}

		$options                 = $this->settings->all();
		$options[ $setting_key ] = ! empty( $_POST['setting_value'] );

		update_option( $this->settings->optionKey(), $options );

		wp_send_json_success(
			array(
				'message' => __( 'Quick optimization saved.', 'pivot-performance-toolkit' ),
				'setting' => $setting_key,
				'value'   => (bool) $options[ $setting_key ],
			)
		);
	}
}
