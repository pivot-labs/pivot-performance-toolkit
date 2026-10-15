<?php
/**
 * Plugin uninstall cleanup routines.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Wrapped in a closure so every local below is function-scoped rather than a
// true PHP global (this file is `include`d at the top level during uninstall,
// so without this wrapper each variable would leak into the global variable
// table for the rest of the request).
( function () {

	$policy = get_option( 'pivot_performance_toolkit_remove_data_on_uninstall', '0' );

	if ( (string) '1' !== $policy ) {
		return;
	}

	$cache_dir      = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';
	$dropin_path    = WP_CONTENT_DIR . '/advanced-cache.php';
	$wp_config_path = ABSPATH . 'wp-config.php';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	global $wp_filesystem;

	// Remove plugin options.
	delete_option( 'pivot_performance_toolkit_settings' );
	delete_option( 'pivot_performance_toolkit_remove_data_on_uninstall' );
	delete_option( 'pivot_performance_toolkit_last_performance_result' );

	// Remove generated minified/combined assets from this plugin's folder in the uploads directory.
	$uploads = wp_upload_dir( null, false );

	if ( empty( $uploads['error'] ) && is_string( $uploads['basedir'] ?? null ) ) {
		$assets_dir = untrailingslashit( $uploads['basedir'] ) . '/pivot-performance-toolkit';

		if ( $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->is_dir( $assets_dir ) ) {
			$wp_filesystem->rmdir( $assets_dir, true );
		}
	}

	// Remove cache directory recursively.
	if ( $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->is_dir( $cache_dir ) ) {
		$wp_filesystem->rmdir( $cache_dir, true );
	}

	// Remove the advanced-cache drop-in only if it appears to belong to this plugin.
	if ( is_file( $dropin_path ) ) {
		$dropin_contents = (string) file_get_contents( $dropin_path );

		if ( '' !== $dropin_contents && str_contains( $dropin_contents, 'Pivot Performance Toolkit' ) ) {
			wp_delete_file( $dropin_path );
		}
	}

	// Remove WP_CACHE define only if this plugin originally added the marker comment.
	if ( is_file( $wp_config_path ) && $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->is_writable( $wp_config_path ) ) {
		$config_contents = $wp_filesystem->get_contents( $wp_config_path );

		if ( is_string( $config_contents ) && '' !== $config_contents ) {
			$new_contents = preg_replace(
				'/^define\s*\(\s*[\'\"]WP_CACHE[\'\"].*\/\/ Added by Pivot Performance Toolkit\r?\n/m',
				'',
				$config_contents
			);

			if ( is_string( $new_contents ) && $new_contents !== $config_contents ) {
				$wp_filesystem->put_contents( $wp_config_path, $new_contents, FS_CHMOD_FILE );
			}
		}
	}
} )();
