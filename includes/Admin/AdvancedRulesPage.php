<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Views\BladeEngine;

final class AdvancedRulesPage implements AdminPageInterface
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function slug(): string
    {
        return 'performance-toolkit-advanced-rules';
    }

    public function menuTitle(): string
    {
        return __('Advanced Rules', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Advanced Rules', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'sliders-horizontal';
    }

    public function renderContent(): void
    {
        echo BladeEngine::view('admin.advanced-rules-page', $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(): array
    {
        $options = $this->settings->all();

        return array(
            'options' => $options,
            'option_key' => $this->settings->optionKey(),
            'settings_updated' => isset($_GET['settings-updated']) && (string) wp_unslash($_GET['settings-updated']) === 'true',
            'woo_defaults' => array(
                '/cart',
                '/checkout',
                '/my-account',
                '/wc-api/*',
                '/?wc-ajax=*',
            ),
        );
    }
}

