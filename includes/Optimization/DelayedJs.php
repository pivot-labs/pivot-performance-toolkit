<?php
/**
 * Delay JavaScript execution until user interaction or a fallback timeout.
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

final class DelayedJs implements ModuleInterface {

	private const PLACEHOLDER_TYPE = 'pivotperformancetoolkit/delayed-js';
	public const RUNTIME_SCRIPT_ID = 'pivot-performance-toolkit-delayed-js-runtime';

	/**
	 * `type` attribute values that mark a <script> as executable JavaScript.
	 * Anything else (application/ld+json, text/template, text/html, ...) is
	 * data or a template literal, not code — must never be delayed/rewritten.
	 */
	private const JS_TYPES = array(
		'',
		'text/javascript',
		'application/javascript',
		'module',
	);

	private const FALLBACK_TIMEOUT_MS = 8000;

	/**
	 * Handles that must never be delayed, regardless of user-configured
	 * exclusions — mirrors Assets::EXCLUDED_HANDLES. Delaying jQuery (or its
	 * polyfills) breaks any inline script on the page that assumes it's
	 * already available, which is common enough that this needs to be a
	 * built-in default, not something the user has to discover and exclude
	 * manually before their first test.
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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueueRuntimeScript' ) );
		// Priority 12: runs third among this plugin's four
		// wp_template_enhancement_output_buffer callbacks, after Combine
		// (10) and AsyncCss (11), before Assets/minify (13) — preserves the
		// same relative transform order the old nested ob_start()
		// priorities (14/13/12/11, closing innermost-first) produced.
		add_filter( 'wp_template_enhancement_output_buffer', array( $this, 'maybeDelayScriptTags' ), 12 );
	}

	/**
	 * Enqueues the runtime that restores delayed scripts (assets/js/delayed-js-runtime.js).
	 * WordPress prints its tag with id="{handle}-js" and its localized settings with
	 * id="{handle}-js-extra"; maybeDelayScriptTag() below skips both so the runtime
	 * never delays itself. Assets and Combine list the handle too, so it is neither
	 * deferred nor folded into a bundle that would itself be delayed.
	 */
	public function enqueueRuntimeScript(): void {
		if ( ! $this->settings->getBool( 'delay_js_execution' ) || is_admin() ) {
			return;
		}

		wp_register_script(
			self::RUNTIME_SCRIPT_ID,
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'assets/js/delayed-js-runtime.js',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION,
			false
		);
		wp_localize_script(
			self::RUNTIME_SCRIPT_ID,
			'pivotPerformanceToolkitDelayedJs',
			array(
				'placeholderType' => self::PLACEHOLDER_TYPE,
				'timeoutMs'       => self::FALLBACK_TIMEOUT_MS,
			)
		);
		wp_enqueue_script( self::RUNTIME_SCRIPT_ID );
	}

	public function maybeDelayScriptTags( string $html ): string {
		if ( ! $this->settings->getBool( 'delay_js_execution' ) || is_admin() ) {
			return $html;
		}

		if ( is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return $html;
		}

		if ( ! RequestInput::isGetRequest() ) {
			return $html;
		}

		return $this->delayScriptTags( $html );
	}

	public function delayScriptTags( string $html ): string {
		if ( '' === $html ) {
			return $html;
		}

		return preg_replace_callback(
			'#<script\b([^>]*)>(.*?)</script>#is',
			array( $this, 'maybeDelayScriptTag' ),
			$html
		) ?? $html;
	}

	/**
	 * @param array<int, string> $matches
	 */
	private function maybeDelayScriptTag( array $matches ): string {
		$attributes_raw = $matches[1];
		$inner          = $matches[2];

		$id   = $this->extractAttribute( $attributes_raw, 'id' );
		$src  = $this->extractAttribute( $attributes_raw, 'src' );
		$type = $this->extractAttribute( $attributes_raw, 'type' );

		if ( str_starts_with( $id, self::RUNTIME_SCRIPT_ID . '-js' ) ) {
			return $matches[0];
		}

		if ( ! in_array( strtolower( $type ), self::JS_TYPES, true ) ) {
			return $matches[0];
		}

		if ( $this->isDefaultExcludedHandle( $id ) || $this->isExcluded( $id, $src ) ) {
			return $matches[0];
		}

		$new_attributes = $this->removeAttribute( $attributes_raw, 'type' );

		if ( '' !== $src ) {
			$new_attributes = $this->removeAttribute( $new_attributes, 'src' );
			$new_attributes = ' data-pivot-delayed-src="' . esc_attr( $src ) . '"' . $new_attributes;
		}

		$new_attributes = ' type="' . self::PLACEHOLDER_TYPE . '"' . $new_attributes;

		return '<script' . $new_attributes . '>' . $inner . '</script>';
	}

	/**
	 * WordPress prints enqueued script tags with id="{handle}-js" — strip
	 * that suffix so it can be compared against raw handles.
	 */
	private function isDefaultExcludedHandle( string $id ): bool {
		if ( '' === $id ) {
			return false;
		}

		$handle = str_ends_with( $id, '-js' ) ? substr( $id, 0, -3 ) : $id;

		return in_array( $handle, self::DEFAULT_EXCLUDED_HANDLES, true );
	}

	private function isExcluded( string $id, string $src ): bool {
		$rules = $this->settings->getLines( 'delay_js_exclusions' );

		if ( array() === $rules ) {
			return false;
		}

		$path     = '' !== $src ? (string) ( wp_parse_url( $src, PHP_URL_PATH ) ?? '' ) : '';
		$basename = '' !== $path ? wp_basename( $path ) : '';

		foreach ( $rules as $rule ) {
			if ( $this->matchesRule( $rule, $id, $src, $path, $basename ) ) {
				return true;
			}
		}

		return false;
	}

	private function matchesRule( string $rule, string $id, string $src, string $path, string $basename ): bool {
		$rule = trim( $rule );

		if ( '' === $rule ) {
			return false;
		}

		$candidates = array_filter( array( $id, $src, $path, $basename ), static fn( string $value ): bool => '' !== $value );

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

	private function extractAttribute( string $attributes, string $name ): string {
		if ( preg_match( '/\b' . preg_quote( $name, '/' ) . '\s*=\s*(["\'])(.*?)\1/i', $attributes, $found ) ) {
			return html_entity_decode( $found[2], ENT_QUOTES );
		}

		return '';
	}

	private function removeAttribute( string $attributes, string $name ): string {
		return (string) preg_replace( '/\s+' . preg_quote( $name, '/' ) . '\s*=\s*(["\']).*?\1/i', '', $attributes );
	}
}
