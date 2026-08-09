<?php
/**
 * Plugin settings manager.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Settings {

	private const OPTION_KEY = 'pivot_performance_toolkit_settings';

	/**
	 * @return array<string, bool|int|string>
	 */
	public function defaults(): array {
		return array(
			'website_profile'                => 'standard',
			'enable_page_cache'              => true,
			'cache_ttl'                      => 600,
			'max_cache_size_mb'              => 50,
			'cache_excluded_urls'            => '',
			'cache_bypass_cookies'           => "woocommerce_items_in_cart\nwoocommerce_cart_hash\nwp_woocommerce_session_*",
			'cdn_provider'                   => '',
			'cloudflare_api_token'           => '',
			'cloudflare_zone_id'             => '',
			'cloudflare_auto_purge'          => false,
			'minify_html'                    => false,
			'minify_css'                     => false,
			'minify_external_css'            => false,
			'minify_external_css_exclusions' => '',
			'minify_external_js'             => false,
			'minify_external_js_exclusions'  => '',
			'combine_css'                    => false,
			'combine_css_exclusions'         => '',
			'combine_js'                     => false,
			'combine_js_exclusions'          => '',
			'minify_js'                      => false,
			'defer_scripts'                  => true,
			'delay_js_execution'             => false,
			'delay_js_exclusions'            => '',
			'async_css_loading'              => false,
			'async_css_exclusions'           => '',
			'lazy_load_images'               => true,
			'last_settings_exported_at_gmt'  => '',
		);
	}

	public function register(): void {
		register_setting(
			'pivot_performance_toolkit',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * @param mixed $raw
	 *
	 * @return array<string, bool|int|string>
	 */
	public function sanitize( $raw ): array {
		$defaults = $this->defaults();
		$raw      = is_array( $raw ) ? $raw : array();
		$saved    = function_exists( 'get_option' ) ? get_option( self::OPTION_KEY, array() ) : array();
		$saved    = is_array( $saved ) ? $saved : array();
		$base     = function_exists( 'wp_parse_args' ) ? wp_parse_args( $saved, $defaults ) : array_merge( $defaults, $saved );

		$provider = $this->sanitizeKey( (string) ( $raw['cdn_provider'] ?? $base['cdn_provider'] ) );

		if ( 'cloudflare' !== $provider ) {
			$provider = '';
		}

		return array(
			'website_profile'                => $this->sanitizeWebsiteProfile( (string) ( $raw['website_profile'] ?? $base['website_profile'] ) ),
			'enable_page_cache'              => array_key_exists( 'enable_page_cache', $raw ) ? ! empty( $raw['enable_page_cache'] ) : (bool) $base['enable_page_cache'],
			'cache_ttl'                      => max( 60, (int) ( $raw['cache_ttl'] ?? $base['cache_ttl'] ) ),
			'max_cache_size_mb'              => max( 1, (int) ( $raw['max_cache_size_mb'] ?? $base['max_cache_size_mb'] ) ),
			'cache_excluded_urls'            => $this->sanitizeTextarea( (string) ( $raw['cache_excluded_urls'] ?? $base['cache_excluded_urls'] ) ),
			'cache_bypass_cookies'           => $this->sanitizeTextarea( (string) ( $raw['cache_bypass_cookies'] ?? $base['cache_bypass_cookies'] ) ),
			'cdn_provider'                   => $provider,
			'cloudflare_api_token'           => $this->sanitizeText( (string) ( $raw['cloudflare_api_token'] ?? $base['cloudflare_api_token'] ) ),
			'cloudflare_zone_id'             => $this->sanitizeText( (string) ( $raw['cloudflare_zone_id'] ?? $base['cloudflare_zone_id'] ) ),
			'cloudflare_auto_purge'          => array_key_exists( 'cloudflare_auto_purge', $raw ) ? ! empty( $raw['cloudflare_auto_purge'] ) : (bool) $base['cloudflare_auto_purge'],
			'minify_html'                    => array_key_exists( 'minify_html', $raw ) ? ! empty( $raw['minify_html'] ) : (bool) $base['minify_html'],
			'minify_css'                     => array_key_exists( 'minify_css', $raw ) ? ! empty( $raw['minify_css'] ) : (bool) $base['minify_css'],
			'minify_external_css'            => array_key_exists( 'minify_external_css', $raw ) ? ! empty( $raw['minify_external_css'] ) : (bool) $base['minify_external_css'],
			'minify_external_css_exclusions' => $this->sanitizeTextarea( (string) ( $raw['minify_external_css_exclusions'] ?? $base['minify_external_css_exclusions'] ) ),
			'minify_external_js'             => array_key_exists( 'minify_external_js', $raw ) ? ! empty( $raw['minify_external_js'] ) : (bool) $base['minify_external_js'],
			'minify_external_js_exclusions'  => $this->sanitizeTextarea( (string) ( $raw['minify_external_js_exclusions'] ?? $base['minify_external_js_exclusions'] ) ),
			'combine_css'                    => array_key_exists( 'combine_css', $raw ) ? ! empty( $raw['combine_css'] ) : (bool) $base['combine_css'],
			'combine_css_exclusions'         => $this->sanitizeTextarea( (string) ( $raw['combine_css_exclusions'] ?? $base['combine_css_exclusions'] ) ),
			'combine_js'                     => array_key_exists( 'combine_js', $raw ) ? ! empty( $raw['combine_js'] ) : (bool) $base['combine_js'],
			'combine_js_exclusions'          => $this->sanitizeTextarea( (string) ( $raw['combine_js_exclusions'] ?? $base['combine_js_exclusions'] ) ),
			'minify_js'                      => array_key_exists( 'minify_js', $raw ) ? ! empty( $raw['minify_js'] ) : (bool) $base['minify_js'],
			'defer_scripts'                  => array_key_exists( 'defer_scripts', $raw ) ? ! empty( $raw['defer_scripts'] ) : (bool) $base['defer_scripts'],
			'delay_js_execution'             => array_key_exists( 'delay_js_execution', $raw ) ? ! empty( $raw['delay_js_execution'] ) : (bool) $base['delay_js_execution'],
			'delay_js_exclusions'            => $this->sanitizeTextarea( (string) ( $raw['delay_js_exclusions'] ?? $base['delay_js_exclusions'] ) ),
			'async_css_loading'              => array_key_exists( 'async_css_loading', $raw ) ? ! empty( $raw['async_css_loading'] ) : (bool) $base['async_css_loading'],
			'async_css_exclusions'           => $this->sanitizeTextarea( (string) ( $raw['async_css_exclusions'] ?? $base['async_css_exclusions'] ) ),
			'lazy_load_images'               => array_key_exists( 'lazy_load_images', $raw ) ? ! empty( $raw['lazy_load_images'] ) : (bool) $base['lazy_load_images'],
			'last_settings_exported_at_gmt'  => $this->sanitizeText( (string) ( $raw['last_settings_exported_at_gmt'] ?? $base['last_settings_exported_at_gmt'] ) ),
		);
	}

	/**
	 * @return array<string, bool|int|string>
	 */
	public function all(): array {
		$saved = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, $this->defaults() );
	}

	private function sanitizeText( string $value ): string {
		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- fallback for when this class is used without WordPress loaded (see tests/smoke-settings.php), where wp_strip_all_tags() is also undefined.
		return trim( strip_tags( $value ) );
	}

	private function sanitizeTextarea( string $value ): string {
		if ( function_exists( 'sanitize_textarea_field' ) ) {
			return sanitize_textarea_field( $value );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- fallback for when this class is used without WordPress loaded (see tests/smoke-settings.php), where wp_strip_all_tags() is also undefined.
		return trim( str_replace( "\r", '', strip_tags( $value ) ) );
	}

	private function sanitizeKey( string $value ): string {
		if ( function_exists( 'sanitize_key' ) ) {
			return sanitize_key( $value );
		}

		return (string) preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) );
	}

	private function sanitizeWebsiteProfile( string $value ): string {
		$allowed = array(
			'standard',
			'woocommerce',
			'membership-lms',
			'page-builder-heavy',
		);

		$profile = $this->sanitizeKey( $value );

		if ( in_array( $profile, $allowed, true ) ) {
			return $profile;
		}

		return (string) $this->defaults()['website_profile'];
	}

	public function getBool( string $key ): bool {
		return ! empty( $this->all()[ $key ] );
	}

	public function getInt( string $key ): int {
		return (int) ( $this->all()[ $key ] ?? 0 );
	}

	public function getString( string $key ): string {
		return (string) ( $this->all()[ $key ] ?? '' );
	}

	/**
	 * Returns a setting stored as newline-delimited text as a trimmed, non-empty array of lines.
	 *
	 * @return string[]
	 */
	public function getLines( string $key ): array {
		$raw   = $this->getString( $key );
		$lines = array_filter(
			array_map( 'trim', explode( "\n", $raw ) ),
			static fn( string $line ): bool => '' !== $line
		);

		return array_values( $lines );
	}

	public function optionKey(): string {
		return self::OPTION_KEY;
	}
}
