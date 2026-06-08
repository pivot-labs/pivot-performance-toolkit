<?php
/**
 * Main plugin bootstrap.
 *
 * @package PerformanceToolkit
 */

declare(strict_types=1);

namespace PerformanceToolkit\Core;

use PerformanceToolkit\Admin\AdminBarMenu;
use PerformanceToolkit\Admin\AdvancedRulesPage;
use PerformanceToolkit\Admin\AssetsPage;
use PerformanceToolkit\Admin\BrowserCacheHeadersPage;
use PerformanceToolkit\Admin\CachePage;
use PerformanceToolkit\Admin\CdnIntegrationsPage;
use PerformanceToolkit\Admin\DashboardPage;
use PerformanceToolkit\Admin\DatabasePage;
use PerformanceToolkit\Admin\DatabaseTablePage;
use PerformanceToolkit\Database\DatabaseOptimizer;
use PerformanceToolkit\Admin\DocumentationPage;
use PerformanceToolkit\Admin\FileOptimizationPage;
use PerformanceToolkit\Admin\FilesystemNotices;
use PerformanceToolkit\Admin\MediaOptimizationPage;
use PerformanceToolkit\Admin\Menu;
use PerformanceToolkit\Admin\PerformanceTest;
use PerformanceToolkit\Admin\PerformancePage;
use PerformanceToolkit\Admin\SettingsPage;
use PerformanceToolkit\Admin\SystemStatusPage;
use PerformanceToolkit\Admin\ToolsPage;
use PerformanceToolkit\Cache\PageCache;
use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Media\ImageOptimizerDetector;
use PerformanceToolkit\Media\LazyLoad;
use PerformanceToolkit\Integrations\CloudflareIntegration;
use PerformanceToolkit\Optimization\Assets;

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

		$this->booted             = true;
		$this->settings           = new Settings();
		$cloudflare               = new CloudflareIntegration( $this->settings );
		$image_optimizer_detector = new ImageOptimizerDetector();
		$database_optimizer       = new DatabaseOptimizer();

		add_action( 'admin_init', array( $this->settings, 'register' ) );

		if ( is_admin() ) {
			$menu = new Menu(
				array(
					new DashboardPage( $this->settings ),
					new CachePage( $this->settings ),
					new FileOptimizationPage( $this->settings ),
					new AssetsPage( $this->settings ),
					new MediaOptimizationPage( $this->settings, $image_optimizer_detector ),
					new PerformancePage(),
					new DatabasePage( $database_optimizer ),
					new DatabaseTablePage( $database_optimizer ),
					new SettingsPage( $this->settings ),
					new BrowserCacheHeadersPage( $this->settings ),
					new CdnIntegrationsPage( $this->settings, $cloudflare ),
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
			new LazyLoad( $this->settings, $image_optimizer_detector ),
			$cloudflare,
		);

		foreach ( $this->modules as $module ) {
			$module->register();
		}
	}
}
