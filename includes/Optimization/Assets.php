<?php
/**
 * Script and stylesheet optimization handler.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Optimization;

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\FilesystemCheck;

final class Assets implements ModuleInterface {

	private const MINIFIED_ASSETS_SUBDIR = 'cache/pivot-performance-toolkit/minified-assets';

	private const EXCLUDED_HANDLES = array(
		'jquery',
		'jquery-core',
		'jquery-migrate',
		'wp-polyfill',
	);

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_filter( 'script_loader_tag', array( $this, 'minifyScriptTag' ), 9, 3 );
		add_filter( 'script_loader_tag', array( $this, 'addDeferAttribute' ), 10, 3 );
		add_filter( 'style_loader_tag', array( $this, 'minifyStylesheetTag' ), 10, 4 );
		add_action( 'template_redirect', array( $this, 'startOutputMinification' ), 11 );
	}

	public function addDeferAttribute( string $tag, string $handle, string $src ): string {
		if ( ! $this->settings->getBool( 'defer_scripts' ) || is_admin() ) {
			return $tag;
		}

		if ( in_array( $handle, self::EXCLUDED_HANDLES, true ) ) {
			return $tag;
		}

		if ( str_contains( $tag, ' defer' ) ) {
			return $tag;
		}

		return str_replace( '<script ', '<script defer ', $tag );
	}

	public function minifyScriptTag( string $tag, string $handle, string $src ): string {
		if ( is_admin() || ! $this->settings->getBool( 'minify_external_js' ) || '' === $src ) {
			return $tag;
		}

		if ( $this->isExcludedExternalJs( $handle, $src ) ) {
			return $tag;
		}

		$source_path = $this->resolveLocalAssetPathFromUrl( $src, 'js' );

		if ( null === $source_path || str_ends_with( $source_path, '.min.js' ) ) {
			return $tag;
		}

		$minified_url = $this->buildMinifiedJsUrl( $source_path );

		if ( null === $minified_url ) {
			return $tag;
		}

		return str_replace( $src, $minified_url, $tag );
	}

	public function minifyStylesheetTag( string $html, string $handle, string $href, string $media ): string {
		if ( is_admin() || ! $this->settings->getBool( 'minify_external_css' ) || '' === $href ) {
			return $html;
		}

		if ( $this->isExcludedExternalCss( $handle, $href ) ) {
			return $html;
		}

		$source_path = $this->resolveLocalAssetPathFromUrl( $href, 'css' );

		if ( null === $source_path || str_ends_with( $source_path, '.min.css' ) ) {
			return $html;
		}

		$minified_url = $this->buildMinifiedCssUrl( $source_path );

		if ( null === $minified_url ) {
			return $html;
		}

		return str_replace( $href, $minified_url, $html );
	}

	public function startOutputMinification(): void {
		if ( is_admin() || is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return;
		}

		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) !== 'GET' ) {
			return;
		}

		$minify_html = $this->settings->getBool( 'minify_html' );
		$minify_css  = $this->settings->getBool( 'minify_css' );
		$minify_js   = $this->settings->getBool( 'minify_js' );

		if ( ! $minify_html && ! $minify_css && ! $minify_js ) {
			return;
		}

		ob_start(
			function ( string $html ) use ( $minify_html, $minify_css, $minify_js ): string {
				if ( '' === $html ) {
					return $html;
				}

				if ( $minify_css ) {
					$html = preg_replace_callback(
						'#<style\b([^>]*)>(.*?)</style>#is',
						static function ( array $matches ): string {
							return '<style' . $matches[1] . '>' . self::minifyCss( $matches[2] ) . '</style>';
						},
						$html
					) ?? $html;
				}

				if ( $minify_js ) {
					$html = preg_replace_callback(
						'#<script\b([^>]*)>(.*?)</script>#is',
						static function ( array $matches ): string {
							if ( '' === trim( $matches[2] ) ) {
								return $matches[0];
							}

							return '<script' . $matches[1] . '>' . self::minifyJs( $matches[2] ) . '</script>';
						},
						$html
					) ?? $html;
				}

				if ( $minify_html ) {
					$html = self::minifyHtml( $html );
				}

				return $html;
			}
		);
	}

	private static function minifyHtml( string $html ): string {
		// Remove non-conditional comments.
		$html = preg_replace( '/<!--(?!\s*\[if).*?-->/s', '', $html ) ?? $html;
		// Collapse whitespace between tags.
		$html = preg_replace( '/>\s+</', '><', $html ) ?? $html;

		return trim( $html );
	}

	private static function minifyCss( string $css ): string {
		$css = preg_replace( '#/\*[^!].*?\*/#s', '', $css ) ?? $css;
		$css = preg_replace( '/\s+/', ' ', $css ) ?? $css;
		$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css ) ?? $css;
		$css = str_replace( ';}', '}', $css );

		return trim( $css );
	}

	private function isExcludedExternalCss( string $handle, string $href ): bool {
		$rules = $this->settings->getLines( 'minify_external_css_exclusions' );

		if ( array() === $rules ) {
			return false;
		}

		$path     = (string) ( wp_parse_url( $href, PHP_URL_PATH ) ?? '' );
		$basename = '' !== $path ? wp_basename( $path ) : '';

		foreach ( $rules as $rule ) {
			if ( $this->matchesExternalAssetRule( $rule, $handle, $href, $path, $basename ) ) {
				return true;
			}
		}

		return false;
	}

	private function isExcludedExternalJs( string $handle, string $src ): bool {
		$rules = $this->settings->getLines( 'minify_external_js_exclusions' );

		if ( array() === $rules ) {
			return false;
		}

		$path     = (string) ( wp_parse_url( $src, PHP_URL_PATH ) ?? '' );
		$basename = '' !== $path ? wp_basename( $path ) : '';

		foreach ( $rules as $rule ) {
			if ( $this->matchesExternalAssetRule( $rule, $handle, $src, $path, $basename ) ) {
				return true;
			}
		}

		return false;
	}

	private function matchesExternalAssetRule( string $rule, string $handle, string $href, string $path, string $basename ): bool {
		$rule = trim( $rule );

		if ( '' === $rule ) {
			return false;
		}

		$candidates = array_filter( array( $handle, $href, $path, $basename ), 'is_string' );

		if ( str_contains( $rule, '*' ) ) {
			foreach ( $candidates as $candidate ) {
				if ( '' !== $candidate && $this->wildcardMatch( $rule, $candidate ) ) {
					return true;
				}
			}

			return false;
		}

		if ( 0 === strcasecmp( $rule, $handle ) || 0 === strcasecmp( $rule, $basename ) || 0 === strcasecmp( $rule, $path ) || 0 === strcasecmp( $rule, $href ) ) {
			return true;
		}

		if ( '' !== $rule && str_starts_with( $rule, '/' ) && '' !== $path && str_starts_with( $path, $rule ) ) {
			return true;
		}

		return false;
	}

	private function wildcardMatch( string $pattern, string $value ): bool {
		$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';

		return (bool) preg_match( $regex, $value );
	}

	private function resolveLocalAssetPathFromUrl( string $url, string $extension ): ?string {
		$path = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );

		if ( '' === $path || ! str_ends_with( strtolower( $path ), '.' . strtolower( $extension ) ) ) {
			return null;
		}

		$url_host  = (string) ( wp_parse_url( $url, PHP_URL_HOST ) ?? '' );
		$home_host = (string) ( wp_parse_url( home_url(), PHP_URL_HOST ) ?? '' );

		if ( '' !== $url_host && 0 !== strcasecmp( $url_host, $home_host ) ) {
			return null;
		}

		$absolute = ABSPATH . ltrim( $path, '/' );
		$real     = realpath( $absolute );
		$root     = realpath( ABSPATH );

		if ( false === $real || false === $root || ! str_starts_with( $real, $root ) ) {
			return null;
		}

		if ( ! is_file( $real ) || ! is_readable( $real ) ) {
			return null;
		}

		return $real;
	}

	private function buildMinifiedCssUrl( string $source_path ): ?string {
		$content = file_get_contents( $source_path );

		if ( ! is_string( $content ) || '' === $content ) {
			return null;
		}

		$cache_dir = WP_CONTENT_DIR . '/' . self::MINIFIED_ASSETS_SUBDIR;

		if ( ! is_dir( $cache_dir ) && ! wp_mkdir_p( $cache_dir ) ) {
			return null;
		}

		// Check if cache directory is writable.
		if ( ! FilesystemCheck::isDirectoryWritable( $cache_dir ) ) {
			return null;
		}

		$signature   = $source_path . '|' . ( is_file( $source_path ) ? (string) filemtime( $source_path ) : '0' ) . '|' . (string) strlen( $content );
		$target_name = md5( $signature ) . '.min.css';
		$target_path = $cache_dir . '/' . $target_name;
		$target_web  = content_url( self::MINIFIED_ASSETS_SUBDIR . '/' . $target_name );

		if ( ! is_file( $target_path ) ) {
			$minified = self::minifyCss( $content );

			if ( '' === $minified ) {
				return null;
			}

			$written = file_put_contents( $target_path, $minified, LOCK_EX );

			if ( false === $written ) {
				FilesystemCheck::invalidateCache();
				return null;
			}
		}

		return $target_web;
	}

	private function buildMinifiedJsUrl( string $source_path ): ?string {
		$content = file_get_contents( $source_path );

		if ( ! is_string( $content ) || '' === $content ) {
			return null;
		}

		$cache_dir = WP_CONTENT_DIR . '/' . self::MINIFIED_ASSETS_SUBDIR;

		if ( ! is_dir( $cache_dir ) && ! wp_mkdir_p( $cache_dir ) ) {
			return null;
		}

		// Check if cache directory is writable.
		if ( ! FilesystemCheck::isDirectoryWritable( $cache_dir ) ) {
			return null;
		}

		$signature   = $source_path . '|' . ( is_file( $source_path ) ? (string) filemtime( $source_path ) : '0' ) . '|' . (string) strlen( $content );
		$target_name = md5( $signature ) . '.min.js';
		$target_path = $cache_dir . '/' . $target_name;
		$target_web  = content_url( self::MINIFIED_ASSETS_SUBDIR . '/' . $target_name );

		if ( ! is_file( $target_path ) ) {
			$minified = self::minifyJs( $content );

			if ( '' === $minified ) {
				return null;
			}

			$written = file_put_contents( $target_path, $minified, LOCK_EX );

			if ( false === $written ) {
				FilesystemCheck::invalidateCache();
				return null;
			}
		}

		return $target_web;
	}

	private static function minifyJs( string $js ): string {
		// Keep this conservative to avoid changing string/regex semantics.
		$js = preg_replace( '/^\s*\/\/.*$/m', '', $js ) ?? $js;
		$js = preg_replace( '/^\s*\/\*[\s\S]*?\*\/\s*$/m', '', $js ) ?? $js;
		$js = preg_replace( '/\n{2,}/', "\n", $js ) ?? $js;

		return trim( $js );
	}
}
