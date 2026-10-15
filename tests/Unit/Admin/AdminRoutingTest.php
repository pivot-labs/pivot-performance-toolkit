<?php
/**
 * Coverage for the plugin's admin routing: every screen is a real admin page
 * (admin.php?page=<slug>), registered hidden except for the root, and the
 * current screen comes from core's $plugin_page rather than the request.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use PivotPerformanceToolkit\Admin\AdminPageInterface;
use PivotPerformanceToolkit\Admin\AdminShell;
use PivotPerformanceToolkit\Admin\Menu;
use PivotPerformanceToolkit\Tests\Unit\TestCase;

final class AdminRoutingTest extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['plugin_page'] );

		parent::tearDown();
	}

	private function fakePage( string $slug ): AdminPageInterface {
		return new class( $slug ) implements AdminPageInterface {
			public function __construct( private string $slug ) {}

			public function slug(): string {
				return $this->slug;
			}

			public function menuTitle(): string {
				return 'Menu ' . $this->slug;
			}

			public function pageTitle(): string {
				return 'Title ' . $this->slug;
			}

			public function iconKey(): string {
				return '';
			}

			public function renderContent(): void {}
		};
	}

	public function test_page_url_targets_the_registered_page_slug_with_no_section_or_tab(): void {
		Functions\when( 'admin_url' )->alias( static fn( string $path = '' ) => 'https://example.com/wp-admin/' . $path );
		Functions\when( 'add_query_arg' )->alias( static fn( string $key, string $value, string $url ) => $url . '?' . $key . '=' . $value );

		self::assertSame(
			'https://example.com/wp-admin/admin.php?page=pivot-performance-toolkit-database-table',
			AdminShell::pageUrl( 'pivot-performance-toolkit-database-table' )
		);
	}

	public function test_section_for_page_groups_pages_and_defaults_to_overview(): void {
		self::assertSame( 'overview', AdminShell::sectionForPage( 'pivot-performance-toolkit' ) );
		self::assertSame( 'caching', AdminShell::sectionForPage( 'pivot-performance-toolkit-cdn-integrations' ) );
		self::assertSame( 'optimization', AdminShell::sectionForPage( 'pivot-performance-toolkit-performance' ) );
		self::assertSame( 'database', AdminShell::sectionForPage( 'pivot-performance-toolkit-database-table' ) );
		self::assertSame( 'system', AdminShell::sectionForPage( 'pivot-performance-toolkit-system-maintenance' ) );
		self::assertSame( 'settings', AdminShell::sectionForPage( 'pivot-performance-toolkit-settings' ) );
		self::assertSame( 'overview', AdminShell::sectionForPage( 'not-a-plugin-page' ) );
	}

	public function test_menu_registers_root_visibly_and_every_other_page_hidden(): void {
		$pages = array(
			$this->fakePage( 'pivot-performance-toolkit' ),
			$this->fakePage( 'pivot-performance-toolkit-cache' ),
			$this->fakePage( 'pivot-performance-toolkit-database' ),
		);

		if ( ! defined( 'PIVOT_PERFORMANCE_TOOLKIT_URL' ) ) {
			define( 'PIVOT_PERFORMANCE_TOOLKIT_URL', 'https://example.com/wp-content/plugins/pivot-performance-toolkit/' );
		}

		Functions\when( '__' )->returnArg();
		Functions\expect( 'add_menu_page' )
			->once()
			->with( 'Title pivot-performance-toolkit', \Mockery::any(), 'manage_options', 'pivot-performance-toolkit', \Mockery::any(), \Mockery::any(), \Mockery::any() )
			->andReturn( 'toplevel_page_pivot-performance-toolkit' );

		$hidden = array();
		Functions\when( 'add_submenu_page' )->alias(
			static function ( $parent, $page_title, $menu_title, $capability, $slug ) use ( &$hidden ) {
				$hidden[] = array( $parent, $capability, $slug );

				return 'admin_page_' . $slug;
			}
		);

		( new Menu( $pages ) )->addMenuPage();

		self::assertSame(
			array(
				array( null, 'manage_options', 'pivot-performance-toolkit-cache' ),
				array( null, 'manage_options', 'pivot-performance-toolkit-database' ),
			),
			$hidden
		);
	}

	public function test_hidden_pages_keep_the_root_menu_highlighted(): void {
		$menu = new Menu( array( $this->fakePage( 'pivot-performance-toolkit' ), $this->fakePage( 'pivot-performance-toolkit-cache' ) ) );

		$GLOBALS['plugin_page'] = 'pivot-performance-toolkit-cache';
		self::assertSame( 'pivot-performance-toolkit', $menu->highlightParentMenu( 'index.php' ) );

		$GLOBALS['plugin_page'] = 'some-other-plugin';
		self::assertSame( 'index.php', $menu->highlightParentMenu( 'index.php' ) );
	}
}
