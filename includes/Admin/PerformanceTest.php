<?php
/**
 * Basic performance test feature.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Core\Settings;
use PivotPerformanceToolkit\Utils\PerformanceProfileBands;
use PivotPerformanceToolkit\Views\BladeEngine;

final class PerformanceTest implements ModuleInterface {

	private const LAST_RESULT_OPTION = 'pivot_performance_toolkit_last_performance_result';
	private const TRANSIENT_PREFIX   = 'pivot_performance_toolkit_test_';
	private const SCORE_WEIGHTS      = array(
		'page_load_time' => 25,
		'lcp'            => 25,
		'ttfb'           => 15,
		'fcp'            => 10,
		'resource_count' => 10,
		'js_size'        => 7,
		'css_size'       => 4,
		'image_size'     => 4,
	);
	private const SCORE_MULTIPLIERS  = array(
		'excellent'         => 1.0,
		'good'              => 0.8,
		'needs_improvement' => 0.5,
		'poor'              => 0.0,
	);

	private Settings $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAdminAssets' ) );
		add_action( 'rest_api_init', array( $this, 'registerRestRoutes' ) );
		add_action( 'wp_head', array( $this, 'renderFrontendProbeScript' ), 0 );
		add_action( 'wp_footer', array( $this, 'renderFrontendProbeScript' ), 99 );
	}

	public function renderPerformanceTestCard(): void {
		$last_result = get_option( self::LAST_RESULT_OPTION, array() );
		$last_score  = self::calculateOverallScoreFromResult( is_array( $last_result ) ? $last_result : array() );

		echo BladeEngine::view(
			'cards.optimization.performance.performance-test',
			array(
				'options'    => self::getTestableContentOptions(),
				'last_score' => $last_score,
			)
		);
	}

	public function enqueueAdminAssets( string $hook_suffix ): void {
		if ( 'toplevel_page_pivot-performance-toolkit' !== $hook_suffix ) {
			return;
		}

		$last_result = get_option( self::LAST_RESULT_OPTION, array() );

		wp_enqueue_script(
			'pivot-performance-toolkit-performance-test',
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'src/js/performance-test.js',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION,
			true
		);

		wp_localize_script(
			'pivot-performance-toolkit-performance-test',
			'ptkPerfTest',
			array(
				'restRoot'         => esc_url_raw( rest_url( 'ptk/v1/performance-tests/' ) ),
				'nonce'            => wp_create_nonce( 'wp_rest' ),
				'defaultUrl'       => esc_url_raw( home_url( '/' ) ),
				'websiteProfile'   => $this->settings->getString( 'website_profile' ) ?: 'standard',
				'profileBands'     => array(
					'resourceCount' => PerformanceProfileBands::resourceCount(),
					'cssSize'       => PerformanceProfileBands::cssSize(),
					'jsSize'        => PerformanceProfileBands::jsSize(),
					'imageSize'     => PerformanceProfileBands::imageSize(),
				),
				'scoreWeights'     => self::SCORE_WEIGHTS,
				'scoreMultipliers' => self::SCORE_MULTIPLIERS,
				'pollInterval'     => 1000,
				'timeoutMs'        => 45000,
				'lastResult'       => is_array( $last_result ) ? $last_result : array(),
				'i18n'             => array(
					'starting'               => __( 'Starting test...', 'pivot-performance-toolkit' ),
					'running'                => __( 'Loading target URL and collecting metrics...', 'pivot-performance-toolkit' ),
					'done'                   => __( 'Test complete.', 'pivot-performance-toolkit' ),
					'timeout'                => __( 'Timed out waiting for test result.', 'pivot-performance-toolkit' ),
					'failed'                 => __( 'Could not run test.', 'pivot-performance-toolkit' ),
					'requestFailed'          => __( 'Request failed.', 'pivot-performance-toolkit' ),
					'cacheServed'            => __( 'Your page is being served from cache.', 'pivot-performance-toolkit' ),
					'cacheNotServed'         => __( 'Your page is not being served from cache.', 'pivot-performance-toolkit' ),
					'bandExcellent'          => __( 'Excellent', 'pivot-performance-toolkit' ),
					'bandGood'               => __( 'Good', 'pivot-performance-toolkit' ),
					'bandNeedsImprovement'   => __( 'Needs Improvement', 'pivot-performance-toolkit' ),
					'bandPoor'               => __( 'Poor', 'pivot-performance-toolkit' ),
					'scoreAriaPrefix'        => __( 'Score', 'pivot-performance-toolkit' ),
					'statusExcellent'        => __( 'Excellent', 'pivot-performance-toolkit' ),
					'statusHit'              => __( 'HIT', 'pivot-performance-toolkit' ),
					'statusGood'             => __( 'Good', 'pivot-performance-toolkit' ),
					'statusOkay'             => __( 'Okay', 'pivot-performance-toolkit' ),
					'statusNeedsImprovement' => __( 'Needs improvement', 'pivot-performance-toolkit' ),
					'statusSlow'             => __( 'Slow', 'pivot-performance-toolkit' ),
					'statusModerate'         => __( 'Moderate', 'pivot-performance-toolkit' ),
					'statusPoor'             => __( 'Poor', 'pivot-performance-toolkit' ),
					'statusMiss'             => __( 'MISS', 'pivot-performance-toolkit' ),
					'statusHeavy'            => __( 'Heavy', 'pivot-performance-toolkit' ),
					'statusHigh'             => __( 'High', 'pivot-performance-toolkit' ),
					'cacheHit'               => __( 'HIT', 'pivot-performance-toolkit' ),
					'cacheMiss'              => __( 'MISS', 'pivot-performance-toolkit' ),
				),
			)
		);
	}

	public function registerRestRoutes(): void {
		register_rest_route(
			'ptk/v1',
			'/performance-tests/start',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'callback'            => array( $this, 'restStartTest' ),
			)
		);

		register_rest_route(
			'ptk/v1',
			'/performance-tests/collect',
			array(
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'restCollectTest' ),
			)
		);

		register_rest_route(
			'ptk/v1',
			'/performance-tests/result',
			array(
				'methods'             => 'GET',
				'permission_callback' => static function (): bool {
					return current_user_can( 'manage_options' );
				},
				'callback'            => array( $this, 'restGetResult' ),
			)
		);
	}

	public function restStartTest( \WP_REST_Request $request ) {
		$raw_target = trim( (string) $request->get_param( 'targetUrl' ) );
		$target_url = self::normalizeTargetUrl( $raw_target );

		if ( '' === $target_url || ! self::isSameHostUrl( $target_url ) ) {
			return new \WP_Error( 'pivot_performance_toolkit_invalid_target', __( 'Please provide a valid URL on this site.', 'pivot-performance-toolkit' ), array( 'status' => 400 ) );
		}

		$token = wp_generate_password( 40, false, false );
		$state = array(
			'status'       => 'pending',
			'created_at'   => gmdate( 'c' ),
			'target_url'   => $target_url,
			'requested_by' => get_current_user_id(),
		);

		set_transient( self::TRANSIENT_PREFIX . $token, $state, 10 * MINUTE_IN_SECONDS );

		return rest_ensure_response(
			array(
				'token'   => $token,
				'testUrl' => $target_url,
			)
		);
	}

	public function restCollectTest( \WP_REST_Request $request ) {
		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );

		if ( '' === $token ) {
			return new \WP_Error( 'pivot_performance_toolkit_missing_token', __( 'Missing test token.', 'pivot-performance-toolkit' ), array( 'status' => 400 ) );
		}

		$state = get_transient( self::TRANSIENT_PREFIX . $token );

		if ( ! is_array( $state ) || empty( $state['status'] ) ) {
			return new \WP_Error( 'pivot_performance_toolkit_unknown_token', __( 'Test token is invalid or expired.', 'pivot-performance-toolkit' ), array( 'status' => 404 ) );
		}

		$result = array(
			'status'       => 'complete',
			'created_at'   => (string) ( $state['created_at'] ?? gmdate( 'c' ) ),
			'collected_at' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) . ' T' ),
			'target_url'   => (string) ( $state['target_url'] ?? '' ),
			'page_url'     => self::cleanProbeQueryArgs( (string) $request->get_param( 'pageUrl' ) ),
			'metrics'      => self::sanitizeMetrics( (array) $request->get_param( 'metrics' ) ),
		);

		set_transient( self::TRANSIENT_PREFIX . $token, $result, 10 * MINUTE_IN_SECONDS );
		update_option( self::LAST_RESULT_OPTION, $result, false );

		return rest_ensure_response( array( 'success' => true ) );
	}

	public function restGetResult( \WP_REST_Request $request ) {
		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );

		if ( '' === $token ) {
			return new \WP_Error( 'pivot_performance_toolkit_missing_token', __( 'Missing test token.', 'pivot-performance-toolkit' ), array( 'status' => 400 ) );
		}

		$state = get_transient( self::TRANSIENT_PREFIX . $token );

		if ( ! is_array( $state ) ) {
			return new \WP_Error( 'pivot_performance_toolkit_unknown_token', __( 'Test token is invalid or expired.', 'pivot-performance-toolkit' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $state );
	}

	public function renderFrontendProbeScript(): void {
		static $printed = false;

		if ( $printed || is_admin() ) {
			return;
		}

		$printed     = true;
		$collect_url = esc_url_raw( rest_url( 'ptk/v1/performance-tests/collect' ) );
		$safe_url    = wp_json_encode( $collect_url );

		echo "\n<script>\n";
		echo '(function(){';
		echo 'var collectUrl=' . $safe_url . ';';
		echo 'function getToken(){var token="";try{token=String(window.name||"").trim();}catch(e){}if(token&&token.length>=20){return token;}try{var m=document.cookie.match(/(?:^|;\\s*)pivot_performance_toolkit_perf_probe=([^;]+)/);if(m){token=String(m[1]||"").trim();if(token.length>=20){return token;}}}catch(e){}return "";}';
		echo 'var fcp=0;';
		echo 'var lcp=0;';
		echo 'if("PerformanceObserver" in window){try{var paintObserver=new PerformanceObserver(function(list){var entries=list.getEntries();entries.forEach(function(entry){if(entry.name==="first-contentful-paint"){fcp=fcp||entry.startTime||0;}});});paintObserver.observe({type:"paint",buffered:true});var po=new PerformanceObserver(function(list){var entries=list.getEntries();if(entries.length){lcp=entries[entries.length-1].startTime||lcp;}});po.observe({type:"largest-contentful-paint",buffered:true});}catch(e){}}';
		echo 'function num(value){var n=Number(value);return Number.isFinite(n)?Math.round(n*100)/100:0;}';
		echo 'function getFcpMetric(){var paints=performance.getEntriesByType("paint")||[];var paintEntry=paints.find(function(p){return p.name==="first-contentful-paint";});var namedEntry=performance.getEntriesByName("first-contentful-paint", "paint")[0]||null;var entry=paintEntry||namedEntry;return num(entry&&entry.startTime?entry.startTime:fcp);}';
		echo 'function getJsMetrics(){var scripts=document.querySelectorAll("script[src]")||[];var count=scripts.length;var totalSize=0;var resources=performance.getEntriesByType("resource")||[];resources.forEach(function(r){if(r.name&&(r.name.endsWith(".js")||r.initiatorType==="script")){var size=r.transferSize||r.encodedBodySize||0;totalSize+=size;}});return{total_js_count:count,total_js_size_bytes:totalSize};}';
		echo 'function getCssMetrics(){var links=document.querySelectorAll("link[rel=\"stylesheet\"]")||[];var count=links.length;var totalSize=0;var resources=performance.getEntriesByType("resource")||[];resources.forEach(function(r){if(r.name&&(r.name.endsWith(".css")||r.initiatorType==="link")){var size=r.transferSize||r.encodedBodySize||0;totalSize+=size;}});return{total_css_count:count,total_css_size_bytes:totalSize};}';
		echo 'function getImageMetrics(){var images=document.querySelectorAll("img")||[];var count=images.length;var totalSize=0;var resources=performance.getEntriesByType("resource")||[];resources.forEach(function(r){var name=String(r&&r.name?r.name:"").toLowerCase();var type=String(r&&r.initiatorType?r.initiatorType:"").toLowerCase();if(type==="img"||/\.(avif|bmp|gif|heic|heif|ico|jpe?g|png|svg|webp|tif|tiff)(\?|#|$)/i.test(name)){var size=r.transferSize||r.encodedBodySize||0;totalSize+=size;}});return{total_image_count:count,total_image_size_bytes:totalSize};}';
		echo 'function collect(){var nav=(performance.getEntriesByType("navigation")[0]||null);if(!fcp){fcp=getFcpMetric();}if(!lcp){var lcpEntries=performance.getEntriesByType("largest-contentful-paint")||[];if(lcpEntries.length){lcp=lcpEntries[lcpEntries.length-1].startTime||0;}}var jsMetrics=getJsMetrics();var cssMetrics=getCssMetrics();var imageMetrics=getImageMetrics();var resources=performance.getEntriesByType("resource")||[];var metrics={ttfb_ms:num(nav&&nav.responseStart?nav.responseStart:0),fcp_ms:num(fcp),lcp_ms:num(lcp),dom_content_loaded_ms:num(nav&&nav.domContentLoadedEventEnd?nav.domContentLoadedEventEnd:0),load_event_ms:num(nav&&nav.loadEventEnd?nav.loadEventEnd:0),total_resource_count:resources.length,total_js_count:jsMetrics.total_js_count,total_js_size_bytes:jsMetrics.total_js_size_bytes,total_css_count:cssMetrics.total_css_count,total_css_size_bytes:cssMetrics.total_css_size_bytes,total_image_count:imageMetrics.total_image_count,total_image_size_bytes:imageMetrics.total_image_size_bytes};return metrics;}';
		echo 'function getCacheHit(){return fetch(window.location.href,{method:"GET",credentials:"same-origin",cache:"no-store"}).then(function(response){var cacheStatus=String(response.headers.get("x-performance-toolkit-cache")||"").toUpperCase();return cacheStatus==="HIT"?1:0;}).catch(function(){return 0;});}';
		// The token is (re-)read here, at send-time, rather than once at parse-time:
		// if this cached page also carries a freshly-injected probe script (from the
		// advanced-cache drop-in, for a genuine cache HIT), that script deletes the
		// cookie/window.name synchronously as soon as it parses, well before this
		// deferred send() fires. Reading the token lazily lets this copy correctly
		// detect that and stand down, instead of racing the fresh script and
		// overwriting its correct result with a stale re-check.
		echo 'function send(){var token=getToken();if(!token){return;}var sentKey="pivot_performance_toolkit_perf_sent_"+token;try{if(window.sessionStorage&&window.sessionStorage.getItem(sentKey)==="1"){return;}}catch(e){}return getCacheHit().then(function(cacheHit){document.cookie="pivot_performance_toolkit_perf_probe=;path=/;SameSite=Lax;max-age=0;expires=Thu, 01 Jan 1970 00:00:00 GMT";var metrics=collect();metrics.page_cache_hit=cacheHit;var payload={token:token,pageUrl:window.location.href,metrics:metrics};return fetch(collectUrl,{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify(payload),keepalive:true,credentials:"omit"}).then(function(){try{if(window.sessionStorage){window.sessionStorage.setItem(sentKey,"1");}}catch(e){}try{window.name="";}catch(e){} });}).catch(function(){});}';
		echo 'window.addEventListener("load",function(){window.setTimeout(send,300);});';
		echo '})();';
		echo "\n</script>\n";
	}

	/**
	 * @return array{pages:array<int,array{label:string,url:string}>,posts:array<int,array{label:string,url:string}>}
	 */
	public static function getTestableContentOptions(): array {
		$types  = array(
			'pages' => 'page',
			'posts' => 'post',
		);
		$result = array(
			'pages' => array(),
			'posts' => array(),
		);

		$front_page_id = 0;
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$front_page_id = (int) get_option( 'page_on_front' );
		}

		foreach ( $types as $bucket => $post_type ) {
			$exclude = ( 'pages' === $bucket && $front_page_id > 0 ) ? array( $front_page_id ) : array();
			$items   = get_posts(
				array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'exclude'        => $exclude,
				)
			);

			if ( ! is_array( $items ) || empty( $items ) ) {
				continue;
			}

			foreach ( $items as $post_id ) {
				$url = get_permalink( (int) $post_id );

				if ( ! is_string( $url ) || '' === $url ) {
					continue;
				}

				$title = get_the_title( (int) $post_id );

				$label = is_string( $title ) && '' !== trim( $title ) ? $title : sprintf(
					/* translators: %d: post ID. */
					__( 'Untitled #%d', 'pivot-performance-toolkit' ),
					(int) $post_id
				);

				$result[ $bucket ][] = array(
					'label' => $label,
					'url'   => $url,
				);
			}

			usort(
				$result[ $bucket ],
				static function ( array $a, array $b ): int {
					return strcasecmp( (string) $a['label'], (string) $b['label'] );
				}
			);
		}

		return $result;
	}

	/**
	 * @param array<string,mixed> $metrics
	 * @return array<string,float|int>
	 */
	private static function sanitizeMetrics( array $metrics ): array {
		$keys = array(
			'ttfb_ms',
			'fcp_ms',
			'lcp_ms',
			'dom_content_loaded_ms',
			'load_event_ms',
			'total_resource_count',
			'total_js_count',
			'total_js_size_bytes',
			'total_css_count',
			'total_css_size_bytes',
			'total_image_count',
			'total_image_size_bytes',
			'page_cache_hit',
		);

		$sanitized = array();

		foreach ( $keys as $key ) {
			$value = isset( $metrics[ $key ] ) ? (float) $metrics[ $key ] : 0.0;

			if ( 'total_resource_count' === $key || 'total_js_count' === $key || 'total_css_count' === $key || 'total_image_count' === $key || 'page_cache_hit' === $key ) {
				$sanitized[ $key ] = max( 0, (int) $value );
			} else {
				$sanitized[ $key ] = round( max( 0.0, $value ), 2 );
			}
		}

		return $sanitized;
	}

	private static function normalizeTargetUrl( string $target ): string {
		if ( '' === $target ) {
			return home_url( '/' );
		}

		if ( str_starts_with( $target, '/' ) ) {
			return home_url( $target );
		}

		$sanitized = esc_url_raw( $target );

		if ( '' === $sanitized || ! wp_http_validate_url( $sanitized ) ) {
			return '';
		}

		return $sanitized;
	}

	private static function isSameHostUrl( string $url ): bool {
		$target_host = (string) wp_parse_url( $url, PHP_URL_HOST );
		$site_host   = (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( '' === $target_host || '' === $site_host ) {
			return false;
		}

		return strtolower( $target_host ) === strtolower( $site_host );
	}

	private static function cleanProbeQueryArgs( string $url ): string {
		$sanitized = esc_url_raw( $url );

		if ( '' === $sanitized ) {
			return '';
		}

		return (string) remove_query_arg( array( 'pivot_performance_toolkit_perf_probe', 'pivot_performance_toolkit_perf_token' ), $sanitized );
	}

	/**
	 * @param array<string,mixed> $result
	 */
	public static function calculateOverallScoreFromResult( array $result ): int {
		$metrics = isset( $result['metrics'] ) && is_array( $result['metrics'] ) ? $result['metrics'] : array();

		if ( array() === $metrics ) {
			return 0;
		}

		$status_by_metric = array(
			'load_event_ms'          => self::classifyPageLoadTime( $metrics['load_event_ms'] ?? 0 ),
			'lcp_ms'                 => self::classifyLcp( $metrics['lcp_ms'] ?? 0 ),
			'ttfb_ms'                => self::classifyTtfb( $metrics['ttfb_ms'] ?? 0 ),
			'fcp_ms'                 => self::classifyFcp( $metrics['fcp_ms'] ?? 0 ),
			'total_resource_count'   => self::classifyResourceCount( $metrics['total_resource_count'] ?? 0 ),
			'total_js_size_bytes'    => self::classifyBytesFromBands( $metrics['total_js_size_bytes'] ?? 0, PerformanceProfileBands::jsSize()['standard'] ?? array() ),
			'total_css_size_bytes'   => self::classifyBytesFromBands( $metrics['total_css_size_bytes'] ?? 0, PerformanceProfileBands::cssSize()['standard'] ?? array() ),
			'total_image_size_bytes' => self::classifyBytesFromBands( $metrics['total_image_size_bytes'] ?? 0, PerformanceProfileBands::imageSize()['standard'] ?? array() ),
		);

		$metric_map = array(
			'page_load_time' => 'load_event_ms',
			'lcp'            => 'lcp_ms',
			'ttfb'           => 'ttfb_ms',
			'fcp'            => 'fcp_ms',
			'resource_count' => 'total_resource_count',
			'js_size'        => 'total_js_size_bytes',
			'css_size'       => 'total_css_size_bytes',
			'image_size'     => 'total_image_size_bytes',
		);

		$weighted_total = 0.0;
		$total_weight   = 0.0;

		foreach ( self::SCORE_WEIGHTS as $section => $weight ) {
			$metric_key = $metric_map[ $section ] ?? '';
			if ( '' === $metric_key ) {
				continue;
			}

			$status     = (string) ( $status_by_metric[ $metric_key ] ?? 'poor' );
			$bucket     = self::normalizeScoreBucket( $status );
			$multiplier = (float) ( self::SCORE_MULTIPLIERS[ $bucket ] ?? 0.0 );

			$total_weight   += (float) $weight;
			$weighted_total += (float) $weight * $multiplier;
		}

		if ( $total_weight <= 0 ) {
			return 0;
		}

		return (int) round( ( $weighted_total / $total_weight ) * 100 );
	}

	private static function normalizeScoreBucket( string $status ): string {
		$lower = strtolower( trim( $status ) );

		if ( in_array( $lower, array( 'excellent' ), true ) ) {
			return 'excellent';
		}

		if ( in_array( $lower, array( 'good', 'okay' ), true ) ) {
			return 'good';
		}

		if ( in_array( $lower, array( 'needs improvement', 'moderate', 'slow' ), true ) ) {
			return 'needs_improvement';
		}

		return 'poor';
	}

	/** @param mixed $value */
	private static function classifyTtfb( $value ): string {
		$n = (float) $value;
		if ( $n <= 0 ) {
			return 'poor';
		}
		if ( $n <= 200 ) {
			return 'excellent';
		}
		if ( $n <= 600 ) {
			return 'okay';
		}
		if ( $n <= 1000 ) {
			return 'slow';
		}
		return 'poor';
	}

	/** @param mixed $value */
	private static function classifyFcp( $value ): string {
		$n = (float) $value;
		if ( $n <= 0 ) {
			return 'poor';
		}
		if ( $n < 1000 ) {
			return 'excellent';
		}
		if ( $n <= 1800 ) {
			return 'good';
		}
		if ( $n <= 3000 ) {
			return 'needs improvement';
		}
		return 'poor';
	}

	/** @param mixed $value */
	private static function classifyLcp( $value ): string {
		$n = (float) $value;
		if ( $n <= 0 ) {
			return 'poor';
		}
		if ( $n < 1200 ) {
			return 'excellent';
		}
		if ( $n <= 2500 ) {
			return 'good';
		}
		if ( $n <= 4000 ) {
			return 'moderate';
		}
		return 'poor';
	}

	/** @param mixed $value */
	private static function classifyPageLoadTime( $value ): string {
		$n = (float) $value;
		if ( $n <= 0 ) {
			return 'poor';
		}
		if ( $n < 1000 ) {
			return 'excellent';
		}
		if ( $n <= 2000 ) {
			return 'good';
		}
		if ( $n <= 4000 ) {
			return 'moderate';
		}
		return 'poor';
	}

	/** @param mixed $value */
	private static function classifyResourceCount( $value ): string {
		$bands = PerformanceProfileBands::resourceCount()['standard'] ?? array();
		return self::classifyBytesFromBands( $value, $bands );
	}

	/**
	 * @param mixed $value
	 * @param array<int,array{label:string,max:int|null}> $bands
	 */
	private static function classifyBytesFromBands( $value, array $bands ): string {
		$n = (float) $value;
		if ( $n <= 0 ) {
			return 'poor';
		}

		foreach ( $bands as $band ) {
			$max = $band['max'];
			if ( null === $max || $n <= (float) $max ) {
				return (string) ( $band['label'] ?? 'poor' );
			}
		}

		return 'poor';
	}
}
