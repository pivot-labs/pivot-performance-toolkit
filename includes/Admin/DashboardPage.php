<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Utils\FilesystemCheck;

final class DashboardPage extends BladeAdminPage
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit';
    }

    public function menuTitle(): string
    {
        return __('Dashboard', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Dashboard', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'layout-dashboard';
    }


    public function view(): string
    {
        return 'admin.dashboard-page';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData(): array
    {
        return array(
            'options' => $this->settings->all(),
            'fs_status' => FilesystemCheck::getCachedStatus(),
        );
    }
}

