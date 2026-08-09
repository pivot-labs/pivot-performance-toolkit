<?php
/**
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Utils;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Cache\ObjectCacheManager;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Tests\Unit\TestCase;
use PivotPerformanceToolkit\Utils\Recommendations;
use ReflectionMethod;

final class RecommendationsTest extends TestCase {

	/** @var array<string, mixed> */
	private array $options = array();

	protected function setUp(): void {
		parent::setUp();

		$this->options = array();

		Functions\when( 'wp_parse_args' )->alias(
			static fn( $args, $defaults = array() ): array => array_merge( $defaults, (array) $args )
		);
		Functions\when( 'add_query_arg' )->alias(
			static function ( array $args, string $url ): string {
				return $url . '?' . http_build_query( $args );
			}
		);
		Functions\when( 'admin_url' )->alias( static fn( string $path = '' ): string => 'https://example.com/wp-admin/' . $path );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'did_action' )->justReturn( 0 );
		Functions\when( '__' )->returnArg();

		// get_option is called for several unrelated things (this plugin's
		// own settings, WordPress's active_plugins list, transient-backed
		// caches) — dispatch by option name rather than a single blanket
		// stub so each rule's test can control only what it cares about.
		Functions\when( 'get_option' )->alias(
			function ( string $option, $default = false ) {
				return $this->options[ $option ] ?? $default;
			}
		);
		Functions\when( 'get_transient' )->alias(
			function ( string $key ) {
				return $this->options[ 'transient:' . $key ] ?? false;
			}
		);
	}

	private function settings( array $option_overrides = array() ): Settings {
		$this->options['pivot_performance_toolkit_settings'] = $option_overrides;

		return new Settings();
	}

	private function objectCacheManager(): ObjectCacheManager {
		return new ObjectCacheManager();
	}

	private function recommendations( array $option_overrides = array() ): Recommendations {
		return new Recommendations( $this->settings( $option_overrides ), $this->objectCacheManager() );
	}

	/**
	 * @return mixed
	 */
	private function call( Recommendations $recommendations, string $method ) {
		$reflection = new ReflectionMethod( Recommendations::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invoke( $recommendations );
	}

	private function stubProtocol( string $version, bool $is_http11, bool $is_modern ): void {
		$this->options['transient:pivot_performance_toolkit_http_protocol'] = array(
			'version'   => $version,
			'is_http11' => $is_http11,
			'is_modern' => $is_modern,
			'source'    => 'test',
		);
	}

	private function stubCacheWritable( bool $writable ): void {
		$this->options['transient:pivot_performance_toolkit_fs_status'] = array(
			'writable' => $writable,
			'dirs'     => array(),
			'errors'   => array(),
		);
	}

	// ── optimizationSettingRules ────────────────────────────────────────

	public function test_optimization_rules_recommend_every_setting_that_is_off(): void {
		// defer_scripts and lazy_load_images default to true, so they have
		// to be explicitly turned off here to exercise "every setting off".
		$recommendations = $this->recommendations(
			array(
				'defer_scripts'    => false,
				'lazy_load_images' => false,
			)
		);
		$this->stubCacheWritable( true );

		$items = $this->call( $recommendations, 'optimizationSettingRules' );

		self::assertCount( 5, $items );
		foreach ( $items as $item ) {
			self::assertSame( 'optimization', $item['category'] );
			self::assertSame( 'suggested', $item['severity'] );
			self::assertNotNull( $item['action'] );
		}

		self::assertSame(
			array( 'defer_scripts', 'minify_html', 'minify_css', 'minify_js', 'lazy_load_images' ),
			array_column( $items, 'id' )
		);
	}

	public function test_optimization_rules_recommend_nothing_once_all_settings_are_on(): void {
		$recommendations = new Recommendations(
			$this->settings(
				array(
					'defer_scripts'    => true,
					'minify_html'      => true,
					'minify_css'       => true,
					'minify_js'        => true,
					'lazy_load_images' => true,
				)
			),
			$this->objectCacheManager()
		);

		self::assertSame( array(), $this->call( $recommendations, 'optimizationSettingRules' ) );
	}

	public function test_optimization_rules_only_recommend_the_settings_still_off(): void {
		// defer_scripts and lazy_load_images already default to true.
		$recommendations = new Recommendations(
			$this->settings( array( 'minify_html' => true ) ),
			$this->objectCacheManager()
		);

		$ids = array_column( $this->call( $recommendations, 'optimizationSettingRules' ), 'id' );

		self::assertSame( array( 'minify_css', 'minify_js' ), $ids );
	}

	// ── protocolRules ───────────────────────────────────────────────────

	public function test_protocol_rule_suggests_combine_on_http11_when_off(): void {
		$this->stubProtocol( '1.1', true, false );
		$recommendations = $this->recommendations( array( 'combine_css' => false, 'combine_js' => false ) );

		$items = $this->call( $recommendations, 'protocolRules' );

		self::assertCount( 1, $items );
		self::assertSame( 'combine_http11', $items[0]['id'] );
		self::assertSame( 'suggested', $items[0]['severity'] );
	}

	public function test_protocol_rule_silent_on_http11_when_combine_already_fully_on(): void {
		$this->stubProtocol( '1.1', true, false );
		$recommendations = $this->recommendations( array( 'combine_css' => true, 'combine_js' => true ) );

		self::assertSame( array(), $this->call( $recommendations, 'protocolRules' ) );
	}

	/**
	 * The bug this closes: the old card only ever described the detected
	 * protocol, it never checked live settings — so a site on HTTP/2 with
	 * combine already turned on got no warning at all that its own
	 * configuration contradicted the advice being displayed right next to it.
	 */
	public function test_protocol_rule_warns_to_disable_combine_on_http2_when_on(): void {
		$this->stubProtocol( '2', false, true );
		$recommendations = $this->recommendations( array( 'combine_css' => true, 'combine_js' => false ) );

		$items = $this->call( $recommendations, 'protocolRules' );

		self::assertCount( 1, $items );
		self::assertSame( 'combine_http2_plus', $items[0]['id'] );
		self::assertSame( 'warning', $items[0]['severity'] );
	}

	public function test_protocol_rule_silent_on_http2_when_combine_already_off(): void {
		$this->stubProtocol( '2', false, true );
		$recommendations = $this->recommendations( array( 'combine_css' => false, 'combine_js' => false ) );

		self::assertSame( array(), $this->call( $recommendations, 'protocolRules' ) );
	}

	public function test_protocol_rule_silent_when_protocol_unknown(): void {
		$this->stubProtocol( 'unknown', false, false );
		$recommendations = $this->recommendations( array( 'combine_css' => true ) );

		self::assertSame( array(), $this->call( $recommendations, 'protocolRules' ) );
	}

	// ── profileRule ─────────────────────────────────────────────────────

	public function test_profile_rule_silent_when_no_signals_detected(): void {
		$recommendations = $this->recommendations( array( 'website_profile' => 'standard' ) );

		self::assertSame( array(), $this->call( $recommendations, 'profileRule' ) );
	}

	public function test_profile_rule_silent_when_detected_profile_matches_current(): void {
		$this->options['active_plugins'] = array( 'woocommerce/woocommerce.php' );
		$recommendations                 = $this->recommendations( array( 'website_profile' => 'woocommerce' ) );

		self::assertSame( array(), $this->call( $recommendations, 'profileRule' ) );
	}

	public function test_profile_rule_recommends_switch_when_detected_profile_differs(): void {
		$this->options['active_plugins'] = array( 'woocommerce/woocommerce.php' );
		$recommendations                 = $this->recommendations( array( 'website_profile' => 'standard' ) );

		$items = $this->call( $recommendations, 'profileRule' );

		self::assertCount( 1, $items );
		self::assertSame( 'website_profile', $items[0]['id'] );
		self::assertSame( 'profile', $items[0]['category'] );
		self::assertSame( array( 'WooCommerce' ), $items[0]['signals'] );
	}

	// ── cacheWritableRule ───────────────────────────────────────────────

	public function test_cache_writable_rule_silent_when_writable(): void {
		$this->stubCacheWritable( true );
		$recommendations = $this->recommendations();

		self::assertSame( array(), $this->call( $recommendations, 'cacheWritableRule' ) );
	}

	public function test_cache_writable_rule_warns_when_not_writable(): void {
		$this->stubCacheWritable( false );
		$recommendations = $this->recommendations();

		$items = $this->call( $recommendations, 'cacheWritableRule' );

		self::assertCount( 1, $items );
		self::assertSame( 'cache_writable', $items[0]['id'] );
		self::assertSame( 'warning', $items[0]['severity'] );
	}

	// ── objectCacheRule ─────────────────────────────────────────────────

	public function test_object_cache_rule_recommends_enabling_when_no_dropin(): void {
		$recommendations = $this->recommendations();

		$items = $this->call( $recommendations, 'objectCacheRule' );

		self::assertCount( 1, $items );
		self::assertSame( 'object_cache', $items[0]['id'] );
	}

	public function test_object_cache_rule_silent_when_dropin_installed(): void {
		if ( ! is_dir( WP_CONTENT_DIR ) ) {
			mkdir( WP_CONTENT_DIR, 0777, true );
		}

		file_put_contents( WP_CONTENT_DIR . '/object-cache.php', '<?php // test drop-in' );

		$recommendations = $this->recommendations();

		self::assertSame( array(), $this->call( $recommendations, 'objectCacheRule' ) );
	}

	// ── collect(): aggregation + category filtering ────────────────────

	public function test_collect_returns_everything_when_no_category_filter_given(): void {
		$this->stubProtocol( 'unknown', false, false );
		$this->stubCacheWritable( true );

		$recommendations = $this->recommendations(
			array(
				'defer_scripts'    => true,
				'minify_html'      => true,
				'minify_css'       => true,
				'minify_js'        => true,
				'lazy_load_images' => true,
			)
		);

		// Only the object-cache rule should fire in this configuration.
		$items = $recommendations->collect();

		self::assertSame( array( 'object_cache' ), array_column( $items, 'id' ) );
	}

	public function test_collect_filters_to_requested_categories_only(): void {
		$this->stubProtocol( 'unknown', false, false );
		$this->stubCacheWritable( false );

		$recommendations = $this->recommendations();

		$items = $recommendations->collect( array( 'cache' ) );

		self::assertSame( array( 'cache_writable' ), array_column( $items, 'id' ) );
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
}
