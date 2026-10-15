<?php
/**
 * Resolves an enqueued asset URL to a readable local file path.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class LocalAssetResolver {

	/**
	 * Resolves $url to a local, readable file path if it is a same-origin
	 * asset with the given extension, or null if it's external, missing,
	 * unreadable, or would resolve outside the WordPress directories.
	 */
	public static function resolve( string $url, string $extension ): ?string {
		$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );

		if ( '' === $path || ! str_ends_with( strtolower( $path ), '.' . strtolower( $extension ) ) ) {
			return null;
		}

		$url_host  = (string) ( wp_parse_url( $url, PHP_URL_HOST ) ?? '' );
		$home_host = (string) ( wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' );

		if ( '' !== $url_host && 0 !== strcasecmp( $url_host, $home_host ) ) {
			return null;
		}

		foreach ( self::roots() as $root_path => $root_dir ) {
			if ( ! str_starts_with( $path, $root_path ) ) {
				continue;
			}

			$real = self::resolveUnder( $root_dir, substr( $path, strlen( $root_path ) ) );

			if ( null !== $real ) {
				return $real;
			}
		}

		return null;
	}

	/**
	 * URL path prefix => filesystem directory, most specific prefix first.
	 *
	 * A URL path cannot be turned into a file path by prefixing the WordPress root:
	 * sites that move wp-content, the uploads folder, or install core in a
	 * subdirectory serve assets from URL prefixes that differ from the directory
	 * layout. Each prefix is therefore taken from the matching WordPress URL
	 * function and paired with the directory WordPress documents for it.
	 * WP_CONTENT_DIR and ABSPATH are the only definitions of those directories:
	 * WordPress has no function that returns them (wp_upload_dir() covers uploads
	 * only), and an asset can live in any of them.
	 *
	 * @return array<string, string>
	 */
	private static function roots(): array {
		$uploads = wp_upload_dir( null, false );
		$roots   = array(
			self::urlPath( content_url() ) => WP_CONTENT_DIR,
			self::urlPath( site_url() )    => ABSPATH,
		);

		if ( empty( $uploads['error'] ) && is_string( $uploads['baseurl'] ?? null ) && is_string( $uploads['basedir'] ?? null ) ) {
			$roots[ self::urlPath( $uploads['baseurl'] ) ] = $uploads['basedir'];
		}

		uksort(
			$roots,
			static fn( string $a, string $b ): int => strlen( $b ) <=> strlen( $a )
		);

		return $roots;
	}

	private static function urlPath( string $url ): string {
		return trailingslashit( (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' ) );
	}

	/**
	 * $relative joined to $root_dir, if it exists, is readable and stays inside $root_dir.
	 */
	private static function resolveUnder( string $root_dir, string $relative ): ?string {
		$real = realpath( trailingslashit( $root_dir ) . ltrim( $relative, '/' ) );
		$root = realpath( $root_dir );

		if ( false === $real || false === $root || ! str_starts_with( $real, trailingslashit( $root ) ) ) {
			return null;
		}

		if ( ! is_file( $real ) || ! is_readable( $real ) ) {
			return null;
		}

		return $real;
	}
}
