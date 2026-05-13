<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Views\BladeEngine;

final class FileOptimizationPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-file-optimization';
    }

    public function menuTitle(): string
    {
        return __('File Optimization', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit File Optimization', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'dashicons-media-code';
    }

    public function renderContent(): void
    {
        echo BladeEngine::view('admin.file-optimization-page', $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(): array
    {
        return array(
            'options'          => $this->settings->all(),
            'option_key'       => $this->settings->optionKey(),
            'settings_updated' => isset($_GET['settings-updated']) && (string) wp_unslash($_GET['settings-updated']) === 'true',
        );
    }
}

