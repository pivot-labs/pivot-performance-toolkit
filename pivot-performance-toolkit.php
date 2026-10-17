<?php
/**
 * Plugin Name: Pivot Performance Toolkit
 * Plugin URI: https://www.pivotlabs.dev/
 * Description: Performance optimization toolkit for WordPress.
 * Version: 1.1.2
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * Author: Pivot Labs
 * Author URI: https://www.pivotlabs.dev
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: pivot-performance-toolkit
 * Domain Path: /languages
 *
 * @package PivotPerformanceToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PIVOT_PERFORMANCE_TOOLKIT_VERSION', '1.1.2' );
define( 'PIVOT_PERFORMANCE_TOOLKIT_PLUGIN_FILE', __FILE__ );
define( 'PIVOT_PERFORMANCE_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PIVOT_PERFORMANCE_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

// The bundled templating library relies on the mbstring extension and ships no polyfill for it.
if ( ! extension_loaded( 'mbstring' ) ) {
	$pivot_performance_toolkit_render_mbstring_notice = static function (): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		esc_html_e(
			'Pivot Performance Toolkit requires the PHP mbstring extension and has not been loaded. Ask your host to enable mbstring, then reload this page.',
			'pivot-performance-toolkit'
		);
		echo '</p></div>';
	};

	add_action( 'admin_notices', $pivot_performance_toolkit_render_mbstring_notice );
	add_action( 'network_admin_notices', $pivot_performance_toolkit_render_mbstring_notice );

	return;
}

$pivot_performance_toolkit_autoload_file = PIVOT_PERFORMANCE_TOOLKIT_PATH . 'vendor/autoload.php';

if ( file_exists( $pivot_performance_toolkit_autoload_file ) ) {
	require_once $pivot_performance_toolkit_autoload_file;
}

// Strauss-scoped vendor dependencies (prevents class conflicts with other plugins).
$pivot_performance_toolkit_scoped_autoload = PIVOT_PERFORMANCE_TOOLKIT_PATH . 'includes/Vendor/autoload.php';

if ( file_exists( $pivot_performance_toolkit_scoped_autoload ) ) {
	require_once $pivot_performance_toolkit_scoped_autoload;
}

register_activation_hook( __FILE__, array( '\\PivotPerformanceToolkit\\Core\\Lifecycle', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\PivotPerformanceToolkit\\Core\\Lifecycle', 'deactivate' ) );

const PIVOT_PERFORMANCE_TOOLKIT_MULTISITE_NOTICE_META = 'pivot_performance_toolkit_multisite_notice_dismissed';

/**
 * Explains that the plugin stays inactive on a multisite network.
 *
 * The page cache uses a single-site layout, so none of the plugin boots on
 * multisite. The notice appears on the Plugins screen only and can be dismissed;
 * the dismissal is remembered per user.
 */
function pivot_performance_toolkit_register_multisite_notice(): void {
	$nonce_action = 'pivot_performance_toolkit_dismiss_multisite_notice';

	add_action(
		'wp_ajax_pivot_performance_toolkit_dismiss_multisite_notice',
		static function () use ( $nonce_action ): void {
			check_ajax_referer( $nonce_action );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( null, 403 );
			}

			update_user_meta( get_current_user_id(), PIVOT_PERFORMANCE_TOOLKIT_MULTISITE_NOTICE_META, 1 );
			wp_send_json_success();
		}
	);

	$render_notice = static function () use ( $nonce_action ): void {
		// The plugin registers no screens of its own on multisite, so the Plugins list is the only place to explain why.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) || get_user_meta( get_current_user_id(), PIVOT_PERFORMANCE_TOOLKIT_MULTISITE_NOTICE_META, true ) ) {
			return;
		}

		wp_enqueue_script(
			'pivot-performance-toolkit-multisite-notice',
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'assets/js/multisite-notice.js',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION,
			true
		);

		printf(
			'<div class="notice notice-warning is-dismissible pivot-performance-toolkit-multisite-notice" data-nonce="%1$s" data-ajax-url="%2$s"><p>%3$s</p></div>',
			esc_attr( wp_create_nonce( $nonce_action ) ),
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_html__( 'Pivot Performance Toolkit does not support WordPress Multisite. It uses a single-site cache architecture and cannot safely operate in a multisite network environment, so it stays inactive on this network.', 'pivot-performance-toolkit' )
		);
	};

	add_action( 'admin_notices', $render_notice );
	add_action( 'network_admin_notices', $render_notice );
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( is_multisite() ) {
			pivot_performance_toolkit_register_multisite_notice();

			return;
		}

		if ( ! class_exists( '\\PivotPerformanceToolkit\\Core\\Plugin' ) ) {
			return;
		}

		\PivotPerformanceToolkit\Core\Plugin::instance()->boot();
	}
);
