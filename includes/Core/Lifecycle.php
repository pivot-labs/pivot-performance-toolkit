<?php
/**
 * Plugin activation and deactivation lifecycle.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Core;

use PerformanceToolkit\Utils\FilesystemCheck;

final class Lifecycle {

	private const DROPIN_INSTALL_NOTICE_TRANSIENT     = 'ptk_dropin_install_failure_notice';
	private const DEACTIVATE_CLEANUP_NOTICE_TRANSIENT = 'ptk_deactivate_cleanup_failure_notice';

	private static function dropinSource(): string {
		return PERFORMANCE_TOOLKIT_PATH . 'includes/Cache/advanced-cache.php';
	}

	private static function dropinDest(): string {
		return WP_CONTENT_DIR . '/advanced-cache.php';
	}

	private static function cacheDir(): string {
		return WP_CONTENT_DIR . '/cache/performance-toolkit';
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
			$cleanup_errors[] = __( 'Could not list cached HTML files for cleanup.', 'performance-toolkit' );
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
					__( 'Could not remove cached file: %s', 'performance-toolkit' ),
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
					__( 'Could not remove cache config file: %s', 'performance-toolkit' ),
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

	// -------------------------------------------------------------------------
	// Drop-in helpers
	// -------------------------------------------------------------------------

	private static function installDropin(): bool {
		$dropin_source = self::dropinSource();
		$dropin_dest   = self::dropinDest();

		// Don't overwrite an existing drop-in that belongs to another plugin.
		if ( file_exists( $dropin_dest ) && ! self::dropinIsOurs() ) {
			self::setDropinInstallFailureNotice(
				__( 'Another plugin already manages wp-content/advanced-cache.php. Performance Toolkit did not overwrite it.', 'performance-toolkit' )
			);
			return false;
		}

		if ( ! file_exists( $dropin_source ) ) {
			self::setDropinInstallFailureNotice(
				__( 'The source drop-in file is missing from the plugin directory.', 'performance-toolkit' )
			);
			return false;
		}

		$dropin_dest_dir = dirname( $dropin_dest );
		if ( ! is_dir( $dropin_dest_dir ) || ! is_writable( $dropin_dest_dir ) ) {
			self::setDropinInstallFailureNotice(
				__( 'The wp-content directory is not writable by PHP.', 'performance-toolkit' )
			);
			return false;
		}

		$copied = copy( $dropin_source, $dropin_dest );
		if ( false === $copied ) {
			self::setDropinInstallFailureNotice(
				__( 'PHP failed to copy advanced-cache.php into wp-content.', 'performance-toolkit' )
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

		if ( str_contains( $contents, 'Generated by: WP Performance Toolkit' ) ) {
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

		return str_contains( $contents, 'Performance Toolkit' );
	}

	// -------------------------------------------------------------------------
	// wp-config.php helpers
	// -------------------------------------------------------------------------

	private static function enableWpCache(): void {
		if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
			return; // Already enabled.
		}

		$config = ABSPATH . 'wp-config.php';

		if ( ! is_writable( $config ) ) {
			return;
		}

		$contents = file_get_contents( $config );

		if ( false === $contents ) {
			return;
		}

		// Already present (maybe defined as false).
		if ( preg_match( '/define\s*\(\s*[\'"]WP_CACHE[\'"]/', $contents ) ) {
			return;
		}

		// Insert before the "That's all, stop editing!" comment.
		$new = preg_replace(
			'/(\\/\\*\\s*That\'s all[^*]*\\*\\/)/i',
			"define( 'WP_CACHE', true ); // Added by Performance Toolkit\n$1",
			$contents
		);

		if ( null !== $new && $new !== $contents ) {
			file_put_contents( $config, $new, LOCK_EX );
		}
	}

	private static function disableWpCache(): void {
		$config = ABSPATH . 'wp-config.php';

		if ( ! is_writable( $config ) ) {
			return;
		}

		$contents = file_get_contents( $config );

		if ( false === $contents ) {
			return;
		}

		$new = preg_replace(
			'/^define\s*\(\s*\'WP_CACHE\'.*\/\/ Added by Performance Toolkit\r?\n/m',
			'',
			$contents
		);

		if ( null !== $new && $new !== $contents ) {
			file_put_contents( $config, $new, LOCK_EX );
		}
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
