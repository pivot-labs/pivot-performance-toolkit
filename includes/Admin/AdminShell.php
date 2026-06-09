<?php
/**
 * Admin page shell renderer.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Views\BladeEngine;

final class AdminShell {

	private const PAGE_BY_SECTION = array(
		'overview'     => 'performance-toolkit',
		'caching'      => 'performance-toolkit-cache',
		'optimization' => 'performance-toolkit-file-optimization',
		'database'     => 'performance-toolkit-database',
		'settings'     => 'performance-toolkit-settings',
		'system'       => 'performance-toolkit-system-status',
	);

	private const SECTION_LABELS = array(
		'overview'     => 'Overview',
		'caching'      => 'Caching',
		'optimization' => 'Optimization',
		'database'     => 'Database',
		'settings'     => 'Settings',
		'system'       => 'System',
	);

	private const SECTION_DESCRIPTIONS = array(
		'overview'     => 'View performance highlights and quick status details for your site.',
		'caching'      => 'Configure page and browser caching behavior for faster page delivery.',
		'optimization' => 'Tune file and media optimization settings to reduce payload size.',
		'database'     => 'Review and clean database overhead to keep queries fast.',
		'settings'     => 'Manage global plugin settings and defaults.',
		'system'       => 'Inspect status and manage import/export or maintenance operations.',
	);

	private const PAGE_HEADINGS = array(
		'performance-toolkit-card-showcase' => 'Card Showcase',
	);

	private const PAGE_DESCRIPTIONS = array(
		'performance-toolkit-card-showcase' => 'Preview reusable admin card layouts for settings, actions, and workflow-based interfaces.',
	);

	/**
	 * @param AdminPageInterface[] $pages
	 */
	public static function render( array $pages ): void {
		// Determine current section from query parameter, default to 'overview'
		$current_section = isset( $_GET['section'] )
			? sanitize_key( (string) wp_unslash( $_GET['section'] ) )
			: 'overview';

		// If a specific tab/page slug is provided, use that; otherwise use the default page for the section
		$tab_override = isset( $_GET['tab'] )
			? sanitize_key( (string) wp_unslash( $_GET['tab'] ) )
			: '';

		if ( '' !== $tab_override && isset( $pages[ $tab_override ] ) ) {
			$current_page_slug = $tab_override;
		} else {
			$current_page_slug = self::PAGE_BY_SECTION[ $current_section ] ?? 'performance-toolkit';
		}

		$current_page = $pages[ $current_page_slug ] ?? null;

		if ( ! $current_page instanceof AdminPageInterface ) {
			return;
		}

		$page_layout = 'two-col';
		if ( 'performance-toolkit' === $current_page_slug ) {
			$page_layout = 'overview';
		} elseif ( in_array( $current_page_slug, array( 'performance-toolkit-system-import-export', 'performance-toolkit-system-maintenance' ), true ) ) {
			$page_layout = 'tools';
		} elseif ( 'performance-toolkit-card-showcase' === $current_page_slug ) {
			$page_layout = 'overview';
		}

		$shell_data = array(
			'icon_url'         => PERFORMANCE_TOOLKIT_URL . 'src/img/performance-toolkit-logo.png',
			'plugin_version'   => defined( 'PERFORMANCE_TOOLKIT_VERSION' ) ? PERFORMANCE_TOOLKIT_VERSION : '',
			'help_url'         => 'https://docs.wpperformancetoolkit.com/',
			'primary_nav'      => self::buildPrimaryNav( $current_section ),
			'secondary_nav'    => self::buildSecondaryNav( $current_section, $current_page_slug ),
			'page_heading'     => self::resolvePageHeading( $current_page_slug, $current_section ),
			'page_description' => self::resolvePageDescription( $current_page_slug, $current_section ),
			'page_layout'      => $page_layout,
			'page_view'        => null,
			'page_data'        => array(),
			'content'          => '',
		);

		if ( $current_page instanceof AdminPageViewInterface ) {
			$shell_data['page_view'] = $current_page->view();
			$shell_data['page_data'] = $current_page->viewData();
		} else {
			ob_start();
			$current_page->renderContent();
			$shell_data['content'] = (string) ob_get_clean();
		}

		echo BladeEngine::view( 'admin.shell', $shell_data );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function buildPrimaryNav( string $current_section ): array {
		$items = array(
			array(
				'key'   => 'overview',
				'label' => __( 'Overview', 'performance-toolkit' ),
				'icon'  => 'layout-dashboard',
			),
			array(
				'key'   => 'caching',
				'label' => __( 'Caching', 'performance-toolkit' ),
				'icon'  => 'rocket',
			),
			array(
				'key'   => 'optimization',
				'label' => __( 'Optimization', 'performance-toolkit' ),
				'icon'  => 'sliders-horizontal',
			),
			array(
				'key'   => 'database',
				'label' => __( 'Database', 'performance-toolkit' ),
				'icon'  => 'database',
			),
			array(
				'key'   => 'system',
				'label' => __( 'System', 'performance-toolkit' ),
				'icon'  => 'wrench',
			),
			array(
				'key'   => 'settings',
				'label' => __( 'Settings', 'performance-toolkit' ),
				'icon'  => 'dashicons-admin-settings',
			),
		);

		foreach ( $items as &$item ) {
			$item['url']    = add_query_arg( 'section', $item['key'], admin_url( 'admin.php?page=performance-toolkit' ) );
			$item['active'] = $item['key'] === $current_section;
		}
		unset( $item );

		return $items;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	private static function buildSecondaryNav( string $current_section, string $current_page_slug ): array {
		if ( 'caching' === $current_section ) {
			return self::withSecondaryState(
				'caching',
				array(
					array(
						'slug'  => 'performance-toolkit-cache',
						'label' => __( 'Page Cache', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-browser-cache',
						'label' => __( 'Browser Cache', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-cdn-integrations',
						'label' => __( 'CDN', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-advanced-rules',
						'label' => __( 'Rules', 'performance-toolkit' ),
					),
				),
				$current_page_slug
			);
		}

		if ( 'optimization' === $current_section ) {
			return self::withSecondaryState(
				'optimization',
				array(
					array(
						'slug'  => 'performance-toolkit-file-optimization',
						'label' => __( 'Files', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-media-optimization',
						'label' => __( 'Media', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-performance',
						'label' => __( 'Performance', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-assets',
						'label' => __( 'Assets', 'performance-toolkit' ),
					),
				),
				$current_page_slug
			);
		}

		if ( 'database' === $current_section ) {
			return self::withSecondaryState(
				'database',
				array(
					array(
						'slug'  => 'performance-toolkit-database',
						'label' => __( 'Overview', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-database-table',
						'label' => __( 'Tables', 'performance-toolkit' ),
					),
				),
				$current_page_slug
			);
		}

		if ( 'system' === $current_section ) {
			return self::withSecondaryState(
				'system',
				array(
					array(
						'slug'  => 'performance-toolkit-system-status',
						'label' => __( 'Status', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-system-import-export',
						'label' => __( 'Import/Export', 'performance-toolkit' ),
					),
					array(
						'slug'  => 'performance-toolkit-system-maintenance',
						'label' => __( 'Maintenance', 'performance-toolkit' ),
					),
				),
				$current_page_slug
			);
		}

		if ( 'settings' === $current_section ) {
			return array();
		}

		return array();
	}

	/**
	 * @param array<int, array{slug:string,label:string}> $items
	 * @return array<int, array<string, mixed>>
	 */
	private static function withSecondaryState( string $section, array $items, string $current_page_slug ): array {
		$base_url = add_query_arg( 'section', $section, admin_url( 'admin.php?page=performance-toolkit' ) );

		foreach ( $items as &$item ) {
			$item['url']    = add_query_arg( 'tab', $item['slug'], $base_url );
			$item['active'] = $item['slug'] === $current_page_slug;
		}
		unset( $item );

		return $items;
	}

	private static function resolvePageHeading( string $current_page_slug, string $current_section ): string {
		if ( 'performance-toolkit-card-showcase' === $current_page_slug ) {
			return __( 'Card Showcase', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-status' === $current_page_slug ) {
			return __( 'System Status', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-import-export' === $current_page_slug ) {
			return __( 'Import/Export', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-maintenance' === $current_page_slug ) {
			return __( 'Maintenance', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-performance' === $current_page_slug ) {
			return __( 'Performance', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-assets' === $current_page_slug ) {
			return __( 'Assets', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-settings' === $current_page_slug ) {
			return __( 'Settings', 'performance-toolkit' );
		}

		switch ( $current_section ) {
			case 'caching':
				return __( 'Caching', 'performance-toolkit' );
			case 'optimization':
				return __( 'Optimization', 'performance-toolkit' );
			case 'database':
				return __( 'Database', 'performance-toolkit' );
			case 'settings':
				return __( 'Settings', 'performance-toolkit' );
			case 'system':
				return __( 'System', 'performance-toolkit' );
			case 'overview':
			default:
				return __( 'Overview', 'performance-toolkit' );
		}
	}

	private static function resolvePageDescription( string $current_page_slug, string $current_section ): string {
		if ( 'performance-toolkit-card-showcase' === $current_page_slug ) {
			return __( 'Preview reusable admin card layouts for settings, actions, and workflow-based interfaces.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-status' === $current_page_slug ) {
			return __( 'Inspect runtime, server, and filesystem health signals for troubleshooting.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-import-export' === $current_page_slug ) {
			return __( 'Export and import configuration packages for migrations, backups, and standardized deployments.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-system-maintenance' === $current_page_slug ) {
			return __( 'Run maintenance operations such as clearing generated assets, setting uninstall policy, and resetting defaults.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-performance' === $current_page_slug ) {
			return __( 'Run website statistics tests and review performance metrics for selected pages and posts.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-assets' === $current_page_slug ) {
			return __( 'Configure CSS and JavaScript delivery rules, exclusions, and optimization behavior.', 'performance-toolkit' );
		}

		if ( 'performance-toolkit-settings' === $current_page_slug ) {
			return __( 'Manage global plugin settings and defaults.', 'performance-toolkit' );
		}

		switch ( $current_section ) {
			case 'caching':
				return __( 'Configure page and browser caching behavior for faster page delivery.', 'performance-toolkit' );
			case 'optimization':
				return __( 'Tune file and media optimization settings to reduce payload size.', 'performance-toolkit' );
			case 'database':
				return __( 'Review and clean database overhead to keep queries fast.', 'performance-toolkit' );
			case 'settings':
				return __( 'Manage global plugin settings and defaults.', 'performance-toolkit' );
			case 'system':
				return __( 'Export, import, and maintenance utilities for advanced site operations.', 'performance-toolkit' );
			case 'overview':
			default:
				return __( 'View performance highlights and quick status details for your site.', 'performance-toolkit' );
		}
	}
}
