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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class DelayedJs implements ModuleInterface {

	private const PLACEHOLDER_TYPE  = 'pivotperformancetoolkit/delayed-js';
	private const RUNTIME_SCRIPT_ID = 'pivot-performance-toolkit-delayed-js-runtime';

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
		add_action( 'wp_head', array( $this, 'renderRuntimeScript' ), 1 );
		add_action( 'template_redirect', array( $this, 'startOutputDelay' ), 12 );
	}

	public function renderRuntimeScript(): void {
		if ( ! $this->settings->getBool( 'delay_js_execution' ) || is_admin() ) {
			return;
		}

		$timeout = self::FALLBACK_TIMEOUT_MS;

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline script, no user input; $timeout is an int constant, not user data.
		echo '<script id="' . esc_attr( self::RUNTIME_SCRIPT_ID ) . '">' . $this->runtimeScript( $timeout ) . '</script>' . "\n";
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function startOutputDelay(): void {
		if ( ! $this->settings->getBool( 'delay_js_execution' ) || is_admin() ) {
			return;
		}

		if ( is_user_logged_in() || is_feed() || is_preview() || is_404() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- used only in a strict === comparison against a hardcoded literal, never stored or output.
		if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || strtoupper( (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) !== 'GET' ) {
			return;
		}

		ob_start( array( $this, 'delayScriptTags' ) );
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

		if ( self::RUNTIME_SCRIPT_ID === $id ) {
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

	private function runtimeScript( int $timeout ): string {
		return sprintf(
			'(function () {
    var triggered = false;
    var events = [\'mousemove\', \'scroll\', \'keydown\', \'touchstart\', \'click\'];

    function restore() {
        if (triggered) return;
        triggered = true;

        events.forEach(function (evt) {
            window.removeEventListener(evt, restore, { passive: true });
        });
        clearTimeout(timer);

        var delayed = document.querySelectorAll(\'script[type="%1$s"]\');
        delayed.forEach(function (oldScript) {
            var newScript = document.createElement(\'script\');
            // Dynamically-inserted external scripts execute as soon as they
            // finish fetching (not in insertion order) unless async is
            // explicitly disabled — required to preserve relative order
            // between e.g. wp-i18n and its inline "after" companion script.
            newScript.async = false;
            for (var i = 0; i < oldScript.attributes.length; i++) {
                var attr = oldScript.attributes[i];
                if (attr.name === \'type\') continue;
                if (attr.name === \'data-pivot-delayed-src\') {
                    newScript.src = attr.value;
                    continue;
                }
                newScript.setAttribute(attr.name, attr.value);
            }
            if (!newScript.src) {
                newScript.text = oldScript.text;
            }
            oldScript.replaceWith(newScript);
        });
    }

    var timer = setTimeout(restore, %2$d);
    events.forEach(function (evt) {
        window.addEventListener(evt, restore, { passive: true, once: true });
    });
})();',
			self::PLACEHOLDER_TYPE,
			$timeout
		);
	}
}
