<?php
/**
 * Where the plugin stores the minified and combined CSS/JS files it generates.
 *
 * These files are public static assets that the browser requests by URL, so they
 * live in a folder named after the plugin inside the uploads directory, resolved
 * at runtime with wp_upload_dir() (never a hard-coded path). The page cache and
 * the advanced-cache.php / object-cache.php drop-ins are different: they must be
 * readable before WordPress has loaded, so they stay in wp-content as a caching
 * plugin's files normally do.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AssetStorage {

	public const MINIFIED = 'minified-assets';
	public const COMBINED = 'combined-assets';

	private const FOLDER = 'pivot-performance-toolkit';

	/**
	 * File name patterns the plugin generates in each folder.
	 *
	 * @var array<string, array<int, string>>
	 */
	private const PATTERNS = array(
		self::MINIFIED => array( '*.min.css', '*.min.js' ),
		self::COMBINED => array( '*.css', '*.js' ),
	);

	/**
	 * Absolute directory for $kind, or null when the uploads directory is unavailable.
	 */
	public static function dir( string $kind ): ?string {
		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) || ! is_string( $uploads['basedir'] ?? null ) ) {
			return null;
		}

		return untrailingslashit( $uploads['basedir'] ) . '/' . self::FOLDER . '/' . $kind;
	}

	/**
	 * Public URL of a file named $name in the $kind folder.
	 */
	public static function url( string $kind, string $name ): ?string {
		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) || ! is_string( $uploads['baseurl'] ?? null ) ) {
			return null;
		}

		return untrailingslashit( $uploads['baseurl'] ) . '/' . self::FOLDER . '/' . $kind . '/' . $name;
	}

	/**
	 * Generated files for $kind.
	 *
	 * @return array<int, string>
	 */
	public static function files( string $kind ): array {
		$dir = self::dir( $kind );

		if ( null === $dir ) {
			return array();
		}

		$files = array();

		foreach ( self::PATTERNS[ $kind ] ?? array() as $pattern ) {
			foreach ( glob( $dir . '/' . $pattern ) ?: array() as $file ) {
				$files[] = $file;
			}
		}

		return $files;
	}

	/**
	 * Deletes every generated file of the given kinds (all kinds by default).
	 * Returns how many files were removed.
	 *
	 * @param array<int, string>|null $kinds
	 */
	public static function clear( ?array $kinds = null ): int {
		$removed = 0;

		foreach ( $kinds ?? array( self::MINIFIED, self::COMBINED ) as $kind ) {
			foreach ( self::files( $kind ) as $file ) {
				if ( wp_delete_file( $file ) || ! file_exists( $file ) ) {
					++$removed;
				}
			}
		}

		// Releases before 1.1.2 kept these files under wp-content/cache; remove what they left.
		foreach ( $kinds ?? array( self::MINIFIED, self::COMBINED ) as $kind ) {
			foreach ( self::PATTERNS[ $kind ] ?? array() as $pattern ) {
				foreach ( glob( WP_CONTENT_DIR . '/cache/' . self::FOLDER . '/' . $kind . '/' . $pattern ) ?: array() as $file ) {
					wp_delete_file( $file );
				}
			}
		}

		return $removed;
	}

	/**
	 * @return array{count:int,bytes:int}
	 */
	public static function stats( string $kind ): array {
		$count = 0;
		$bytes = 0;

		foreach ( self::files( $kind ) as $file ) {
			++$count;
			$bytes += (int) @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a file may vanish between glob() and filesize().
		}

		return array(
			'count' => $count,
			'bytes' => $bytes,
		);
	}
}
