<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Optimization;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Optimization\AsyncCss;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class AsyncCssTest extends TestCase {

	private AsyncCss $async_css;

	protected function setUp(): void {
		parent::setUp();
		$this->requireHtmlApi();

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
		Functions\when( 'wp_unslash' )->returnArg();

		$this->async_css = new AsyncCss( new Settings() );
	}

	public function test_non_stylesheet_link_is_left_unchanged(): void {
		$tag = '<link rel="preconnect" href="https://fonts.gstatic.com">';

		self::assertSame( $tag, $this->async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_print_stylesheet_is_left_unchanged(): void {
		$tag = "<link rel='stylesheet' href='/wp-content/themes/x/print.css' media='print'>";

		self::assertSame( $tag, $this->async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_default_media_stylesheet_becomes_preload_with_noscript_fallback(): void {
		$tag = "<link rel='stylesheet' id='theme-style-css' href='/wp-content/themes/x/style.css' media='all'>";

		$result = $this->async_css->asyncStylesheetTags( $tag );

		[ $preload, $noscript ] = explode( '<noscript>', $result, 2 );

		self::assertSame( 'preload', $this->attribute( $preload, 'rel' ) );
		self::assertSame( 'style', $this->attribute( $preload, 'as' ) );
		self::assertSame( '/wp-content/themes/x/style.css', $this->attribute( $preload, 'href' ) );
		self::assertSame( 'theme-style-css', $this->attribute( $preload, 'id' ) );
		self::assertSame( "this.onload=null;this.rel='stylesheet'", $this->attribute( $preload, 'onload' ) );

		// The no-JavaScript fallback is the original tag, untouched.
		self::assertSame( $tag . '</noscript>', $noscript );
	}

	/**
	 * A stylesheet scoped to a non-default media (e.g. a responsive breakpoint) must keep
	 * that scoping on *both* the preload tag and the noscript fallback: once the onload
	 * handler flips rel to "stylesheet", an absent media attribute defaults to "all", so
	 * dropping it would make the stylesheet apply globally instead of only at its breakpoint.
	 */
	public function test_non_default_media_is_preserved_on_both_output_tags(): void {
		$tag = "<link rel='stylesheet' id='responsive-css' href='/wp-content/themes/x/responsive.css' media='screen and (max-width: 782px)'>";

		[ $preload, $noscript ] = explode( '<noscript>', $this->async_css->asyncStylesheetTags( $tag ), 2 );

		self::assertSame( 'screen and (max-width: 782px)', $this->attribute( $preload, 'media' ) );
		self::assertSame( 'screen and (max-width: 782px)', $this->attribute( $noscript, 'media' ) );
	}

	public function test_other_attributes_survive_the_rewrite(): void {
		$tag = "<link rel='stylesheet' href='/a.css' crossorigin='anonymous'>";

		[ $preload, $noscript ] = explode( '<noscript>', $this->async_css->asyncStylesheetTags( $tag ), 2 );

		self::assertSame( 'anonymous', $this->attribute( $preload, 'crossorigin' ) );
		self::assertSame( 'anonymous', $this->attribute( $noscript, 'crossorigin' ) );
	}

	/**
	 * First attribute value of the first <link> in $html, read with core's HTML API.
	 */
	private function attribute( string $html, string $name ): ?string {
		$processor = new \WP_HTML_Tag_Processor( $html );
		$processor->next_tag( 'link' );

		$value = $processor->get_attribute( $name );

		return is_string( $value ) ? $value : null;
	}

	public function test_excluded_by_exact_id_is_left_unchanged(): void {
		Functions\when( 'get_option' )->justReturn( array( 'async_css_exclusions' => 'critical-css' ) );
		$async_css = new AsyncCss( new Settings() );

		$tag = "<link rel='stylesheet' id='critical-css' href='/wp-content/themes/x/critical.css'>";

		self::assertSame( $tag, $async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_excluded_by_wildcard_href_pattern(): void {
		Functions\when( 'get_option' )->justReturn( array( 'async_css_exclusions' => "*/woocommerce/*" ) );
		$async_css = new AsyncCss( new Settings() );

		$tag = "<link rel='stylesheet' id='wc-css' href='/wp-content/plugins/woocommerce/assets/css/style.css'>";

		self::assertSame( $tag, $async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_non_excluded_stylesheet_is_still_transformed_when_exclusions_configured(): void {
		Functions\when( 'get_option' )->justReturn( array( 'async_css_exclusions' => 'critical-css' ) );
		$async_css = new AsyncCss( new Settings() );

		$tag = "<link rel='stylesheet' id='other-css' href='/wp-content/themes/x/other.css'>";

		self::assertStringContainsString( 'preload', $async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_multiple_tags_in_document_are_each_handled_independently(): void {
		$html = "<link rel='stylesheet' href='/a.css'>\n"
			. "<link rel='preconnect' href='https://fonts.gstatic.com'>\n"
			. "<link rel='stylesheet' href='/b.css' media='print'>";

		$result = $this->async_css->asyncStylesheetTags( $html );

		self::assertStringContainsString( 'preload', $result );
		self::assertStringContainsString( "rel='preconnect'", $result );
		self::assertStringContainsString( "rel='stylesheet' href='/b.css' media='print'", $result );
	}

	// ── maybeAsyncStylesheetTags: the wp_template_enhancement_output_buffer
	// guard logic that replaced the old template_redirect + ob_start() gate.

	private function asyncCssWithSetting( bool $enabled ): AsyncCss {
		Functions\when( 'get_option' )->justReturn( array( 'async_css_loading' => $enabled ) );

		return new AsyncCss( new Settings() );
	}

	/**
	 * Stubs every guard condition to "pass" (feature on, front-end,
	 * logged-out, GET) so a single guard can be flipped per test to prove
	 * it alone is sufficient to suppress the transform.
	 */
	private function stubPassingGuardConditions(): void {
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	protected function tearDown(): void {
		unset( $_SERVER['REQUEST_METHOD'] );

		parent::tearDown();
	}

	public function test_maybe_async_returns_html_unchanged_when_setting_is_off(): void {
		$this->stubPassingGuardConditions();
		$async_css = $this->asyncCssWithSetting( false );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_on_admin(): void {
		$this->stubPassingGuardConditions();
		Functions\when( 'is_admin' )->justReturn( true );
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_when_logged_in(): void {
		$this->stubPassingGuardConditions();
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_on_feed(): void {
		$this->stubPassingGuardConditions();
		Functions\when( 'is_feed' )->justReturn( true );
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_on_preview(): void {
		$this->stubPassingGuardConditions();
		Functions\when( 'is_preview' )->justReturn( true );
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_on_404(): void {
		$this->stubPassingGuardConditions();
		Functions\when( 'is_404' )->justReturn( true );
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_returns_html_unchanged_on_non_get_request(): void {
		$this->stubPassingGuardConditions();
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$async_css                 = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertSame( $html, $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	public function test_maybe_async_transforms_html_when_all_guards_pass(): void {
		$this->stubPassingGuardConditions();
		$async_css = $this->asyncCssWithSetting( true );

		$html = "<link rel='stylesheet' href='/a.css'>";

		self::assertStringContainsString( 'rel="preload"', $async_css->maybeAsyncStylesheetTags( $html ) );
	}

	/**
	 * Priority 11 is semantically load-bearing, not arbitrary: it must run
	 * after Combine (10) and before DelayedJs (12)/Assets (13) to preserve
	 * the transform order the old nested ob_start() priorities produced —
	 * see the comment on this registration in AsyncCss::register().
	 */
	public function test_registers_on_wp_template_enhancement_output_buffer_at_priority_11(): void {
		$calls = array();
		Functions\when( 'add_filter' )->alias(
			static function ( ...$args ) use ( &$calls ): void {
				$calls[] = $args;
			}
		);

		$this->async_css->register();

		self::assertSame(
			array( 'wp_template_enhancement_output_buffer', array( $this->async_css, 'maybeAsyncStylesheetTags' ), 11 ),
			$calls[0]
		);
	}
}
