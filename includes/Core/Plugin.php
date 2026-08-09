<?php
/**
 * Main plugin bootstrap.
 *
 * @package PivotPerformanceToolkit
 */

declare(strict_types=1);

namespace PivotPerformanceToolkit\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PivotPerformanceToolkit\Admin\AdminBarMenu;
use PivotPerformanceToolkit\Admin\AdvancedRulesPage;
use PivotPerformanceToolkit\Admin\AssetsPage;
use PivotPerformanceToolkit\Admin\BrowserCacheHeadersPage;
use PivotPerformanceToolkit\Admin\CachePage;
use PivotPerformanceToolkit\Admin\CdnIntegrationsPage;
use PivotPerformanceToolkit\Admin\DashboardPage;
use PivotPerformanceToolkit\Admin\DatabasePage;
use PivotPerformanceToolkit\Admin\DatabaseTablePage;
use PivotPerformanceToolkit\Database\DatabaseOptimizer;
use PivotPerformanceToolkit\Admin\DocumentationPage;
use PivotPerformanceToolkit\Admin\FileOptimizationPage;
use PivotPerformanceToolkit\Admin\FilesystemNotices;
use PivotPerformanceToolkit\Admin\MediaOptimizationPage;
use PivotPerformanceToolkit\Admin\Menu;
use PivotPerformanceToolkit\Admin\PerformanceTest;
use PivotPerformanceToolkit\Admin\PerformancePage;
use PivotPerformanceToolkit\Admin\SettingsPage;
use PivotPerformanceToolkit\Admin\SystemStatusPage;
use PivotPerformanceToolkit\Admin\ToolsPage;
use PivotPerformanceToolkit\Cache\ObjectCacheManager;
use PivotPerformanceToolkit\Cache\PageCache;
use PivotPerformanceToolkit\Contracts\ModuleInterface;
use PivotPerformanceToolkit\Media\ImageOptimizerDetector;
use PivotPerformanceToolkit\Media\LazyLoad;
use PivotPerformanceToolkit\Integrations\CloudflareIntegration;
use PivotPerformanceToolkit\Optimization\Assets;
use PivotPerformanceToolkit\Optimization\AsyncCss;
use PivotPerformanceToolkit\Optimization\Combine;
use PivotPerformanceToolkit\Optimization\DelayedJs;

final class Plugin {

	private static ?self $instance = null;

	private bool $booted = false;

	/**
	 * @var ModuleInterface[]
	 */
	private array $modules = array();

	private Settings $settings;

	private function __construct() {
	}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		Lifecycle::maybeMigrateLegacyOptionKeys();

		$this->settings           = new Settings();
		$cloudflare               = new CloudflareIntegration( $this->settings );
		$image_optimizer_detector = new ImageOptimizerDetector();
		$database_optimizer       = new DatabaseOptimizer();
		$object_cache_manager     = new ObjectCacheManager();

		add_action( 'admin_init', array( $this->settings, 'register' ) );
		add_action( 'admin_init', array( Lifecycle::class, 'maybeUpdateDropin' ) );
		add_action( 'admin_init', array( $object_cache_manager, 'maybeUpdateDropin' ) );

		if ( is_admin() ) {
			$menu = new Menu(
				array(
					new DashboardPage( $this->settings ),
					new CachePage( $this->settings, $object_cache_manager ),
					new FileOptimizationPage( $this->settings ),
					new AssetsPage( $this->settings ),
					new MediaOptimizationPage( $this->settings, $image_optimizer_detector ),
					new PerformancePage(),
					new DatabasePage( $database_optimizer ),
					new DatabaseTablePage( $database_optimizer ),
					new SettingsPage( $this->settings ),
					new BrowserCacheHeadersPage( $this->settings ),
					new CdnIntegrationsPage( $this->settings, $cloudflare, $image_optimizer_detector ),
					new AdvancedRulesPage( $this->settings ),
					new ToolsPage( $this->settings, ToolsPage::MODE_IMPORT_EXPORT ),
					new ToolsPage( $this->settings, ToolsPage::MODE_MAINTENANCE ),
					new SystemStatusPage( $this->settings, $image_optimizer_detector ),
					new DocumentationPage(),
				)
			);
			$menu->register();
		}

		$this->modules = array(
			new FilesystemNotices(),
			new AdminBarMenu(),
			new PerformanceTest( $this->settings ),
			new PageCache( $this->settings ),
			new Assets( $this->settings ),
			new AsyncCss( $this->settings ),
			new Combine( $this->settings ),
			new DelayedJs( $this->settings ),
			new LazyLoad( $this->settings, $image_optimizer_detector ),
			$cloudflare,
		);

		foreach ( $this->modules as $module ) {
			$module->register();
		}
	}
}
