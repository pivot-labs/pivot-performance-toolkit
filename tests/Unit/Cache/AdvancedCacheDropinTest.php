<?php
/**
 * The advanced-cache.php drop-in (runs before WordPress loads, sanitizes with plain
 * PHP) and PageCache (runs after, sanitizes via RequestInput) must derive the same
 * cache key from the same request, otherwise the drop-in never finds the files
 * PageCache wrote. This runs the real drop-in in a separate PHP process.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\DataProvider;
use PivotPerformanceToolkit\Cache\PageCache;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class AdvancedCacheDropinTest extends TestCase {

	private string $content_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->content_dir = sys_get_temp_dir() . '/ptk-dropin-' . uniqid();
		mkdir( $this->content_dir . '/cache/pivot-performance-toolkit', 0777, true );
		file_put_contents(
			$this->content_dir . '/cache/pivot-performance-toolkit/config.php',
			"<?php\nreturn array( 'enabled' => true, 'ttl' => 3600, 'bypass_cookies' => array( 'skip_me' ) );\n"
		);

		Functions\when( 'get_option' )->alias( static fn( string $key, $fallback = array() ) => $fallback );
		Functions\when( 'wp_parse_args' )->alias( static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args ) );
	}

	protected function tearDown(): void {
		foreach ( glob( $this->content_dir . '/cache/pivot-performance-toolkit/*' ) ?: array() as $file ) {
			unlink( $file );
		}

		rmdir( $this->content_dir . '/cache/pivot-performance-toolkit' );
		rmdir( $this->content_dir . '/cache' );
		rmdir( $this->content_dir );

		parent::tearDown();
	}

	/**
	 * @return array<string, array{0: array<string, string>, 1: array<string, string>}>
	 */
	public static function requests(): array {
		return array(
			'plain'                   => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/about/' ), array() ),
			'query string'            => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/shop/?a=1&b=two' ), array() ),
			'https'                   => array( array( 'HTTP_HOST' => 'example.com', 'HTTPS' => 'on', 'REQUEST_URI' => '/' ), array() ),
			'percent encoding kept'   => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/caf%C3%A9/' ), array() ),
			'quote and backslash'     => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => "/it's\\here/" ), array() ),
			'control chars and space' => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => "/a b\r\nX-Evil: 1" ), array() ),
			'raw non-ascii'           => array( array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => "/caf\xC3\xA9/" ), array() ),
			'no host'                 => array( array( 'REQUEST_URI' => '/x/' ), array() ),
		);
	}

	/**
	 * @param array<string, string> $server
	 * @param array<string, string> $cookies
	 */
	#[DataProvider( 'requests' )]
	public function test_drop_in_finds_the_file_page_cache_wrote_for_the_same_request( array $server, array $cookies ): void {
		$server['REQUEST_METHOD'] = 'GET';

		// What PageCache sees: WordPress has already slashed the superglobals.
		$_SERVER = array_map( 'addslashes', $server );
		$page    = new PageCache( new Settings() );
		$method  = new \ReflectionMethod( $page, 'cacheFilePath' );
		$path    = $method->invoke( $page );

		file_put_contents( $this->content_dir . '/cache/pivot-performance-toolkit/' . basename( $path ), 'CACHED-BODY' );

		self::assertSame( 'CACHED-BODY', $this->runDropin( $server, $cookies ) );
	}

	public function test_drop_in_ignores_non_get_and_bypass_cookie_requests(): void {
		$server = array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/', 'REQUEST_METHOD' => 'POST' );

		$_SERVER = $server;
		$page    = new PageCache( new Settings() );
		$path    = ( new \ReflectionMethod( $page, 'cacheFilePath' ) )->invoke( $page );
		file_put_contents( $this->content_dir . '/cache/pivot-performance-toolkit/' . basename( $path ), 'CACHED-BODY' );

		self::assertSame( '', $this->runDropin( $server, array() ) );

		$server['REQUEST_METHOD'] = 'GET';
		self::assertSame( 'CACHED-BODY', $this->runDropin( $server, array() ) );
		self::assertSame( '', $this->runDropin( $server, array( 'skip_me' => '1' ) ) );
		self::assertSame( '', $this->runDropin( $server, array( 'wordpress_logged_in_abc' => 'x' ) ) );
	}

	public function test_probe_requests_get_the_probe_script_tag_before_body_end(): void {
		file_put_contents(
			$this->content_dir . '/cache/pivot-performance-toolkit/config.php',
			"<?php\nreturn array( 'enabled' => true, 'ttl' => 3600, 'collect_url' => 'https://example.com/wp-json/x/collect?a=1&b=2', 'probe_script' => 'https://example.com/p/performance-probe.js?ver=1.2' );\n"
		);

		$server = array( 'HTTP_HOST' => 'example.com', 'REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET' );
		$_SERVER = $server;
		$page    = new PageCache( new Settings() );
		$path    = ( new \ReflectionMethod( $page, 'cacheFilePath' ) )->invoke( $page );
		file_put_contents( $this->content_dir . '/cache/pivot-performance-toolkit/' . basename( $path ), '<html><body>Hi</body></html>' );

		$output = $this->runDropin( $server, array( 'pivot_performance_toolkit_perf_probe' => str_repeat( 'a', 24 ) ) );

		self::assertSame(
			"<html><body>Hi\n" . '<script src="https://example.com/p/performance-probe.js?ver=1.2" data-collect-url="https://example.com/wp-json/x/collect?a=1&amp;b=2"></script>' . "\n</body></html>",
			$output
		);

		// Without the probe cookie the cached page is replayed untouched.
		self::assertSame( '<html><body>Hi</body></html>', $this->runDropin( $server, array() ) );
	}

	/**
	 * @param array<string, string> $server
	 * @param array<string, string> $cookies
	 */
	private function runDropin( array $server, array $cookies ): string {
		$script = $this->content_dir . '/run.php';
		file_put_contents(
			$script,
			'<?php define( "ABSPATH", "/" ); define( "WP_CONTENT_DIR", ' . var_export( $this->content_dir, true ) . ' );'
			. '$_SERVER = ' . var_export( $server, true ) . '; $_COOKIE = ' . var_export( $cookies, true ) . ';'
			. 'include ' . var_export( dirname( __DIR__, 3 ) . '/includes/Cache/advanced-cache.php', true ) . ';'
		);

		$output = (string) shell_exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ) . ' 2>/dev/null' );
		unlink( $script );

		return $output;
	}
}
