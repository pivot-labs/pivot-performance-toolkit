<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Optimization;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Optimization\Combine;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use ReflectionMethod;

final class CombineTest extends TestCase {

	private Combine $combine;

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'wp_basename' )->alias( 'basename' );
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);

		Functions\when( 'get_option' )->justReturn( array() );
		Functions\when( 'wp_mkdir_p' )->alias(
			static fn( string $dir ): bool => is_dir( $dir ) || mkdir( $dir, 0777, true )
		);
		Functions\when( 'content_url' )->alias(
			static fn( string $path = '' ): string => 'https://example.com/wp-content/' . ltrim( $path, '/' )
		);

		$settings      = new Settings();
		$this->combine = new Combine( $settings );
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

	/**
	 * @return mixed
	 */
	private function call( string $method, array $args ) {
		$reflection = new ReflectionMethod( Combine::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $this->combine, $args );
	}

	// ── eligibleCssTag ──────────────────────────────────────────────────

	public function test_css_tag_is_ineligible_when_not_a_stylesheet(): void {
		$tag = '<link rel="preload" as="style" href="/wp-content/themes/x/style.css">';

		self::assertNull( $this->call( 'eligibleCssTag', array( $tag ) ) );
	}

	/**
	 * A print (or any other non-default) media stylesheet can't be merged
	 * into an "all" bundle without either applying somewhere it shouldn't or
	 * being suppressed somewhere it should apply.
	 */
	public function test_css_tag_is_ineligible_for_non_default_media(): void {
		$tag = '<link rel="stylesheet" media="print" href="/wp-content/themes/x/print.css">';

		self::assertNull( $this->call( 'eligibleCssTag', array( $tag ) ) );
	}

	public function test_css_tag_is_ineligible_when_cross_origin(): void {
		$tag = '<link rel="stylesheet" href="https://cdn.example.net/style.css">';

		self::assertNull( $this->call( 'eligibleCssTag', array( $tag ) ) );
	}

	// ── eligibleJsTag ───────────────────────────────────────────────────

	public function test_js_tag_is_ineligible_when_inline(): void {
		$tag = '<script id="foo-js">console.log("hi");</script>';

		self::assertNull( $this->call( 'eligibleJsTag', array( $tag ) ) );
	}

	public function test_js_tag_is_ineligible_for_module_type(): void {
		$tag = '<script type="module" id="foo-js" src="/wp-content/themes/x/app.js"></script>';

		self::assertNull( $this->call( 'eligibleJsTag', array( $tag ) ) );
	}

	/**
	 * A combined bundle can only carry one async/defer state for every file
	 * in it — a script that explicitly opts into different loading behavior
	 * must not be silently folded into a bundle that changes it.
	 */
	public function test_js_tag_is_ineligible_when_deferred_or_async(): void {
		self::assertNull(
			$this->call( 'eligibleJsTag', array( '<script defer id="a-js" src="/wp-content/themes/x/a.js"></script>' ) )
		);
		self::assertNull(
			$this->call( 'eligibleJsTag', array( '<script async id="a-js" src="/wp-content/themes/x/a.js"></script>' ) )
		);
	}

	public function test_js_tag_is_ineligible_for_default_excluded_handle(): void {
		$tag = '<script id="jquery-core-js" src="/wp-includes/js/jquery/jquery.min.js"></script>';

		self::assertNull( $this->call( 'eligibleJsTag', array( $tag ) ) );
	}

	// ── combineCssRun / combineJsRun: grouping & order preservation ────

	/**
	 * Two adjacent eligible tags in the same run collapse into a single
	 * combined <link>; a run of exactly one eligible tag is left as-is
	 * (nothing gained by "combining" a single file).
	 */
	public function test_single_eligible_css_tag_is_left_unchanged(): void {
		$tag = '<link rel="stylesheet" href="https://cdn.example.net/style.css">';

		self::assertSame( $tag, $this->call( 'combineCssRun', array( array( $tag ) ) ) );
	}

	/**
	 * An ineligible tag inside a run must break the group rather than be
	 * silently dropped or reordered — everything before it and after it may
	 * still combine separately, but the ineligible tag's own position in the
	 * document must be preserved exactly.
	 */
	public function test_ineligible_tag_between_eligible_ones_is_preserved_in_place(): void {
		$external = '<link rel="stylesheet" href="https://cdn.example.net/style.css">';

		$result = $this->call( 'combineCssRun', array( array( $external, $external, $external ) ) );

		// All three are individually ineligible (cross-origin) here, so the
		// run must come back byte-for-byte unchanged and in order.
		self::assertSame( $external . $external . $external, $result );
	}

	/**
	 * Full success path: two real, adjacent, eligible local stylesheets are
	 * combined into one written bundle file whose content is both files'
	 * CSS concatenated, with the first file's relative url() rewritten so it
	 * still resolves correctly from the bundle's own (different) directory.
	 */
	public function test_two_adjacent_local_stylesheets_combine_into_one_bundle(): void {
		$tag_a = '<link rel="stylesheet" href="https://example.com/fixtures/css/a.css">';
		$tag_b = '<link rel="stylesheet" href="https://example.com/fixtures/css/b.css">';

		$result = $this->call( 'combineCssRun', array( array( $tag_a, $tag_b ) ) );

		self::assertMatchesRegularExpression(
			'#^<link rel="stylesheet" href="https://example\.com/wp-content/cache/pivot-performance-toolkit/combined-assets/[a-f0-9]{32}\.css">$#',
			$result
		);

		preg_match( '/href="([^"]+)"/', $result, $href_match );
		$bundle_path = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit/combined-assets/' . basename( $href_match[1] );

		self::assertFileExists( $bundle_path );

		$bundle_content = (string) file_get_contents( $bundle_path );

		self::assertStringContainsString( '.a { color: red;', $bundle_content );
		self::assertStringContainsString( '.b { color: blue; }', $bundle_content );
		self::assertStringContainsString(
			'url(/fixtures/css/images/bg.png)',
			$bundle_content,
			'The relative url() in a.css must be rewritten relative to its own source directory.'
		);
	}

	// ── rewriteRelativeCssUrls / resolveRelativeCssUrl ─────────────────

	public function test_relative_css_urls_are_rewritten_root_relative_to_source_file(): void {
		$css = ".a { background: url(images/bg.png); }";

		$result = $this->call(
			'rewriteRelativeCssUrls',
			array( $css, 'https://example.com/wp-content/themes/astra/assets/css/minified/main.min.css' )
		);

		self::assertSame(
			'.a { background: url(/wp-content/themes/astra/assets/css/minified/images/bg.png); }',
			$result
		);
	}

	public function test_relative_css_url_with_parent_directory_traversal(): void {
		$css = ".b { background: url(../fonts/font.woff2); }";

		$result = $this->call(
			'rewriteRelativeCssUrls',
			array( $css, 'https://example.com/wp-content/themes/astra/assets/css/minified/main.min.css' )
		);

		self::assertSame(
			'.b { background: url(/wp-content/themes/astra/assets/css/fonts/font.woff2); }',
			$result
		);
	}

	public function test_relative_css_url_preserves_query_and_fragment(): void {
		$css = ".c { background: url(./icon.svg?x=1#frag); }";

		$result = $this->call(
			'rewriteRelativeCssUrls',
			array( $css, 'https://example.com/wp-content/themes/x/style.css' )
		);

		self::assertSame(
			'.c { background: url(/wp-content/themes/x/icon.svg?x=1#frag); }',
			$result
		);
	}

	/**
	 * data:, absolute-path, and cross-origin references don't depend on the
	 * source file's own location, so rewriting them would be both
	 * unnecessary and (for data: URIs, which can contain characters that
	 * look like path segments) actively wrong.
	 */
	public function test_already_absolute_css_urls_are_left_untouched(): void {
		$cases = array(
			'url("data:image/png;base64,AAA")',
			'url(/already/absolute.png)',
			'url(https://cdn.example.com/x.png)',
			'url(//cdn.example.com/x.png)',
		);

		foreach ( $cases as $case ) {
			$css    = ".d { background: {$case}; }";
			$result = $this->call( 'rewriteRelativeCssUrls', array( $css, 'https://example.com/wp-content/themes/x/style.css' ) );

			self::assertStringContainsString( $case, $result, "Expected {$case} to be left untouched." );
		}
	}
}
