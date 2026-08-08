<?php
/**
 * Applies browser cache/compression headers to .htaccess automatically.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class HtaccessCacheHeaders {

	private const MARKER_START = '# BEGIN Pivot Performance Toolkit - Browser Cache & Compression';
	private const MARKER_END   = '# END Pivot Performance Toolkit - Browser Cache & Compression';

	public static function snippet(): string {
		return self::MARKER_START . "\n" . <<<'HTACCESS'
<IfModule mod_expires.c>
    ExpiresActive On

    # Cache HTML for 1 hour
    ExpiresByType text/html "access plus 1 hour"

    # Cache CSS/JS for 1 year (versioned assets like style.css?v=123)
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"

    # Cache images for 1 month
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"

    # Cache fonts for 1 year
    ExpiresByType font/ttf "access plus 1 year"
    ExpiresByType font/otf "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
    ExpiresByType application/font-woff "access plus 1 year"

    # Default expiration
    ExpiresDefault "access plus 2 days"
</IfModule>

<IfModule mod_deflate.c>
    # Gzip compression for text-based files
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE text/javascript
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/x-httpd-php
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/atom+xml
    AddOutputFilterByType DEFLATE image/svg+xml

    # Disable for broken browsers
    BrowserMatch ^Mozilla/4 gzip-only-text/html
    BrowserMatch ^Mozilla/4\.0[678] no-gzip
    BrowserMatch \bMSIE !no-gzip !gzip-only-text/html
    Header append Vary User-Agent
</IfModule>

<IfModule mod_headers.c>
    # Cache control headers for versioned assets
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf)$">
        Header set Cache-Control "max-age=31536000, immutable"
    </FilesMatch>

    # Cache control for HTML (revalidate frequently)
    <FilesMatch "\.html$">
        Header set Cache-Control "max-age=3600, must-revalidate"
    </FilesMatch>

    # Security headers
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
</IfModule>
HTACCESS
			. "\n" . self::MARKER_END;
	}

	private static function htaccessPath(): string {
		return ABSPATH . '.htaccess';
	}

	/**
	 * Whether our block is currently present in .htaccess.
	 */
	public static function isApplied(): bool {
		$path = self::htaccessPath();

		if ( ! file_exists( $path ) ) {
			return false;
		}

		$contents = (string) file_get_contents( $path );

		return str_contains( $contents, self::MARKER_START );
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public static function apply(): array {
		$wp_filesystem = self::initFilesystem();

		if ( null === $wp_filesystem ) {
			return array(
				'success' => false,
				'message' => __( "Pivot Performance Toolkit couldn't access the filesystem to update .htaccess — add the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		$path = self::htaccessPath();

		if ( $wp_filesystem->exists( $path ) && ! $wp_filesystem->is_writable( $path ) ) {
			return array(
				'success' => false,
				'message' => __( ".htaccess isn't writable — add the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		$contents = $wp_filesystem->exists( $path ) ? (string) $wp_filesystem->get_contents( $path ) : '';
		$stripped = self::stripBlock( $contents );

		// Insert before WordPress' own block if present, otherwise prepend —
		// never inside/after it, to avoid interfering with WP's rewrite rules.
		$wp_marker = '# BEGIN WordPress';
		$new_block = self::snippet() . "\n\n";

		if ( str_contains( $stripped, $wp_marker ) ) {
			$new_contents = (string) preg_replace(
				'/(' . preg_quote( $wp_marker, '/' ) . ')/',
				$new_block . '$1',
				$stripped,
				1
			);
		} else {
			$new_contents = $new_block . $stripped;
		}

		$written = $wp_filesystem->put_contents( $path, $new_contents, FS_CHMOD_FILE );

		if ( false === $written ) {
			return array(
				'success' => false,
				'message' => __( "Pivot Performance Toolkit couldn't write to .htaccess — add the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Browser cache headers applied to .htaccess.', 'pivot-performance-toolkit' ),
		);
	}

	/**
	 * @return array{success: bool, message: string}
	 */
	public static function remove(): array {
		$path = self::htaccessPath();

		if ( ! file_exists( $path ) ) {
			return array(
				'success' => true,
				'message' => __( 'Nothing to remove.', 'pivot-performance-toolkit' ),
			);
		}

		$wp_filesystem = self::initFilesystem();

		if ( null === $wp_filesystem ) {
			return array(
				'success' => false,
				'message' => __( "Pivot Performance Toolkit couldn't access the filesystem to update .htaccess — remove the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		if ( ! $wp_filesystem->is_writable( $path ) ) {
			return array(
				'success' => false,
				'message' => __( ".htaccess isn't writable — remove the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		$contents = (string) $wp_filesystem->get_contents( $path );
		$new      = self::stripBlock( $contents );

		if ( $new === $contents ) {
			return array(
				'success' => true,
				'message' => __( 'Nothing to remove.', 'pivot-performance-toolkit' ),
			);
		}

		$written = $wp_filesystem->put_contents( $path, $new, FS_CHMOD_FILE );

		if ( false === $written ) {
			return array(
				'success' => false,
				'message' => __( "Pivot Performance Toolkit couldn't write to .htaccess — remove the snippet manually, or fix file permissions.", 'pivot-performance-toolkit' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Browser cache headers removed from .htaccess.', 'pivot-performance-toolkit' ),
		);
	}

	private static function stripBlock( string $contents ): string {
		$pattern = '/' . preg_quote( self::MARKER_START, '/' ) . '.*?' . preg_quote( self::MARKER_END, '/' ) . '\n*/s';

		return (string) preg_replace( $pattern, '', $contents );
	}

	/**
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
}
