<?php
/**
 * Plugin activation and deactivation lifecycle.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Core;

use PivotPerformanceToolkit\Utils\FilesystemCheck;

final class Lifecycle {

	private const DROPIN_INSTALL_NOTICE_TRANSIENT     = 'pivot_performance_toolkit_dropin_install_failure_notice';
	private const DEACTIVATE_CLEANUP_NOTICE_TRANSIENT = 'pivot_performance_toolkit_deactivate_cleanup_failure_notice';
	private const WP_CONFIG_NOTICE_TRANSIENT          = 'pivot_performance_toolkit_wp_config_failure_notice';

	/**
	 * Option keys renamed during the Pivot Performance Toolkit rebrand, mapped from their
	 * pre-rebrand names so existing installs keep their saved data instead of reverting to defaults.
	 *
	 * @var array<string, string>
	 */
	private const LEGACY_OPTION_KEYS = array(
		'performance_toolkit_settings'                 => 'pivot_performance_toolkit_settings',
		'performance_toolkit_remove_data_on_uninstall' => 'pivot_performance_toolkit_remove_data_on_uninstall',
		'ptk_last_performance_result'                  => 'pivot_performance_toolkit_last_performance_result',
	);

	/**
	 * Copies any option values still stored under a pre-rebrand key to its new key.
	 * Safe to call on every request: it's a no-op once migrated.
	 */
	public static function maybeMigrateLegacyOptionKeys(): void {
		foreach ( self::LEGACY_OPTION_KEYS as $legacy_key => $new_key ) {
			if ( false !== get_option( $new_key, false ) ) {
				continue;
			}

			$legacy_value = get_option( $legacy_key, false );

			if ( false === $legacy_value ) {
				continue;
			}

			update_option( $new_key, $legacy_value );
			delete_option( $legacy_key );
		}
	}

	private static function dropinSource(): string {
		return PIVOT_PERFORMANCE_TOOLKIT_PATH . 'includes/Cache/advanced-cache.php';
	}

	private static function dropinDest(): string {
		return WP_CONTENT_DIR . '/advanced-cache.php';
	}

	private static function cacheDir(): string {
		return WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';
	}

	public static function activate(): void {
		// Create cache directory.
		if ( ! file_exists( self::cacheDir() ) ) {
			wp_mkdir_p( self::cacheDir() );
		}

		// Check filesystem status and cache it for admin display.
		$fs_status = FilesystemCheck::checkCacheDirectories();
		FilesystemCheck::setCachedStatus( $fs_status );

		// Install the advanced-cache.php drop-in.
		$dropin_installed = self::installDropin();

		// Add WP_CACHE define to wp-config.php only when the drop-in is available.
		if ( true === $dropin_installed ) {
			self::enableWpCache();
		} else {
			// Remove the plugin-managed define to avoid claiming cache support when the drop-in is missing.
			self::disableWpCache();
		}
	}

	public static function deactivate(): void {
		// Remove the advanced-cache.php drop-in only if it was installed by us.
		self::removeDropin();

		// Remove the object-cache.php drop-in if it was installed by us.
		self::removeObjectCacheDropin();

		// Remove WP_CACHE define we added.
		self::disableWpCache();

		$cleanup_errors = array();

		// Purge all cached HTML files.
		$html_files = glob( self::cacheDir() . '/*.html' );
		if ( false === $html_files ) {
			$cleanup_errors[] = __( 'Could not list cached HTML files for cleanup.', 'pivot-performance-toolkit' );
			$html_files       = array();
		}

		foreach ( $html_files as $file ) {
			if ( ! is_string( $file ) || '' === $file || ! file_exists( $file ) ) {
				continue;
			}

			$deleted = unlink( $file );
			if ( false === $deleted ) {
				$cleanup_errors[] = sprintf(
					/* translators: %s: absolute file path that could not be deleted during deactivation cleanup. */
					__( 'Could not remove cached file: %s', 'pivot-performance-toolkit' ),
					$file
				);
			}
		}

		// Remove the config file.
		$config = self::cacheDir() . '/config.php';
		if ( file_exists( $config ) ) {
			$config_deleted = unlink( $config );
			if ( false === $config_deleted ) {
				$cleanup_errors[] = sprintf(
					/* translators: %s: absolute config file path that could not be deleted during deactivation cleanup. */
					__( 'Could not remove cache config file: %s', 'pivot-performance-toolkit' ),
					$config
				);
			}
		}

		if ( ! empty( $cleanup_errors ) ) {
			self::setDeactivateCleanupFailureNotice( $cleanup_errors );
			return;
		}

		delete_transient( self::DEACTIVATE_CLEANUP_NOTICE_TRANSIENT );
	}

	/**
	 * Update the installed drop-in if it is ours but outdated (e.g. after a plugin update).
	 */
	public static function maybeUpdateDropin(): void {
		$source = self::dropinSource();
		$dest   = self::dropinDest();

		if ( ! file_exists( $dest ) || ! self::dropinIsOurs() ) {
			return;
		}

		if ( ! file_exists( $source ) ) {
			return;
		}

		if ( sha1_file( $source ) === sha1_file( $dest ) ) {
			return;
		}

		copy( $source, $dest );
	}

	// -------------------------------------------------------------------------
	// Drop-in helpers
	// -------------------------------------------------------------------------

	private static function installDropin(): bool {
		$dropin_source = self::dropinSource();
		$dropin_dest   = self::dropinDest();

		// Don't overwrite an existing drop-in that belongs to another plugin.
		if ( file_exists( $dropin_dest ) && ! self::dropinIsOurs() ) {
			self::setDropinInstallFailureNotice(
				__( 'Another plugin already manages wp-content/advanced-cache.php. Pivot Performance Toolkit did not overwrite it.', 'pivot-performance-toolkit' )
			);
			return false;
		}

		if ( ! file_exists( $dropin_source ) ) {
			self::setDropinInstallFailureNotice(
				__( 'The source drop-in file is missing from the plugin directory.', 'pivot-performance-toolkit' )
			);
			return false;
		}

		$dropin_dest_dir = dirname( $dropin_dest );
		if ( ! is_dir( $dropin_dest_dir ) || ! is_writable( $dropin_dest_dir ) ) {
			self::setDropinInstallFailureNotice(
				__( 'The wp-content directory is not writable by PHP.', 'pivot-performance-toolkit' )
			);
			return false;
		}

		$copied = copy( $dropin_source, $dropin_dest );
		if ( false === $copied ) {
			self::setDropinInstallFailureNotice(
				__( 'PHP failed to copy advanced-cache.php into wp-content.', 'pivot-performance-toolkit' )
			);
			return false;
		}

		delete_transient( self::DROPIN_INSTALL_NOTICE_TRANSIENT );

		return true;
	}

	private static function setDropinInstallFailureNotice( string $reason ): void {
		set_transient(
			self::DROPIN_INSTALL_NOTICE_TRANSIENT,
			array(
				'reason' => $reason,
				'source' => self::dropinSource(),
				'dest'   => self::dropinDest(),
			),
			DAY_IN_SECONDS
		);
	}

	private static function removeDropin(): void {
		$dropin_dest = self::dropinDest();

		if ( file_exists( $dropin_dest ) && self::dropinIsOurs() ) {
			unlink( $dropin_dest );
		}
	}

	/**
	 * Remove the object-cache.php drop-in if it was installed by us.
	 */
	private static function removeObjectCacheDropin(): void {
		$dest = WP_CONTENT_DIR . '/object-cache.php';

		if ( ! file_exists( $dest ) ) {
			return;
		}

		$contents = (string) file_get_contents( $dest );

		if ( str_contains( $contents, 'Generated by: Pivot Performance Toolkit' ) ) {
			unlink( $dest );
		}
	}

	/**
	 * Returns true if the installed advanced-cache.php was placed by this plugin.
	 */
	private static function dropinIsOurs(): bool {
		if ( ! file_exists( self::dropinDest() ) ) {
			return false;
		}

		$contents = (string) file_get_contents( self::dropinDest() );

		return str_contains( $contents, 'Pivot Performance Toolkit' );
	}

	// -------------------------------------------------------------------------
	// wp-config.php helpers
	// -------------------------------------------------------------------------

	private static function enableWpCache(): void {
		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
			return; // Already enabled.
		}

		$config = ABSPATH . 'wp-config.php';

		$wp_filesystem = self::initFilesystem();

		if ( null === $wp_filesystem ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't enable page caching because WordPress couldn't access the filesystem — add `define('WP_CACHE', true);` to wp-config.php manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		if ( ! $wp_filesystem->exists( $config ) || ! $wp_filesystem->is_writable( $config ) ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't enable page caching because wp-config.php isn't writable — add `define('WP_CACHE', true);` manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		$contents = $wp_filesystem->get_contents( $config );

		if ( false === $contents ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't enable page caching because wp-config.php could not be read — add `define('WP_CACHE', true);` manually.", 'pivot-performance-toolkit' )
			);
			return;
		}

		// Already present (maybe defined as false).
		if ( preg_match( '/define\s*\(\s*[\'"]WP_CACHE[\'"]/', $contents ) ) {
			delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
			return;
		}

		// Insert before the "That's all, stop editing!" comment.
		$new = preg_replace(
			'/(\\/\\*\\s*That\'s all[^*]*\\*\\/)/i',
			"define( 'WP_CACHE', true ); // Added by Pivot Performance Toolkit\n$1",
			$contents
		);

		if ( null === $new || $new === $contents ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't find where to add the WP_CACHE setting in wp-config.php — add `define('WP_CACHE', true);` manually.", 'pivot-performance-toolkit' )
			);
			return;
		}

		$written = $wp_filesystem->put_contents( $config, $new, FS_CHMOD_FILE );

		if ( false === $written ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't enable page caching because wp-config.php isn't writable — add `define('WP_CACHE', true);` manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
	}

	private static function disableWpCache(): void {
		$config = ABSPATH . 'wp-config.php';

		$wp_filesystem = self::initFilesystem();

		if ( null === $wp_filesystem ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't disable page caching because WordPress couldn't access the filesystem — remove `define('WP_CACHE', true);` from wp-config.php manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		if ( ! $wp_filesystem->exists( $config ) || ! $wp_filesystem->is_writable( $config ) ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't disable page caching because wp-config.php isn't writable — remove `define('WP_CACHE', true);` manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		$contents = $wp_filesystem->get_contents( $config );

		if ( false === $contents ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't disable page caching because wp-config.php could not be read — remove `define('WP_CACHE', true);` manually.", 'pivot-performance-toolkit' )
			);
			return;
		}

		$new = preg_replace(
			'/^define\s*\(\s*\'WP_CACHE\'.*\/\/ Added by Pivot Performance Toolkit\r?\n/m',
			'',
			$contents
		);

		if ( null === $new || $new === $contents ) {
			// Nothing to remove (define absent, or not one we added) — not a failure.
			delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
			return;
		}

		$written = $wp_filesystem->put_contents( $config, $new, FS_CHMOD_FILE );

		if ( false === $written ) {
			self::setWpConfigFailureNotice(
				__( "Pivot Performance Toolkit couldn't disable page caching because wp-config.php isn't writable — remove `define('WP_CACHE', true);` manually, or fix file permissions.", 'pivot-performance-toolkit' )
			);
			return;
		}

		delete_transient( self::WP_CONFIG_NOTICE_TRANSIENT );
	}

	/**
	 * Initializes WP_Filesystem for direct file access, returning null if it's unavailable.
	 *
	 * @return \WP_Filesystem_Base|null
	 */
	private static function initFilesystem() {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if ( ! WP_Filesystem() ) {
			return null;
		}

		global $wp_filesystem;

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			return null;
		}

		return $wp_filesystem;
	}

	private static function setWpConfigFailureNotice( string $message ): void {
		set_transient(
			self::WP_CONFIG_NOTICE_TRANSIENT,
			array( 'message' => $message ),
			DAY_IN_SECONDS
		);
	}

	/**
	 * @param array<int, string> $errors
	 */
	private static function setDeactivateCleanupFailureNotice( array $errors ): void {
		set_transient(
			self::DEACTIVATE_CLEANUP_NOTICE_TRANSIENT,
			array(
				'errors'      => $errors,
				'cache_dir'   => self::cacheDir(),
				'config_file' => self::cacheDir() . '/config.php',
			),
			DAY_IN_SECONDS
		);
	}
}
