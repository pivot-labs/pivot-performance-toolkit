<?php
/**
 * Filesystem utility checks.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Utils;

final class FilesystemCheck {

	private const FILESYSTEM_STATUS_TRANSIENT = 'performance_toolkit_fs_status';
	private const FILESYSTEM_STATUS_TTL       = 3600; // 1 hour

	/**
	 * Check if all required cache directories are writable.
	 *
	 * @return array{writable: bool, dirs: array<string, bool>, errors: string[]}
	 */
	public static function checkCacheDirectories(): array {
		$cache_base = WP_CONTENT_DIR . '/cache/performance-toolkit';

		$dirs = array(
			'base'            => $cache_base,
			'minified_assets' => $cache_base . '/minified-assets',
		);

		$results = array(
			'writable' => true,
			'dirs'     => array(),
			'errors'   => array(),
		);

		foreach ( $dirs as $name => $dir_path ) {
			$writable                 = self::isDirectoryWritable( $dir_path );
			$results['dirs'][ $name ] = $writable;

			if ( ! $writable ) {
				$results['writable'] = false;
				$results['errors'][] = sprintf(
					/* translators: %s: directory path */
					esc_html__( 'Cache directory not writable: %s', 'performance-toolkit' ),
					$dir_path
				);
			}
		}

		return $results;
	}

	/**
	 * Check if a directory exists and is writable, creating it if necessary.
	 *
	 * @param string $dir_path Full path to directory
	 * @return bool True if directory is writable (or was created and is writable)
	 */
	public static function isDirectoryWritable( string $dir_path ): bool {
		if ( ! is_dir( $dir_path ) ) {
			// Try to create the directory
			if ( ! wp_mkdir_p( $dir_path ) ) {
				return false;
			}
		}

		return is_writable( $dir_path );
	}

	/**
	 * Get cached filesystem status (checks again after TTL expires).
	 *
	 * @return array{writable: bool, dirs: array<string, bool>, errors: string[]}
	 */
	public static function getCachedStatus(): array {
		$cached = get_transient( self::FILESYSTEM_STATUS_TRANSIENT );

		if ( is_array( $cached ) && isset( $cached['writable'] ) ) {
			return $cached;
		}

		$status = self::checkCacheDirectories();
		self::setCachedStatus( $status );

		return $status;
	}

	/**
	 * Update cached filesystem status.
	 *
	 * @param array<string, mixed> $status
	 */
	public static function setCachedStatus( array $status ): void {
		set_transient( self::FILESYSTEM_STATUS_TRANSIENT, $status, self::FILESYSTEM_STATUS_TTL );
	}

	/**
	 * Invalidate cached filesystem status (call when directories change).
	 */
	public static function invalidateCache(): void {
		delete_transient( self::FILESYSTEM_STATUS_TRANSIENT );
	}

	/**
	 * Perform a safe write test to verify actual write capability.
	 *
	 * @param string $dir_path Directory to test
	 * @return bool True if write test succeeds
	 */
	public static function testWrite( string $dir_path ): bool {
		if ( ! is_dir( $dir_path ) || ! is_writable( $dir_path ) ) {
			return false;
		}

		$test_file = $dir_path . '/.ptk-write-test-' . uniqid();

			$result = file_put_contents( $test_file, 'test' );

		if ( false !== $result ) {
			@unlink( $test_file );
			return true;
		}

		return false;
	}
}
