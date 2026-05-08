<?php

declare(strict_types=1);

namespace PerformanceToolkit\Core;

use PerformanceToolkit\Admin\AdvancedRulesPage;
use PerformanceToolkit\Admin\CachePage;
use PerformanceToolkit\Admin\CdnIntegrationsPage;
use PerformanceToolkit\Admin\DashboardPage;
use PerformanceToolkit\Admin\DatabasePage;
use PerformanceToolkit\Database\DatabaseOptimizer;
use PerformanceToolkit\Admin\DocumentationPage;
use PerformanceToolkit\Admin\FileOptimizationPage;
use PerformanceToolkit\Admin\MediaOptimizationPage;
use PerformanceToolkit\Admin\Menu;
use PerformanceToolkit\Admin\SystemStatusPage;
use PerformanceToolkit\Admin\ToolsPage;
use PerformanceToolkit\Cache\PageCache;
use PerformanceToolkit\Contracts\ModuleInterface;
use PerformanceToolkit\Media\LazyLoad;
use PerformanceToolkit\Optimization\Assets;

final class Plugin
{
    private static ?self $instance = null;

    private bool $booted = false;

    /**
     * @var ModuleInterface[]
     */
    private array $modules = array();

    private Settings $settings;

    private function __construct()
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
        $this->settings = new Settings();

        add_action('admin_init', array($this->settings, 'register'));

        if (is_admin()) {
            $menu = new Menu(
                array(
                    new DashboardPage($this->settings),
                    new CachePage($this->settings),
                    new FileOptimizationPage($this->settings),
                    new MediaOptimizationPage($this->settings),
                    new DatabasePage(new DatabaseOptimizer()),
                    new CdnIntegrationsPage(),
                    new AdvancedRulesPage($this->settings),
                    new ToolsPage(),
                    new DocumentationPage(),
                    new SystemStatusPage($this->settings),
                )
            );
            $menu->register();
        }

        $this->modules = array(
            new PageCache($this->settings),
            new Assets($this->settings),
            new LazyLoad($this->settings),
        );

        foreach ($this->modules as $module) {
            $module->register();
        }
    }
}

