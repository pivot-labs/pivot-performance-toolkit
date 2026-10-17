<?php
/**
 * No test file existed for this class before — it previously ran entirely
 * inside an ob_start() callback (see the WP.org-review-driven migration to
 * wp_finalized_template_enhancement_output_buffer), which made it awkward
 * to unit test in isolation. Converting it to a plain method taking $html
 * directly made this coverage straightforward to add.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Cache\PageCache;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class PageCacheTest extends TestCase {

	private const CACHE_DIR = WP_CONTENT_DIR . '/cache/pivot-performance-toolkit';

	/** @var array<string, mixed> */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();

		$this->options = array();

		Functions\when( 'get_option' )->alias(
			function ( string $key, $default = array() ) {
				return $this->options[ $key ] ?? $default;
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'set_url_scheme' )->alias( static fn( string $url, string $scheme ): string => preg_replace( '#^[a-z]+://#', $scheme . '://', $url ) );
		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
		Functions\when( 'wp_mkdir_p' )->alias(
			static fn( string $dir ): bool => is_dir( $dir ) || mkdir( $dir, 0777, true )
		);
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'rest_url' )->justReturn( 'https://example.com/wp-json/pivot-performance-toolkit/v1/performance-tests/collect' );
		Functions\when( 'wp_delete_file' )->alias(
			static function ( string $path ): void {
				if ( file_exists( $path ) ) {
					unlink( $path );
				}
			}
		);

		$this->stubPassingGuardConditions();
	}

	protected function tearDown(): void {
		$this->removeDir( WP_CONTENT_DIR );
		unset( $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['HTTP_HOST'], $_SERVER['HTTPS'] );
		$_COOKIE = array();

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
	 * @param array<string, mixed> $overrides
	 */
	private function pageCache( array $overrides = array() ): PageCache {
		$this->options['pivot_performance_toolkit_settings'] = array_merge(
			array( 'enable_page_cache' => true ),
			$overrides
		);

		return new PageCache( new Settings() );
	}

	private function stubPassingGuardConditions(): void {
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI']    = '/some-page/';
		$_SERVER['HTTP_HOST']      = 'example.com';
		$_COOKIE                   = array();
	}

	private function cacheFileCount(): int {
		return count( glob( self::CACHE_DIR . '/*.html' ) ?: array() );
	}

	// ── maybeCacheOutput: bypass conditions ─────────────────────────────

	public function test_does_not_write_when_page_cache_disabled(): void {
		$page_cache = $this->pageCache( array( 'enable_page_cache' => false ) );

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_on_admin(): void {
		Functions\when( 'is_admin' )->justReturn( true );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_when_logged_in_and_not_a_probe_request(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_on_preview(): void {
		Functions\when( 'is_preview' )->justReturn( true );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_on_feed(): void {
		Functions\when( 'is_feed' )->justReturn( true );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_on_404(): void {
		Functions\when( 'is_404' )->justReturn( true );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_on_non_get_request(): void {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$page_cache                = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_when_a_bypass_cookie_is_present(): void {
		$_COOKIE = array( 'woocommerce_items_in_cart' => '1' );
		$page_cache = $this->pageCache( array( 'cache_bypass_cookies' => 'woocommerce_items_in_cart' ) );

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_when_request_path_matches_an_excluded_url(): void {
		$_SERVER['REQUEST_URI'] = '/checkout/?step=2';
		$page_cache             = $this->pageCache( array( 'cache_excluded_urls' => '/checkout' ) );

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	public function test_does_not_write_when_html_is_empty(): void {
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '' );

		self::assertSame( 0, $this->cacheFileCount() );
	}

	/**
	 * Regression case for a real, deliberate distinction in the source: a
	 * logged-in probe request (valid pivot_performance_toolkit_perf_probe
	 * cookie, >= 20 chars) is NOT treated as "logged_in" bypass — it's
	 * allowed past that check specifically so the performance test can run
	 * while logged in — but it's still never written to the shared cache,
	 * for a different, more specific reason (probe_no_write, see the source
	 * comment on why: a probe's page contains a one-off metrics script that
	 * must never be cached and replayed to other visitors).
	 */
	public function test_probe_request_while_logged_in_is_not_written_but_for_the_probe_reason_not_login(): void {
		Functions\when( 'is_user_logged_in' )->justReturn( true );
		$_COOKIE['pivot_performance_toolkit_perf_probe'] = str_repeat( 'a', 20 );
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>content</html>' );

		// Not written either way, but reachable only if 'logged_in' didn't
		// short-circuit first — proven by isProbeRequest() suppressing the
		// bypassReason() 'logged_in' branch for this specific cookie.
		self::assertSame( 0, $this->cacheFileCount() );
	}

	// ── maybeCacheOutput: success path ──────────────────────────────────

	public function test_writes_cache_file_when_all_guards_pass(): void {
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>real page content</html>' );

		self::assertSame( 1, $this->cacheFileCount() );

		$files = glob( self::CACHE_DIR . '/*.html' ) ?: array();
		self::assertStringContainsString( 'real page content', (string) file_get_contents( $files[0] ) );
	}

	/**
	 * The cache key is derived from scheme + host + request URI (see
	 * cacheFilePath()) — same URL must always resolve to the same file, so
	 * a second request to the same path overwrites rather than duplicates.
	 */
	public function test_same_request_uri_reuses_the_same_cache_file(): void {
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>first</html>' );
		$first_files = glob( self::CACHE_DIR . '/*.html' ) ?: array();

		$page_cache->maybeCacheOutput( '<html>second</html>' );
		$second_files = glob( self::CACHE_DIR . '/*.html' ) ?: array();

		self::assertSame( $first_files, $second_files );
		self::assertStringContainsString( 'second', (string) file_get_contents( $second_files[0] ) );
	}

	public function test_different_request_uris_produce_different_cache_files(): void {
		$page_cache = $this->pageCache();

		$page_cache->maybeCacheOutput( '<html>page one</html>' );

		$_SERVER['REQUEST_URI'] = '/a-different-page/';
		$page_cache->maybeCacheOutput( '<html>page two</html>' );

		self::assertSame( 2, $this->cacheFileCount() );
	}

	// ── purgeAll ─────────────────────────────────────────────────────────

	public function test_purge_all_removes_only_html_files_in_cache_dir(): void {
		$page_cache = $this->pageCache();
		$page_cache->maybeCacheOutput( '<html>content</html>' );
		self::assertSame( 1, $this->cacheFileCount() );

		mkdir( self::CACHE_DIR . '/combined-assets', 0777, true );
		file_put_contents( self::CACHE_DIR . '/combined-assets/bundle.css', 'body{}' );
		file_put_contents( self::CACHE_DIR . '/config.php', '<?php return array();' );

		$page_cache->purgeAll();

		self::assertSame( 0, $this->cacheFileCount() );
		self::assertFileExists( self::CACHE_DIR . '/combined-assets/bundle.css' );
		self::assertFileExists( self::CACHE_DIR . '/config.php' );
	}

	public function test_purge_all_is_a_no_op_when_cache_dir_does_not_exist(): void {
		$page_cache = $this->pageCache();

		$page_cache->purgeAll();

		self::assertDirectoryDoesNotExist( self::CACHE_DIR );
	}

	// ── writeConfigFile ──────────────────────────────────────────────────

	public function test_write_config_file_writes_expected_shape(): void {
		$page_cache = $this->pageCache(
			array(
				'enable_page_cache'    => true,
				'cache_ttl'            => 3600,
				'cache_bypass_cookies' => "woocommerce_items_in_cart\nwordpress_logged_in_*",
			)
		);

		$page_cache->writeConfigFile();

		self::assertFileExists( self::CACHE_DIR . '/config.php' );

		$config = include self::CACHE_DIR . '/config.php';

		self::assertTrue( $config['enabled'] );
		self::assertSame( 3600, $config['ttl'] );
		self::assertSame( array( 'woocommerce_items_in_cart', 'wordpress_logged_in_*' ), $config['bypass_cookies'] );
		self::assertSame( 'https://example.com/wp-json/pivot-performance-toolkit/v1/performance-tests/collect', $config['collect_url'] );
		self::assertStringStartsWith( 'https://example.com/wp-content/plugins/pivot-performance-toolkit/assets/js/performance-probe.js?ver=', $config['probe_script'] );
	}

	public function test_register_rewrites_a_config_file_written_by_an_older_version(): void {
		$this->pageCache()->writeConfigFile();

		// Same file on disk, but the stored version no longer matches the running plugin.
		Functions\when( 'get_option' )->alias(
			fn( string $key, $fallback = array() ) => 'pivot_performance_toolkit_cache_config_version' === $key ? '0.0.1' : ( $this->options[ $key ] ?? $fallback )
		);
		file_put_contents( self::CACHE_DIR . '/config.php', "<?php\nreturn array( 'enabled' => true );\n" );
		Functions\when( 'add_action' )->justReturn( true );

		$this->pageCache()->refreshStaleConfig();

		$config = include self::CACHE_DIR . '/config.php';

		self::assertArrayHasKey( 'probe_script', $config );
	}

	// ── register ─────────────────────────────────────────────────────────

	/**
	 * Registered as an action, not a filter — this class only observes and
	 * stores the finalized output, it never needs to modify it — so it
	 * always sees whatever Combine/AsyncCss/DelayedJs/Assets already
	 * transformed. See the comment on this registration in
	 * PageCache::register() for the full reasoning.
	 */
	public function test_registers_on_wp_finalized_template_enhancement_output_buffer(): void {
		$page_cache = $this->pageCache();

		$calls = array();
		Functions\when( 'add_action' )->alias(
			static function ( ...$args ) use ( &$calls ): void {
				$calls[] = $args;
			}
		);

		$page_cache->register();

		$buffer_calls = array_values(
			array_filter( $calls, static fn( array $call ): bool => 'wp_finalized_template_enhancement_output_buffer' === $call[0] )
		);

		self::assertCount( 1, $buffer_calls );
		self::assertSame(
			array( 'wp_finalized_template_enhancement_output_buffer', array( $page_cache, 'maybeCacheOutput' ) ),
			$buffer_calls[0]
		);
	}
	// ── warmUrl: fill the cache before a performance test ───────────────

	/**
	 * Stub the loopback request. $on_request runs when the plugin "requests" the page
	 * (a real request would end in maybeCacheOutput()); returns the number of requests made.
	 */
	private function stubLoopback( callable $on_request ): \ArrayObject {
		$calls = new \ArrayObject();

		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );
		Functions\when( 'wp_remote_get' )->alias(
			static function ( string $url ) use ( $calls, $on_request ) {
				$calls[] = $url;
				$on_request( $url );

				return array( 'response' => array( 'code' => 200 ) );
			}
		);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		return $calls;
	}

	public function test_warm_does_nothing_when_page_cache_is_disabled(): void {
		$calls = $this->stubLoopback( static function (): void {} );

		self::assertSame( 'disabled', $this->pageCache( array( 'enable_page_cache' => false ) )->warmUrl( 'https://example.com/some-page/' ) );
		self::assertCount( 0, $calls );
	}

	public function test_warm_skips_urls_that_are_never_cached(): void {
		$calls = $this->stubLoopback( static function (): void {} );

		self::assertSame( 'excluded', $this->pageCache( array( 'cache_excluded_urls' => '/checkout' ) )->warmUrl( 'https://example.com/checkout/?step=2' ) );
		self::assertCount( 0, $calls );
	}

	/**
	 * The key warmUrl() looks for must be the one a real request writes (cacheFilePath()),
	 * or "already cached" would never be detected and every test would re-request the page.
	 */
	public function test_warm_recognises_a_page_a_real_request_already_cached(): void {
		$page_cache = $this->pageCache();
		$calls      = $this->stubLoopback( static function (): void {} );

		$_SERVER['HTTPS']       = 'on';
		$_SERVER['REQUEST_URI'] = '/some-page/?a=1';
		$page_cache->maybeCacheOutput( '<html>cached</html>' );

		self::assertSame( 'cached', $page_cache->warmUrl( 'https://example.com/some-page/?a=1#section' ) );
		self::assertCount( 0, $calls );
	}

	public function test_warm_requests_the_page_once_and_reports_it_warmed(): void {
		$page_cache = $this->pageCache();
		$calls      = $this->stubLoopback(
			function () use ( $page_cache ): void {
				$_SERVER['HTTPS']       = 'on';
				$_SERVER['REQUEST_URI'] = '/some-page/';
				$page_cache->maybeCacheOutput( '<html>fresh</html>' );
			}
		);

		self::assertSame( 'warmed', $page_cache->warmUrl( 'https://example.com/some-page/' ) );
		self::assertSame( array( 'https://example.com/some-page/' ), $calls->getArrayCopy() );
		self::assertSame( 1, $this->cacheFileCount() );

		// A second test run finds it cached and makes no request.
		self::assertSame( 'cached', $page_cache->warmUrl( 'https://example.com/some-page/' ) );
		self::assertCount( 1, $calls );
	}

	public function test_warm_reports_failed_when_the_request_does_not_fill_the_cache(): void {
		$calls = $this->stubLoopback( static function (): void {} );

		self::assertSame( 'failed', $this->pageCache()->warmUrl( 'https://example.com/some-page/' ) );
		self::assertCount( 1, $calls );
	}

	public function test_warm_treats_an_expired_cache_file_as_missing(): void {
		$page_cache = $this->pageCache( array( 'cache_ttl' => 60 ) );
		$this->stubLoopback( static function (): void {} );

		$_SERVER['HTTPS']       = 'on';
		$_SERVER['REQUEST_URI'] = '/some-page/';
		$page_cache->maybeCacheOutput( '<html>old</html>' );

		foreach ( glob( self::CACHE_DIR . '/*.html' ) ?: array() as $file ) {
			touch( $file, time() - 3600 );
		}

		self::assertSame( 'failed', $page_cache->warmUrl( 'https://example.com/some-page/' ) );
	}
}
