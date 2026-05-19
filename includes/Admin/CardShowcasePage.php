<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class CardShowcasePage extends BladeAdminPage
{
    public function slug(): string
    {
        return 'performance-toolkit-card-showcase';
    }

    public function menuTitle(): string
    {
        return __('Card Showcase', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Card Showcase', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'layout-dashboard';
    }

    public function view(): string
    {
        return 'admin.card-system-showcase';
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildViewData(): array
    {
        return array();
    }
}

