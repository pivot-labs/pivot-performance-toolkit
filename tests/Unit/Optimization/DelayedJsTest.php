<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Optimization;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Optimization\DelayedJs;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class DelayedJsTest extends TestCase {

	private DelayedJs $delayed_js;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);

		$this->delayed_js = new DelayedJs( new Settings() );
	}

	private function withExclusions( string $exclusions ): DelayedJs {
		Functions\when( 'get_option' )->justReturn( array( 'delay_js_exclusions' => $exclusions ) );

		return new DelayedJs( new Settings() );
	}

	public function test_runtime_script_itself_is_never_delayed(): void {
		$tag = '<script id="pivot-performance-toolkit-delayed-js-runtime">/* runtime */</script>';

		self::assertSame( $tag, $this->delayed_js->delayScriptTags( $tag ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function non_js_type_provider(): array {
		return array(
			'ld+json'       => array( 'application/ld+json' ),
			'text/template' => array( 'text/template' ),
			'text/html'     => array( 'text/html' ),
		);
	}

	#[DataProvider( 'non_js_type_provider' )]
	public function test_non_javascript_types_are_never_delayed( string $type ): void {
		$tag = "<script id=\"data-js\" type=\"{$type}\">{}</script>";

		self::assertSame( $tag, $this->delayed_js->delayScriptTags( $tag ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function js_type_provider(): array {
		return array(
			'no type attribute'      => array( '' ),
			'text/javascript'        => array( 'text/javascript' ),
			'application/javascript' => array( 'application/javascript' ),
			'module'                 => array( 'module' ),
		);
	}

	#[DataProvider( 'js_type_provider' )]
	public function test_executable_js_types_are_delayed( string $type ): void {
		$type_attr = '' !== $type ? " type=\"{$type}\"" : '';
		$tag       = "<script id=\"app-js\"{$type_attr}>doStuff();</script>";

		$result = $this->delayed_js->delayScriptTags( $tag );

		self::assertStringContainsString( 'type="pivotperformancetoolkit/delayed-js"', $result );
		self::assertStringContainsString( 'doStuff();', $result );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function default_excluded_handle_provider(): array {
		return array(
			'jquery-core'    => array( 'jquery-core-js' ),
			'jquery-migrate' => array( 'jquery-migrate-js' ),
			'wp-polyfill'    => array( 'wp-polyfill-js' ),
			// WordPress always prints ids as "{handle}-js" — a bare "jquery"
			// id (no suffix) should still match the raw handle.
			'bare jquery id' => array( 'jquery' ),
		);
	}

	#[DataProvider( 'default_excluded_handle_provider' )]
	public function test_default_excluded_handles_are_never_delayed( string $id ): void {
		$tag = "<script id=\"{$id}\" src=\"/wp-includes/js/jquery/jquery.min.js\"></script>";

		self::assertSame( $tag, $this->delayed_js->delayScriptTags( $tag ) );
	}

	public function test_external_script_gets_src_moved_to_data_attribute(): void {
		$tag = '<script id="app-js" src="/wp-content/themes/x/app.js"></script>';

		$result = $this->delayed_js->delayScriptTags( $tag );

		self::assertStringContainsString( 'type="pivotperformancetoolkit/delayed-js"', $result );
		self::assertStringContainsString( 'data-pivot-delayed-src="/wp-content/themes/x/app.js"', $result );
		self::assertDoesNotMatchRegularExpression(
			'/(?<!data-pivot-delayed-)\bsrc="\/wp-content\/themes\/x\/app\.js"/',
			$result,
			'The original src attribute must be removed, not just shadowed by data-pivot-delayed-src.'
		);
	}

	public function test_inline_script_keeps_its_content_and_gets_no_data_src(): void {
		$tag = '<script id="inline-js">var x = 1;</script>';

		$result = $this->delayed_js->delayScriptTags( $tag );

		self::assertStringContainsString( 'type="pivotperformancetoolkit/delayed-js"', $result );
		self::assertStringContainsString( 'var x = 1;', $result );
		self::assertStringNotContainsString( 'data-pivot-delayed-src', $result );
	}

	public function test_excluded_by_exact_id(): void {
		$delayed_js = $this->withExclusions( 'critical-js' );
		$tag        = '<script id="critical-js">doStuff();</script>';

		self::assertSame( $tag, $delayed_js->delayScriptTags( $tag ) );
	}

	public function test_excluded_by_wildcard_src_pattern(): void {
		$delayed_js = $this->withExclusions( '*/analytics/*' );
		$tag        = '<script id="ga-js" src="/wp-content/plugins/analytics/tracker.js"></script>';

		self::assertSame( $tag, $delayed_js->delayScriptTags( $tag ) );
	}

	public function test_excluded_by_path_prefix_pattern(): void {
		$delayed_js = $this->withExclusions( '/wp-content/themes/x/critical/' );
		$tag        = '<script id="theme-js" src="/wp-content/themes/x/critical/boot.js"></script>';

		self::assertSame( $tag, $delayed_js->delayScriptTags( $tag ) );
	}

	public function test_non_excluded_script_still_delayed_when_exclusions_configured(): void {
		$delayed_js = $this->withExclusions( 'critical-js' );
		$tag        = '<script id="other-js">doStuff();</script>';

		self::assertStringContainsString( 'type="pivotperformancetoolkit/delayed-js"', $delayed_js->delayScriptTags( $tag ) );
	}

	public function test_multiple_scripts_in_document_are_each_handled_independently(): void {
		$html = '<script id="jquery-core-js" src="/jquery.js"></script>'
			. '<script id="app-js" src="/app.js"></script>'
			. '<script id="data-js" type="application/ld+json">{}</script>';

		$result = $this->delayed_js->delayScriptTags( $html );

		self::assertStringContainsString( 'src="/jquery.js"', $result, 'jQuery must be left completely untouched.' );
		self::assertStringContainsString( 'data-pivot-delayed-src="/app.js"', $result );
		self::assertStringContainsString( 'type="application/ld+json">{}</script>', $result, 'JSON-LD must be left completely untouched.' );
	}
}
