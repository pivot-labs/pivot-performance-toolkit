<?php
/**
 * Admin menu registration.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Menu {

	private const ROOT_SLUG               = 'pivot-performance-toolkit';
	private const PLUGIN_NAMESPACE_PREFIX = 'PivotPerformanceToolkit\\';

	/**
	 * @var array<string, AdminPageInterface>
	 */
	private array $pages;

	/**
	 * @var string[]
	 */
	private array $page_hooks = array();

	/**
	 * @param AdminPageInterface[] $pages
	 */
	public function __construct( array $pages ) {
		$this->pages = array();

		foreach ( $pages as $page ) {
			$this->pages[ $page->slug() ] = $page;
		}
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'addMenuPage' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueAssets' ) );
		add_action( 'current_screen', array( $this, 'suppressThirdPartyNotices' ), 100 );
		add_filter( 'parent_file', array( $this, 'highlightParentMenu' ) );
	}

	public function addMenuPage(): void {
		$root_page = $this->pages[ self::ROOT_SLUG ] ?? null;

		if ( ! $root_page instanceof AdminPageInterface ) {
			return;
		}

		// The root page is the only visible menu entry; the plugin's own navigation links to the
		// rest. Every other page is still a real, capability-checked admin page (registered
		// without a menu parent, so it is reachable at admin.php?page=<slug> but not listed in
		// the sidebar), which lets WordPress itself tell AdminShell which screen to render.
		$top_level_hook = add_menu_page(
			$root_page->pageTitle(),
			__( 'Performance', 'pivot-performance-toolkit' ),
			'manage_options',
			self::ROOT_SLUG,
			array( $this, 'renderCurrentPage' ),
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'src/img/pivot-performance-toolkit-menu-icon-white.svg',
			81
		);

		if ( is_string( $top_level_hook ) ) {
			$this->page_hooks[] = $top_level_hook;
		}

		foreach ( $this->pages as $slug => $page ) {
			if ( self::ROOT_SLUG === $slug ) {
				continue;
			}

			$hook = add_submenu_page(
				null,
				$page->pageTitle(),
				$page->pageTitle(),
				'manage_options',
				$slug,
				array( $this, 'renderCurrentPage' )
			);

			if ( is_string( $hook ) ) {
				$this->page_hooks[] = $hook;
			}
		}
	}

	/**
	 * Keeps the "Performance" sidebar entry highlighted while one of the plugin's
	 * hidden pages is showing.
	 *
	 * @param mixed $parent_file
	 *
	 * @return mixed
	 */
	public function highlightParentMenu( $parent_file ) {
		global $plugin_page;

		if ( is_string( $plugin_page ) && isset( $this->pages[ $plugin_page ] ) ) {
			return self::ROOT_SLUG;
		}

		return $parent_file;
	}

	public function renderCurrentPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		AdminShell::render( $this->pages );
	}

	public function enqueueAssets( string $hook_suffix ): void {
		// The menu icon appears in the global admin sidebar on every wp-admin
		// screen, not just this plugin's own pages, so its styling can't wait
		// for the page-specific bundle below — a small dedicated stylesheet
		// carries it.
		wp_enqueue_style(
			'pivot-performance-toolkit-menu-icon',
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'assets/css/menu-icon.css',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION
		);

		if ( ! in_array( $hook_suffix, $this->page_hooks, true ) ) {
			return;
		}

		wp_enqueue_style(
			'pivot-performance-toolkit-admin',
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'dist/admin.css',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION
		);

		wp_enqueue_script(
			'pivot-performance-toolkit-admin-js',
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'dist/admin-js.js',
			array(),
			PIVOT_PERFORMANCE_TOOLKIT_VERSION,
			true
		);

		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'pivotPerformanceToolkitSnippet',
			array(
				'copied'     => __( 'Copied!', 'pivot-performance-toolkit' ),
				'copyFailed' => __( 'Failed to copy. Please try again.', 'pivot-performance-toolkit' ),
				'expand'     => __( 'Expand Full Configuration', 'pivot-performance-toolkit' ),
				'collapse'   => __( 'Hide Full Configuration', 'pivot-performance-toolkit' ),
			)
		);

		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'pivotPerformanceToolkitAdmin',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'requestFailed' => __( 'Request failed.', 'pivot-performance-toolkit' ),
				'saved'         => __( 'Saved', 'pivot-performance-toolkit' ),
				'error'         => __( 'Error', 'pivot-performance-toolkit' ),
			)
		);

		// Every plugin page loads this single bundle (see the page_hooks check above), so
		// section-specific data is localized here too and the JS binds against it only
		// when that section's markup is present on the page.
		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'pivotPerformanceToolkitAdvancedRules',
			array(
				'wooDefaults' => array(
					'/cart',
					'/checkout',
					'/my-account',
					'/wc-api/*',
					'/?wc-ajax=*',
				),
			)
		);

		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'pivotPerformanceToolkitBrowserCacheTest',
			array(
				'homeUrl' => home_url(),
				'i18n'    => array(
					'noAssets'          => __( 'No testable assets found. Make sure your WordPress site is publicly accessible.', 'pivot-performance-toolkit' ),
					'results'           => __( 'Results:', 'pivot-performance-toolkit' ),
					'assetsHaveHeaders' => __( 'assets have cache headers', 'pivot-performance-toolkit' ),
					'assetsCompressed'  => __( 'assets are compressed', 'pivot-performance-toolkit' ),
					'configGood'        => __( 'Browser cache configuration looks good!', 'pivot-performance-toolkit' ),
					'applyConfig'       => __( 'Not all assets have cache headers. Apply the configuration below.', 'pivot-performance-toolkit' ),
					'notSet'            => __( 'Not set', 'pivot-performance-toolkit' ),
					'none'              => __( 'None', 'pivot-performance-toolkit' ),
					'unknown'           => __( 'Unknown', 'pivot-performance-toolkit' ),
					'yes'               => __( 'Yes', 'pivot-performance-toolkit' ),
					'errorPrefix'       => __( 'Error:', 'pivot-performance-toolkit' ),
					'na'                => __( 'N/A', 'pivot-performance-toolkit' ),
					'good'              => __( 'GOOD', 'pivot-performance-toolkit' ),
					'check'             => __( 'CHECK', 'pivot-performance-toolkit' ),
					'compressionOk'     => __( 'OK', 'pivot-performance-toolkit' ),
					'notUsed'           => __( 'NOT USED', 'pivot-performance-toolkit' ),
					'css'               => __( 'CSS', 'pivot-performance-toolkit' ),
					'js'                => __( 'JavaScript', 'pivot-performance-toolkit' ),
					'image'             => __( 'Image', 'pivot-performance-toolkit' ),
					'error'             => __( 'Error', 'pivot-performance-toolkit' ),
				),
			)
		);

		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'pivotPerformanceToolkitAssetsDetector',
			array(
				'i18n' => array(
					'selectUrl'         => __( 'Please select a URL first.', 'pivot-performance-toolkit' ),
					'detecting'         => __( 'Detecting assets...', 'pivot-performance-toolkit' ),
					'detectionFailed'   => __( 'Detection failed.', 'pivot-performance-toolkit' ),
					'noAssetsFound'     => __( 'Detection complete. No assets found.', 'pivot-performance-toolkit' ),
					'detectionComplete' => __( 'Detection complete.', 'pivot-performance-toolkit' ),
					'css'               => __( 'CSS', 'pivot-performance-toolkit' ),
					'javascript'        => __( 'JavaScript', 'pivot-performance-toolkit' ),
					'fonts'             => __( 'Fonts', 'pivot-performance-toolkit' ),
					'images'            => __( 'Images', 'pivot-performance-toolkit' ),
					'other'             => __( 'Other', 'pivot-performance-toolkit' ),
					'detected'          => __( 'Detected:', 'pivot-performance-toolkit' ),
				),
			)
		);
	}
	/**
	 * Remove third-party admin notices from plugin screens.
	 */
	public function suppressThirdPartyNotices(): void {
		if ( ! $this->isPluginScreen() ) {
			return;
		}

		$this->filterNoticeHookCallbacks( 'admin_notices' );
		$this->filterNoticeHookCallbacks( 'all_admin_notices' );
	}

	/**
	 * Determine if the current admin screen belongs to this plugin.
	 */
	private function isPluginScreen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}

		return in_array( $screen->id, $this->page_hooks, true );
	}

	/**
	 * Keep only Pivot Performance Toolkit callbacks on the notice hook.
	 *
	 * @param string $hook_name Hook name.
	 */
	private function filterNoticeHookCallbacks( string $hook_name ): void {
		global $wp_filter;

		if ( ! isset( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof \WP_Hook ) {
			return;
		}

		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback_data ) {
				$callback = $callback_data['function'] ?? null;

				if ( ! $this->isPluginNoticeCallback( $callback ) ) {
					remove_action( $hook_name, $callback, (int) $priority );
				}
			}
		}
	}

	/**
	 * Check whether a notice callback belongs to this plugin.
	 *
	 * @param mixed $callback Hook callback.
	 */
	private function isPluginNoticeCallback( $callback ): bool {
		if ( is_array( $callback ) && isset( $callback[0] ) ) {
			$class_name = is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];

			if ( str_starts_with( $class_name, self::PLUGIN_NAMESPACE_PREFIX ) ) {
				return true;
			}
		}

		if ( is_string( $callback ) && str_starts_with( $callback, 'pivot_performance_toolkit_' ) ) {
			return true;
		}

		$callback_file = $this->getCallbackFilePath( $callback );

		if ( '' === $callback_file ) {
			// Unknown/internal callback; keep it to avoid breaking core behavior.
			return true;
		}

		if ( str_starts_with( $callback_file, PIVOT_PERFORMANCE_TOOLKIT_PATH ) ) {
			return true;
		}

		// plugin_basename() strips the plugins and mu-plugins directories (wherever this
		// site relocated them) and returns any other path unchanged, so a changed result
		// means the callback lives in another plugin.
		$normalized = wp_normalize_path( $callback_file );

		if ( plugin_basename( $normalized ) !== trim( $normalized, '/' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Resolve a callback file path when available.
	 *
	 * @param mixed $callback Hook callback.
	 */
	private function getCallbackFilePath( $callback ): string {
		try {
			if ( is_array( $callback ) && isset( $callback[0], $callback[1] ) ) {
				$reflection = new \ReflectionMethod( $callback[0], (string) $callback[1] );
				return (string) $reflection->getFileName();
			}

			if ( $callback instanceof \Closure || is_string( $callback ) ) {
				$reflection = new \ReflectionFunction( $callback );
				return (string) $reflection->getFileName();
			}
		} catch ( \ReflectionException $exception ) {
			return '';
		}

		return '';
	}
}
