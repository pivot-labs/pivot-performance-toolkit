<?php
/**
 * Plugin uninstall cleanup routines.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$policy = get_option( 'performance_toolkit_remove_data_on_uninstall', '0' );

if ( (string) '1' !== $policy ) {
	return;
}

$plugin_option_key = 'performance_toolkit_settings';
$cache_dir         = WP_CONTENT_DIR . '/cache/performance-toolkit';
$dropin_path       = WP_CONTENT_DIR . '/advanced-cache.php';
$wp_config_path    = ABSPATH . 'wp-config.php';

// Remove plugin options.
delete_option( $plugin_option_key );
delete_option( 'performance_toolkit_remove_data_on_uninstall' );

// Remove cache directory recursively.
if ( is_dir( $cache_dir ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $cache_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $item ) {
		if ( $item->isDir() ) {
			@rmdir( $item->getPathname() );
			continue;
		}

		@unlink( $item->getPathname() );
	}

	@rmdir( $cache_dir );
}

// Remove the advanced-cache drop-in only if it appears to belong to this plugin.
if ( is_file( $dropin_path ) ) {
	$dropin_contents = (string) file_get_contents( $dropin_path );

	if ( '' !== $dropin_contents && str_contains( $dropin_contents, 'Performance Toolkit' ) ) {
		@unlink( $dropin_path );
	}
}

// Remove WP_CACHE define only if this plugin originally added the marker comment.
if ( is_file( $wp_config_path ) && is_writable( $wp_config_path ) ) {
	$config_contents = file_get_contents( $wp_config_path );

	if ( is_string( $config_contents ) && '' !== $config_contents ) {
		$new_contents = preg_replace(
			'/^define\s*\(\s*[\'\"]WP_CACHE[\'\"].*\/\/ Added by Performance Toolkit\r?\n/m',
			'',
			$config_contents
		);

		if ( is_string( $new_contents ) && $new_contents !== $config_contents ) {
			file_put_contents( $wp_config_path, $new_contents, LOCK_EX );
		}
	}
}
