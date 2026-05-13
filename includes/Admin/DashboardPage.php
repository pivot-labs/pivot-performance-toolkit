<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Utils\FilesystemCheck;
use PerformanceToolkit\Views\BladeEngine;

final class DashboardPage implements AdminPageInterface
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

    public function renderContent(): void
    {
        echo BladeEngine::view('admin.dashboard-page', $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(): array
    {
        return array(
            'options' => $this->settings->all(),
            'fs_status' => FilesystemCheck::getCachedStatus(),
        );
    }
}

