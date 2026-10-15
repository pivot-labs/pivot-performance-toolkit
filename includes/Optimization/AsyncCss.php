<?php
/**
 * Load non-critical CSS asynchronously to eliminate render-blocking stylesheets.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Optimization;

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\RequestInput;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AsyncCss implements ModuleInterface {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		// Priority 11: runs second among this plugin's four
		// wp_template_enhancement_output_buffer callbacks, after Combine
		// (10) and before DelayedJs (12) and Assets/minify (13) — preserves
		// the same relative transform order the old nested ob_start()
		// priorities (13/14/12/11, closing innermost-first) produced.
		add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'maybeAsyncStylesheetTags' ), 11 );
	}

	public function maybeAsyncStylesheetTags( string $html ): string {
		if ( ! $this->settings->getBool( 'async_css_loading' ) || is_admin() ) {
			return $html;
		}

		if ( is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return $html;
		}

		if ( ! RequestInput::isGetRequest() ) {
			return $html;
		}

		return $this->asyncStylesheetTags( $html );
	}

	public function asyncStylesheetTags( string $html ): string {
		if ( '' === $html ) {
			return $html;
		}

		return preg_replace_callback(
			'#<link\b[^>]*>#i',
			array( $this, 'maybeAsyncLinkTag' ),
			$html
		) ?? $html;
	}

	private function maybeAsyncLinkTag( array $matches ): string {
		$tag = $matches[0];

		$rel = $this->extractAttribute( $tag, 'rel' );

		if ( 0 !== strcasecmp( $rel, 'stylesheet' ) ) {
			return $tag;
		}

		$media = $this->extractAttribute( $tag, 'media' );

		// A print stylesheet is already non-render-blocking for the visible
		// page; leave it alone rather than adding pointless preload overhead.
		if ( 0 === strcasecmp( $media, 'print' ) ) {
			return $tag;
		}

		$id   = $this->extractAttribute( $tag, 'id' );
		$href = $this->extractAttribute( $tag, 'href' );

		if ( '' === $href || $this->isExcluded( $id, $href ) ) {
			return $tag;
		}

		// Rewrite the existing tag with the HTML API instead of assembling a new one: every
		// attribute it already has (id, media, crossorigin, ...) survives, and a non-default
		// media must, since once the onload handler sets rel="stylesheet" an absent media
		// attribute defaults to "all" and would make a scoped stylesheet apply globally.
		$processor = new \WP_HTML_Tag_Processor( $tag );

		if ( ! $processor->next_tag( 'link' ) ) {
			return $tag;
		}

		$processor->set_attribute( 'rel', 'preload' );
		$processor->set_attribute( 'as', 'style' );
		$processor->set_attribute( 'onload', "this.onload=null;this.rel='stylesheet'" );

		// The untouched original is the no-JavaScript fallback.
		return $processor->get_updated_html() . '<noscript>' . $tag . '</noscript>';
	}

	private function isExcluded( string $id, string $href ): bool {
		$rules = $this->settings->getLines( 'async_css_exclusions' );

		if ( array() === $rules ) {
			return false;
		}

		$path     = (string) ( wp_parse_url( $href, PHP_URL_PATH ) ?? '' );
		$basename = '' !== $path ? wp_basename( $path ) : '';

		foreach ( $rules as $rule ) {
			if ( $this->matchesRule( $rule, $id, $href, $path, $basename ) ) {
				return true;
			}
		}

		return false;
	}

	private function matchesRule( string $rule, string $id, string $href, string $path, string $basename ): bool {
		$rule = trim( $rule );

		if ( '' === $rule ) {
			return false;
		}

		$candidates = array_filter( array( $id, $href, $path, $basename ), static fn( string $value ): bool => '' !== $value );

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
}
