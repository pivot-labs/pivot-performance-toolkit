<?php
/**
 * Plugin Name: Pivot Performance Toolkit
 * Plugin URI: https://www.pivotlabs.dev/
 * Description: Performance optimization toolkit for WordPress.
 * Version: 1.1.1-dev
 * Requires at least: 6.5
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

define( 'PIVOT_PERFORMANCE_TOOLKIT_VERSION', '1.1.1-dev' );
define( 'PIVOT_PERFORMANCE_TOOLKIT_PLUGIN_FILE', __FILE__ );
define( 'PIVOT_PERFORMANCE_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PIVOT_PERFORMANCE_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

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
function pivot_performance_toolkit_is_pro_active(): bool {
	return defined( 'PIVOT_PERFORMANCE_TOOLKIT_PRO_VERSION' );
}

function pivot_performance_toolkit_has_pro(): bool {
	return apply_filters( 'pivot_performance_toolkit_has_pro', pivot_performance_toolkit_is_pro_active() );
}

add_action(
	'plugins_loaded',
	static function (): void {
		if ( is_multisite() ) {
			$render_multisite_notice = static function (): void {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				echo '<div class="notice notice-warning"><p>';
				esc_html_e(
					'Pivot Performance Toolkit Free does not support WordPress Multisite. 
					The free version uses a single-site cache architecture and cannot safely operate in a multisite network environment. 
					Multisite support will be available in Pro.',
					'pivot-performance-toolkit'
				);
				echo '</p></div>';
			};

			add_action( 'admin_notices', $render_multisite_notice );
			add_action( 'network_admin_notices', $render_multisite_notice );

			return;
		}

		if ( ! class_exists( '\\PivotPerformanceToolkit\\Core\\Plugin' ) ) {
			return;
		}

		\PivotPerformanceToolkit\Core\Plugin::instance()->boot();
	}
);
