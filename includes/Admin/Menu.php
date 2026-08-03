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
		add_action( 'admin_head', array( $this, 'printMenuIconStyles' ) );
	}

	public function addMenuPage(): void {
		$root_page = $this->pages[ self::ROOT_SLUG ] ?? null;

		if ( ! $root_page instanceof AdminPageInterface ) {
			return;
		}

		// Register only the root menu page. All other pages are routed internally
		// via the ?section= query parameter.
		$top_level_hook = add_menu_page(
			$root_page->pageTitle(),
			__( 'Performance', 'pivot-performance-toolkit' ),
			'manage_options',
			self::ROOT_SLUG,
			array( $this, 'renderCurrentPage' ),
			PIVOT_PERFORMANCE_TOOLKIT_URL . 'src/img/pivot-performance-toolkit-logo-white.svg',
			81
		);

		if ( is_string( $top_level_hook ) ) {
			$this->page_hooks[] = $top_level_hook;
		}
	}

	public function renderCurrentPage(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		AdminShell::render( $this->pages );
	}

	public function enqueueAssets( string $hook_suffix ): void {
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
			'ptkSnippet',
			array(
				'copied'     => __( 'Copied!', 'pivot-performance-toolkit' ),
				'copyFailed' => __( 'Failed to copy. Please try again.', 'pivot-performance-toolkit' ),
				'expand'     => __( 'Expand Full Configuration', 'pivot-performance-toolkit' ),
				'collapse'   => __( 'Hide Full Configuration', 'pivot-performance-toolkit' ),
			)
		);

		wp_localize_script(
			'pivot-performance-toolkit-admin-js',
			'ptkAdmin',
			array(
				'requestFailed' => __( 'Request failed.', 'pivot-performance-toolkit' ),
				'saved'         => __( 'Saved', 'pivot-performance-toolkit' ),
				'error'         => __( 'Error', 'pivot-performance-toolkit' ),
			)
		);
	}

	public function printMenuIconStyles(): void {
		$icon_url = esc_url( PIVOT_PERFORMANCE_TOOLKIT_URL . 'src/img/pivot-performance-toolkit-logo-white.svg' );

		echo '<style id="pivot-performance-toolkit-menu-icon">#adminmenu .toplevel_page_pivot-performance-toolkit .wp-menu-image img{display:none}#adminmenu .toplevel_page_pivot-performance-toolkit .wp-menu-image{color:inherit}#adminmenu .toplevel_page_pivot-performance-toolkit .wp-menu-image:before{content:"";display:block;width:20px;height:20px;margin:1px auto 0;transform:translateY(-1px);background-color:currentColor;-webkit-mask-image:url("' . $icon_url . '");mask-image:url("' . $icon_url . '");-webkit-mask-repeat:no-repeat;mask-repeat:no-repeat;-webkit-mask-position:center;mask-position:center;-webkit-mask-size:20px 20px;mask-size:20px 20px}#adminmenu .toplevel_page_pivot-performance-toolkit.wp-has-current-submenu .wp-menu-image:before,#adminmenu .toplevel_page_pivot-performance-toolkit.current .wp-menu-image:before{transform:translateY(5px)}</style>';
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

		if ( defined( 'WP_PLUGIN_DIR' ) && str_starts_with( $callback_file, WP_PLUGIN_DIR ) ) {
			return false;
		}

		if ( defined( 'WPMU_PLUGIN_DIR' ) && str_starts_with( $callback_file, WPMU_PLUGIN_DIR ) ) {
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
