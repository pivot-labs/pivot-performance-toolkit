<?php
/**
 * Plugin Name: WP Performance Toolkit
 * Plugin URI: https://www.wpperformancetoolkit.com
 * Description: Performance optimization toolkit for WordPress.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Digital Canvas
 * Author URI: https://www.digitalcanvas.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: performance-toolkit
 * Domain Path: /languages
 * Network: false
 *
 * @package PerformanceToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PERFORMANCE_TOOLKIT_VERSION', '1.0.0' );
define( 'PERFORMANCE_TOOLKIT_PLUGIN_FILE', __FILE__ );
define( 'PERFORMANCE_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PERFORMANCE_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );

$autoload_file = PERFORMANCE_TOOLKIT_PATH . 'vendor/autoload.php';

if ( file_exists( $autoload_file ) ) {
	require_once $autoload_file;
}

// Strauss-scoped vendor dependencies (prevents class conflicts with other plugins).
$scoped_autoload = PERFORMANCE_TOOLKIT_PATH . 'includes/Vendor/autoload.php';

if ( file_exists( $scoped_autoload ) ) {
	require_once $scoped_autoload;
}

register_activation_hook( __FILE__, array( '\\PerformanceToolkit\\Core\\Lifecycle', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\\PerformanceToolkit\\Core\\Lifecycle', 'deactivate' ) );
function ptk_is_pro_active(): bool {
	return defined( 'PERFORMANCE_TOOLKIT_PRO_VERSION' );
}

function ptk_has_pro(): bool {
	return apply_filters( 'ptk_has_pro', ptk_is_pro_active() );
}

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'performance-toolkit',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

		if ( is_multisite() ) {
			$render_multisite_notice = static function (): void {
				if ( ! current_user_can( 'manage_options' ) ) {
					return;
				}

				echo '<div class="notice notice-warning"><p>';
				esc_html_e(
					'WP Performance Toolkit Free does not support WordPress Multisite. 
					The free version uses a single-site cache architecture and cannot safely operate in a multisite network environment. 
					Multisite support will be available in Pro.',
					'performance-toolkit'
				);
				echo '</p></div>';
			};

			add_action( 'admin_notices', $render_multisite_notice );
			add_action( 'network_admin_notices', $render_multisite_notice );

			return;
		}

		if ( ! class_exists( '\\PerformanceToolkit\\Core\\Plugin' ) ) {
			return;
		}

		\PerformanceToolkit\Core\Plugin::instance()->boot();
	}
);
