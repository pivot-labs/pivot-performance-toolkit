<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Optimization;

use Brain\Monkey\Functions;
use Mockery;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Optimization\Assets;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use ReflectionMethod;

final class AssetsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
		Functions\when( 'is_admin' )->justReturn( false );
	}

	protected function tearDown(): void {
		$this->removeDir( WP_CONTENT_DIR );

		parent::tearDown();
	}

	private function removeDir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}

		foreach ( glob( $dir . '/*' ) ?: array() as $item ) {
			is_dir( $item ) ? $this->removeDir( $item ) : unlink( $item );
		}

		rmdir( $dir );
	}

	private function assetsWithSettings( array $option_overrides ): Assets {
		Functions\when( 'get_option' )->justReturn( $option_overrides );

		return new Assets( new Settings() );
	}

	/**
	 * @return mixed
	 */
	private function callStatic( string $method, array $args ) {
		$reflection = new ReflectionMethod( Assets::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( null, $args );
	}

	// ── addDeferAttribute ───────────────────────────────────────────────

	public function test_defer_not_added_when_setting_disabled(): void {
		$assets = $this->assetsWithSettings( array( 'defer_scripts' => false ) );
		$tag    = '<script src="/app.js"></script>';

		self::assertSame( $tag, $assets->addDeferAttribute( $tag, 'app', '/app.js' ) );
	}

	public function test_defer_not_added_for_default_excluded_handle(): void {
		$assets = $this->assetsWithSettings( array( 'defer_scripts' => true ) );
		$tag    = '<script src="/jquery.js"></script>';

		self::assertSame( $tag, $assets->addDeferAttribute( $tag, 'jquery-core', '/jquery.js' ) );
	}

	public function test_defer_not_duplicated_when_already_present(): void {
		$assets = $this->assetsWithSettings( array( 'defer_scripts' => true ) );
		$tag    = '<script defer src="/app.js"></script>';

		self::assertSame( $tag, $assets->addDeferAttribute( $tag, 'app', '/app.js' ) );
	}

	/**
	 * Regression test for the bug fixed this session: the `defer` attribute
	 * has no effect on inline scripts, so a script with an inline companion
	 * (wp_add_inline_script 'before'/'after') always runs synchronously at
	 * its original position — deferring only the external half would run it
	 * before/after that inline companion instead of immediately alongside
	 * it, breaking anything the inline code expects from the external file
	 * (e.g. wp-i18n's setLocaleData() call depending on globals the deferred
	 * script hasn't defined yet).
	 */
	public function test_defer_not_added_when_handle_has_inline_companion_script(): void {
		$assets = $this->assetsWithSettings( array( 'defer_scripts' => true ) );

		$wp_scripts = Mockery::mock();
		$wp_scripts->shouldReceive( 'get_data' )->with( 'wp-i18n', 'after' )->andReturn( 'wp.i18n.setLocaleData(...)' );
		$wp_scripts->shouldReceive( 'get_data' )->with( 'wp-i18n', 'before' )->andReturn( false );
		Functions\when( 'wp_scripts' )->justReturn( $wp_scripts );

		$tag = '<script src="/wp-i18n.js"></script>';

		self::assertSame( $tag, $assets->addDeferAttribute( $tag, 'wp-i18n', '/wp-i18n.js' ) );
	}

	public function test_defer_added_for_eligible_script_with_no_inline_companion(): void {
		$assets = $this->assetsWithSettings( array( 'defer_scripts' => true ) );

		$wp_scripts = Mockery::mock();
		$wp_scripts->shouldReceive( 'get_data' )->with( 'app', 'after' )->andReturn( false );
		$wp_scripts->shouldReceive( 'get_data' )->with( 'app', 'before' )->andReturn( false );
		Functions\when( 'wp_scripts' )->justReturn( $wp_scripts );

		$result = $assets->addDeferAttribute( '<script src="/app.js"></script>', 'app', '/app.js' );

		self::assertSame( '<script defer src="/app.js"></script>', $result );
	}

	// ── minifyCss ───────────────────────────────────────────────────────

	public function test_minify_css_strips_regular_comments(): void {
		$result = $this->callStatic( 'minifyCss', array( '.a { color: red; /* a comment */ }' ) );

		self::assertSame( '.a{color:red}', $result );
	}

	public function test_minify_css_preserves_bang_comments(): void {
		$result = $this->callStatic( 'minifyCss', array( '/*! license banner */.a{color:red}' ) );

		self::assertStringContainsString( '/*! license banner */', $result );
	}

	public function test_minify_css_collapses_whitespace_and_trailing_semicolons(): void {
		$result = $this->callStatic( 'minifyCss', array( ".a {\n  color: red;\n  margin: 0;\n}\n" ) );

		self::assertSame( '.a{color:red;margin:0}', $result );
	}

	// ── minifyJs ────────────────────────────────────────────────────────

	public function test_minify_js_strips_standalone_line_comments(): void {
		$result = $this->callStatic( 'minifyJs', array( "// a standalone comment\nvar x = 1;" ) );

		self::assertSame( 'var x = 1;', $result );
	}

	/**
	 * The line-comment regex is anchored to the start of the line
	 * (`^\s*\/\/`) specifically so it never touches a `//` that appears
	 * after real code on the same line — that could be a URL fragment, a
	 * regex literal, or a comment that's semantically attached to that
	 * line, none of which are safe to blindly truncate.
	 */
	public function test_minify_js_does_not_strip_trailing_same_line_comment(): void {
		$js = 'var url = "https://example.com"; // not a standalone comment';

		self::assertSame( $js, $this->callStatic( 'minifyJs', array( $js ) ) );
	}

	public function test_minify_js_strips_standalone_block_comments(): void {
		$js = "/*\n * block comment\n */\nvar x = 1;";

		self::assertSame( 'var x = 1;', $this->callStatic( 'minifyJs', array( $js ) ) );
	}

	public function test_minify_js_collapses_multiple_blank_lines(): void {
		$js = "var a = 1;\n\n\n\nvar b = 2;";

		self::assertSame( "var a = 1;\nvar b = 2;", $this->callStatic( 'minifyJs', array( $js ) ) );
	}

	// ── minifyHtml ──────────────────────────────────────────────────────

	public function test_minify_html_strips_regular_comments(): void {
		$html = '<div><!-- a comment --><p>text</p></div>';

		self::assertSame( '<div><p>text</p></div>', $this->callStatic( 'minifyHtml', array( $html ) ) );
	}

	/**
	 * Conditional comments (<!--[if ...]-->) are real, meaningful markup for
	 * legacy IE targeting, not decorative — stripping them would silently
	 * change page behavior for anyone still relying on them.
	 */
	public function test_minify_html_preserves_conditional_comments(): void {
		$html = '<!--[if lt IE 9]><script src="/html5shiv.js"></script><![endif]-->';

		self::assertSame( $html, $this->callStatic( 'minifyHtml', array( $html ) ) );
	}

	public function test_minify_html_collapses_whitespace_between_tags(): void {
		$html = "<div>\n    <p>text</p>\n</div>";

		self::assertSame( '<div><p>text</p></div>', $this->callStatic( 'minifyHtml', array( $html ) ) );
	}

	// ── minifyScriptTag / minifyStylesheetTag ──────────────────────────

	public function test_minify_script_tag_unchanged_when_setting_disabled(): void {
		$assets = $this->assetsWithSettings( array( 'minify_external_js' => false ) );
		$tag    = '<script src="/app.js"></script>';

		self::assertSame( $tag, $assets->minifyScriptTag( $tag, 'app', '/app.js' ) );
	}

	public function test_minify_script_tag_unchanged_when_already_minified(): void {
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		$assets = $this->assetsWithSettings( array( 'minify_external_js' => true ) );

		$tag = '<script src="https://example.com/app.min.js"></script>';

		self::assertSame( $tag, $assets->minifyScriptTag( $tag, 'app', 'https://example.com/app.min.js' ) );
	}

	public function test_minify_stylesheet_tag_unchanged_for_excluded_handle(): void {
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		$assets = $this->assetsWithSettings(
			array(
				'minify_external_css'            => true,
				'minify_external_css_exclusions' => 'theme-style',
			)
		);

		$tag = "<link rel='stylesheet' href='https://example.com/style.css'>";

		self::assertSame( $tag, $assets->minifyStylesheetTag( $tag, 'theme-style', 'https://example.com/style.css', 'all' ) );
	}

	/**
	 * Full success path: an eligible external script resolves to a real
	 * local file, gets minified, written to the cache dir, and the tag's
	 * src is rewritten to point at the minified copy.
	 */
	public function test_minify_script_tag_writes_minified_file_and_rewrites_src(): void {
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_mkdir_p' )->alias(
			static fn( string $dir ): bool => is_dir( $dir ) || mkdir( $dir, 0777, true )
		);
		Functions\when( 'content_url' )->alias(
			static fn( string $path = '' ): string => 'https://example.com/wp-content/' . ltrim( $path, '/' )
		);

		$assets = $this->assetsWithSettings( array( 'minify_external_js' => true ) );

		$src = 'https://example.com/fixtures/js/a.js';
		$tag = "<script src='{$src}'></script>";

		$result = $assets->minifyScriptTag( $tag, 'a', $src );

		self::assertMatchesRegularExpression(
			'#src=\'https://example\.com/wp-content/cache/pivot-performance-toolkit/minified-assets/[a-f0-9]{32}\.min\.js\'#',
			$result
		);

		preg_match( "/src='([^']+)'/", $result, $src_match );
		$minified_path = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit/minified-assets/' . basename( $src_match[1] );

		self::assertFileExists( $minified_path );

		$minified_content = (string) file_get_contents( $minified_path );

		self::assertStringNotContainsString( 'a standalone comment', $minified_content );
		self::assertStringContainsString( 'var x = 1;', $minified_content );
		self::assertStringContainsString( 'var y = 2;', $minified_content );
	}
}
