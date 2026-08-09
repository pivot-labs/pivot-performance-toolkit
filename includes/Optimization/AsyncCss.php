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

final class AsyncCss implements ModuleInterface {

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'template_redirect', array( $this, 'startOutputAsync' ), 13 );
	}

	public function startOutputAsync(): void {
		if ( ! $this->settings->getBool( 'async_css_loading' ) || is_admin() ) {
			return;
		}

		if ( is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return;
		}

		ob_start( array( $this, 'asyncStylesheetTags' ) );
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

		$id_attr = '' !== $id ? ' id="' . esc_attr( $id ) . '"' : '';

		// A non-default media (e.g. a responsive/conditional stylesheet) must
		// survive the swap below — once the onload handler sets rel="stylesheet",
		// an absent media attribute defaults to "all", so dropping it here would
		// make a scoped stylesheet apply globally.
		$media_attr = ( '' !== $media && 0 !== strcasecmp( $media, 'all' ) ) ? ' media="' . esc_attr( $media ) . '"' : '';

		$preload = '<link rel="preload" as="style" href="' . esc_url( $href ) . '"' . $id_attr . $media_attr
			. ' onload="this.onload=null;this.rel=&#039;stylesheet&#039;">';

		$noscript = '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"' . $id_attr . $media_attr . '></noscript>';

		return $preload . $noscript;
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
