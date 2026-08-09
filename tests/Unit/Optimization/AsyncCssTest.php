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

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);

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

		self::assertStringContainsString( 'rel="preload"', $result );
		self::assertStringContainsString( 'as="style"', $result );
		self::assertStringContainsString( 'href="/wp-content/themes/x/style.css"', $result );
		self::assertStringContainsString( 'onload="this.onload=null;this.rel=&#039;stylesheet&#039;"', $result );
		self::assertStringContainsString( '<noscript><link rel="stylesheet"', $result );

		// Default/absent media isn't meaningfully lost by omitting it (an
		// absent attribute already means "all"), so it shouldn't appear on
		// either output tag.
		self::assertStringNotContainsString( 'media=', $result );
	}

	/**
	 * Regression test for the bug fixed this session: a stylesheet scoped to
	 * a non-default media (e.g. a responsive breakpoint) must keep that
	 * scoping on *both* the preload tag and the noscript fallback — once the
	 * onload handler flips rel to "stylesheet", an absent media attribute
	 * defaults to "all", so dropping it would make the stylesheet apply
	 * globally instead of only at its intended breakpoint.
	 */
	public function test_non_default_media_is_preserved_on_both_output_tags(): void {
		$tag = "<link rel='stylesheet' id='responsive-css' href='/wp-content/themes/x/responsive.css' media='screen and (max-width: 782px)'>";

		$result = $this->async_css->asyncStylesheetTags( $tag );

		self::assertSame(
			2,
			substr_count( $result, 'media="screen and (max-width: 782px)"' ),
			'The non-default media value must appear on both the preload tag and the noscript fallback.'
		);
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

		self::assertStringContainsString( 'rel="preload"', $async_css->asyncStylesheetTags( $tag ) );
	}

	public function test_multiple_tags_in_document_are_each_handled_independently(): void {
		$html = "<link rel='stylesheet' href='/a.css'>\n"
			. "<link rel='preconnect' href='https://fonts.gstatic.com'>\n"
			. "<link rel='stylesheet' href='/b.css' media='print'>";

		$result = $this->async_css->asyncStylesheetTags( $html );

		self::assertStringContainsString( 'rel="preload"', $result );
		self::assertStringContainsString( "rel='preconnect'", $result );
		self::assertStringContainsString( "rel='stylesheet' href='/b.css' media='print'", $result );
	}
}
