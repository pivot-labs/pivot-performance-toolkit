<?php
/**
 * Combine eligible CSS/JS files into fewer requests (HTTP/1.1 optimization).
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Optimization;

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\FilesystemCheck;
use PivotPerformanceToolkit\Utils\LocalAssetResolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Combine implements ModuleInterface {

	private const COMBINED_ASSETS_SUBDIR = 'cache/pivot-performance-toolkit/combined-assets';

	/**
	 * `type` attribute values eligible for combining. Deliberately excludes
	 * "module" — module scripts are deferred and scoped differently from
	 * classic scripts, so concatenating one into a classic-script bundle
	 * would change both its execution timing and its scoping semantics.
	 */
	private const JS_TYPES = array( '', 'text/javascript', 'application/javascript' );

	/**
	 * Handles that must never be combined, regardless of user-configured
	 * exclusions — mirrors DelayedJs::DEFAULT_EXCLUDED_HANDLES. jQuery in
	 * particular is depended on by handle/global timing by code this plugin
	 * can't see; combining it into a bundle changes when it becomes
	 * available relative to everything else on the page.
	 */
	private const DEFAULT_EXCLUDED_HANDLES = array(
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
		// Priority 14 — after Assets' inline minify (11), DelayedJs (12), and
		// AsyncCss (11), DelayedJs (12), Assets/minify (13). Priority 10 —
		// lowest of the four, so this runs *first* among them, seeing the
		// original <link>/<script> tags before anything else rewrites the
		// rel/type/src attributes this class depends on. Its own output
		// (fewer, combined tags) then flows into AsyncCss/DelayedJs/minify
		// as if they were the original tags — composing correctly with all
		// three instead of running blind to what they've already changed.
		// (Preserves the same relative order the old nested ob_start()
		// priorities, 14/13/12/11 closing innermost-first, produced.)
		add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'maybeCombineTags' ), 10 );
	}

	public function maybeCombineTags( string $html ): string {
		if ( is_admin() ) {
			return $html;
		}

		if ( is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return $html;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return $html;
		}

		$combine_css = $this->settings->getBool( 'combine_css' );
		$combine_js  = $this->settings->getBool( 'combine_js' );

		if ( ! $combine_css && ! $combine_js ) {
			return $html;
		}

		if ( '' === $html ) {
			return $html;
		}

		if ( $combine_css ) {
			$html = $this->combineRuns( $html, '#(?:<link\b[^>]*>\s*)+#i', '#<link\b[^>]*>#i', array( $this, 'combineCssRun' ) );
		}

		if ( $combine_js ) {
			$html = $this->combineRuns( $html, '#(?:<script\b[^>]*>.*?</script>\s*)+#is', '#<script\b[^>]*>.*?</script>#is', array( $this, 'combineJsRun' ) );
		}

		return $html;
	}

	/**
	 * Finds runs of consecutive (whitespace-only-separated) tags matching
	 * $tag_pattern and rewrites each run via $run_handler. Tags separated by
	 * anything else (other markup, text) are never grouped together, so
	 * combining can't reorder a stylesheet or script relative to whatever
	 * sits between two runs.
	 *
	 * @param callable $run_handler
	 */
	private function combineRuns( string $html, string $run_pattern, string $tag_pattern, callable $run_handler ): string {
		return preg_replace_callback(
			$run_pattern,
			function ( array $matches ) use ( $tag_pattern, $run_handler ): string {
				preg_match_all( $tag_pattern, $matches[0], $tag_matches );

				return $run_handler( $tag_matches[0] );
			},
			$html
		) ?? $html;
	}

	/**
	 * @param array<int, string> $tags
	 */
	private function combineCssRun( array $tags ): string {
		$output = '';
		$group  = array();

		foreach ( $tags as $tag ) {
			$eligible = $this->eligibleCssTag( $tag );

			if ( null !== $eligible ) {
				$group[] = $eligible;
				continue;
			}

			$output .= $this->flushCssGroup( $group ) . $tag;
			$group   = array();
		}

		return $output . $this->flushCssGroup( $group );
	}

	/**
	 * @param array<int, string> $tags
	 */
	private function combineJsRun( array $tags ): string {
		$output = '';
		$group  = array();

		foreach ( $tags as $tag ) {
			$eligible = $this->eligibleJsTag( $tag );

			if ( null !== $eligible ) {
				$group[] = $eligible;
				continue;
			}

			$output .= $this->flushJsGroup( $group ) . $tag;
			$group   = array();
		}

		return $output . $this->flushJsGroup( $group );
	}

	/**
	 * @return array{path: string, href: string, tag: string}|null
	 */
	private function eligibleCssTag( string $tag ): ?array {
		$rel = $this->extractAttribute( $tag, 'rel' );

		if ( 0 !== strcasecmp( $rel, 'stylesheet' ) ) {
			return null;
		}

		// Only combine default-media stylesheets. A print (or any other
		// conditional-media) stylesheet merged in with "all" content would
		// either apply where it shouldn't or vanish where it should apply —
		// there's no single media value that stays correct for the merge.
		$media = $this->extractAttribute( $tag, 'media' );

		if ( '' !== $media && 0 !== strcasecmp( $media, 'all' ) ) {
			return null;
		}

		$id   = $this->extractAttribute( $tag, 'id' );
		$href = $this->extractAttribute( $tag, 'href' );

		if ( '' === $href || $this->isDefaultExcludedHandle( $id ) || $this->isExcludedCss( $id, $href ) ) {
			return null;
		}

		$path = LocalAssetResolver::resolve( $href, 'css' );

		if ( null === $path ) {
			return null;
		}

		return array(
			'path' => $path,
			'href' => $href,
			'tag'  => $tag,
		);
	}

	/**
	 * @return array{path: string, tag: string}|null
	 */
	private function eligibleJsTag( string $tag ): ?array {
		if ( ! preg_match( '#<script\b([^>]*)>(.*?)</script>#is', $tag, $parts ) ) {
			return null;
		}

		$attributes = $parts[1];
		$inner      = $parts[2];

		// Only external scripts are combined — inline content has no file to
		// merge, and merging its position into a bundle would change when
		// relative to the page it runs.
		if ( '' !== trim( $inner ) ) {
			return null;
		}

		$type = $this->extractAttribute( $attributes, 'type' );

		if ( ! in_array( strtolower( $type ), self::JS_TYPES, true ) ) {
			return null;
		}

		// A bundle can only honor one async/defer state for all the files in
		// it. Rather than guess, only combine plain scripts that have
		// neither attribute — mirrors how Assets::addDeferAttribute() already
		// treats "has special loading behavior" as a reason to leave a
		// script alone.
		if ( preg_match( '/\basync\b/i', $attributes ) || preg_match( '/\bdefer\b/i', $attributes ) ) {
			return null;
		}

		$id  = $this->extractAttribute( $attributes, 'id' );
		$src = $this->extractAttribute( $attributes, 'src' );

		if ( '' === $src || $this->isDefaultExcludedHandle( $id ) || $this->hasInlineCompanionScript( $id ) || $this->isExcludedJs( $id, $src ) ) {
			return null;
		}

		$path = LocalAssetResolver::resolve( $src, 'js' );

		if ( null === $path ) {
			return null;
		}

		return array(
			'path' => $path,
			'tag'  => $tag,
		);
	}

	/**
	 * WordPress prints enqueued tags with id="{handle}-css"/"{handle}-js" —
	 * strip that suffix so it can be compared against raw handles.
	 */
	private function isDefaultExcludedHandle( string $id ): bool {
		if ( '' === $id ) {
			return false;
		}

		$handle = $this->stripHandleSuffix( $id );

		return in_array( $handle, self::DEFAULT_EXCLUDED_HANDLES, true );
	}

	private function stripHandleSuffix( string $id ): string {
		if ( str_ends_with( $id, '-css' ) ) {
			return substr( $id, 0, -4 );
		}

		if ( str_ends_with( $id, '-js' ) ) {
			return substr( $id, 0, -3 );
		}

		return $id;
	}

	/**
	 * The `defer` attribute (added elsewhere by Assets::addDeferAttribute())
	 * has no effect on inline scripts, so a script with inline code attached
	 * via wp_add_inline_script() always runs synchronously at its original
	 * position — combining its external half into a bundle placed elsewhere
	 * would run it before or after that inline companion instead of
	 * immediately alongside it, breaking any dependency between the two.
	 */
	private function hasInlineCompanionScript( string $id ): bool {
		if ( '' === $id || ! function_exists( 'wp_scripts' ) ) {
			return false;
		}

		$handle  = $this->stripHandleSuffix( $id );
		$scripts = wp_scripts();

		return (bool) $scripts->get_data( $handle, 'after' ) || (bool) $scripts->get_data( $handle, 'before' );
	}

	private function isExcludedCss( string $id, string $href ): bool {
		return $this->isExcluded( $this->settings->getLines( 'combine_css_exclusions' ), $id, $href );
	}

	private function isExcludedJs( string $id, string $src ): bool {
		return $this->isExcluded( $this->settings->getLines( 'combine_js_exclusions' ), $id, $src );
	}

	/**
	 * @param array<int, string> $rules
	 */
	private function isExcluded( array $rules, string $id, string $url ): bool {
		if ( array() === $rules ) {
			return false;
		}

		$path     = (string) ( wp_parse_url( $url, PHP_URL_PATH ) ?? '' );
		$basename = '' !== $path ? wp_basename( $path ) : '';

		foreach ( $rules as $rule ) {
			if ( $this->matchesRule( $rule, $id, $url, $path, $basename ) ) {
				return true;
			}
		}

		return false;
	}

	private function matchesRule( string $rule, string $id, string $url, string $path, string $basename ): bool {
		$rule = trim( $rule );

		if ( '' === $rule ) {
			return false;
		}

		$candidates = array_filter( array( $id, $url, $path, $basename ), static fn( string $value ): bool => '' !== $value );

		if ( str_contains( $rule, '*' ) ) {
			foreach ( $candidates as $candidate ) {
				if ( $this->wildcardMatch( $rule, $candidate ) ) {
					return true;
				}
			}

			return false;
		}

		foreach ( $candidates as $candidate ) {
			if ( 0 === strcasecmp( $rule, $candidate ) ) {
				return true;
			}
		}

		if ( str_starts_with( $rule, '/' ) && '' !== $path && str_starts_with( $path, $rule ) ) {
			return true;
		}

		return false;
	}

	private function wildcardMatch( string $pattern, string $value ): bool {
		$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i';

		return (bool) preg_match( $regex, $value );
	}

	private function extractAttribute( string $tag, string $name ): string {
		if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '\s*=\s*(["\'])(.*?)\1/i', $tag, $found ) ) {
			return html_entity_decode( $found[2], ENT_QUOTES );
		}

		return '';
	}

	/**
	 * @param array<int, array{path: string, href: string, tag: string}> $group
	 */
	private function flushCssGroup( array $group ): string {
		if ( array() === $group ) {
			return '';
		}

		if ( 1 === count( $group ) ) {
			return $group[0]['tag'];
		}

		$signature_parts = array();
		$contents        = array();

		foreach ( $group as $item ) {
			$content = file_get_contents( $item['path'] );

			if ( ! is_string( $content ) ) {
				return $this->originalTags( $group );
			}

			$mtime             = is_file( $item['path'] ) ? (string) filemtime( $item['path'] ) : '0';
			$signature_parts[] = $item['path'] . '|' . $mtime;
			$contents[]        = $this->rewriteRelativeCssUrls( $content, $item['href'] );
		}

		$target_web = $this->writeCombinedAsset( $signature_parts, implode( "\n", $contents ), 'css' );

		if ( null === $target_web ) {
			return $this->originalTags( $group );
		}

		return '<link rel="stylesheet" href="' . esc_url( $target_web ) . '">';
	}

	/**
	 * @param array<int, array{path: string, tag: string}> $group
	 */
	private function flushJsGroup( array $group ): string {
		if ( array() === $group ) {
			return '';
		}

		if ( 1 === count( $group ) ) {
			return $group[0]['tag'];
		}

		$signature_parts = array();
		$contents        = array();

		foreach ( $group as $item ) {
			$content = file_get_contents( $item['path'] );

			if ( ! is_string( $content ) ) {
				return $this->originalTags( $group );
			}

			$mtime             = is_file( $item['path'] ) ? (string) filemtime( $item['path'] ) : '0';
			$signature_parts[] = $item['path'] . '|' . $mtime;

			// A trailing semicolon guards against automatic-semicolon-insertion
			// breakage where one file's final statement runs into the next
			// file's first token — harmless even if the file already ends
			// with one. Deliberately not IIFE-wrapped: enqueued scripts
			// routinely rely on defining page-global variables/functions
			// (e.g. localized config objects) that other, non-combined code
			// on the page reads — wrapping would silently break those.
			$contents[] = rtrim( $content ) . ";\n";
		}

		$target_web = $this->writeCombinedAsset( $signature_parts, implode( '', $contents ), 'js' );

		if ( null === $target_web ) {
			return $this->originalTags( $group );
		}

		return '<script src="' . esc_url( $target_web ) . '"></script>';
	}

	/**
	 * @param array<int, array{tag: string}> $group
	 */
	private function originalTags( array $group ): string {
		return implode( '', array_column( $group, 'tag' ) );
	}

	/**
	 * Writes $content to the combined-assets cache dir under a name derived
	 * from $signature_parts (source paths + mtimes), reusing an existing
	 * file if the signature already matches. Returns the public URL, or null
	 * if the write couldn't happen — callers fall back to the original tags
	 * rather than ship a page with a missing stylesheet/script.
	 *
	 * @param array<int, string> $signature_parts
	 */
	private function writeCombinedAsset( array $signature_parts, string $content, string $extension ): ?string {
		$cache_dir = WP_CONTENT_DIR . '/' . self::COMBINED_ASSETS_SUBDIR;

		if ( ! is_dir( $cache_dir ) && ! wp_mkdir_p( $cache_dir ) ) {
			return null;
		}

		if ( ! FilesystemCheck::isDirectoryWritable( $cache_dir ) ) {
			return null;
		}

		$target_name = md5( implode( '|', $signature_parts ) ) . '.' . $extension;
		$target_path = $cache_dir . '/' . $target_name;
		$target_web  = content_url( self::COMBINED_ASSETS_SUBDIR . '/' . $target_name );

		if ( is_file( $target_path ) ) {
			return $target_web;
		}

		// phpcs:ignore PluginCheck.CodeAnalysis.WriteFile.PluginDirectoryWrite -- $target_path resolves under WP_CONTENT_DIR . '/cache/pivot-performance-toolkit/...', a sibling of wp-content/plugins/, not inside the plugin's own folder; it's untouched by plugin upgrades/reinstalls.
		$written = file_put_contents( $target_path, $content, LOCK_EX );

		if ( false === $written ) {
			FilesystemCheck::invalidateCache();
			return null;
		}

		return $target_web;
	}

	/**
	 * Rewrites url(...) references in $css that are relative to the source
	 * file's own location into root-relative paths, so they still resolve
	 * correctly once combined into a bundle served from a different
	 * directory. Already-absolute references (http(s)://, //, /, data:, #)
	 * are left untouched since they don't depend on the source file's
	 * location. Known limitation shared with any naive CSS combiner: a
	 * non-first source file's own @import/@charset rules are not hoisted or
	 * deduplicated, since those are only valid as the very first rule(s) in
	 * a stylesheet.
	 */
	private function rewriteRelativeCssUrls( string $css, string $source_href ): string {
		return preg_replace_callback(
			'/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/i',
			function ( array $matches ) use ( $source_href ): string {
				$quote = $matches[1];
				$ref   = trim( $matches[2] );

				if (
					'' === $ref
					|| str_starts_with( $ref, 'data:' )
					|| str_starts_with( $ref, '#' )
					|| str_starts_with( $ref, 'http://' )
					|| str_starts_with( $ref, 'https://' )
					|| str_starts_with( $ref, '//' )
					|| str_starts_with( $ref, '/' )
				) {
					return $matches[0];
				}

				return 'url(' . $quote . $this->resolveRelativeCssUrl( $source_href, $ref ) . $quote . ')';
			},
			$css
		) ?? $css;
	}

	private function resolveRelativeCssUrl( string $source_href, string $relative ): string {
		$suffix    = '';
		$query_pos = strcspn( $relative, '?#' );

		if ( $query_pos < strlen( $relative ) ) {
			$suffix   = substr( $relative, $query_pos );
			$relative = substr( $relative, 0, $query_pos );
		}

		$base_path = dirname( (string) ( wp_parse_url( $source_href, PHP_URL_PATH ) ?? '/' ) );
		$combined  = rtrim( $base_path, '/' ) . '/' . ltrim( $relative, '/' );

		$stack = array();

		foreach ( explode( '/', $combined ) as $segment ) {
			if ( '' === $segment || '.' === $segment ) {
				continue;
			}

			if ( '..' === $segment ) {
				array_pop( $stack );
				continue;
			}

			$stack[] = $segment;
		}

		return '/' . implode( '/', $stack ) . $suffix;
	}
}
