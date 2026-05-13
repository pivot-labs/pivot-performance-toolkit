<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Core\Settings;
use PerformanceToolkit\Media\ImageOptimizerDetector;
use PerformanceToolkit\Views\BladeEngine;

final class MediaOptimizationPage implements AdminPageInterface
{
    private Settings $settings;

    private ImageOptimizerDetector $optimizer_detector;

    public function __construct(Settings $settings, ImageOptimizerDetector $optimizer_detector)
    {
        $this->settings           = $settings;
        $this->optimizer_detector = $optimizer_detector;
    }

    public function slug(): string
    {
        return 'performance-toolkit-media-optimization';
    }

    public function menuTitle(): string
    {
        return __('Media Optimization', 'performance-toolkit');
    }

    public function pageTitle(): string
    {
        return __('Performance Toolkit Media Optimization', 'performance-toolkit');
    }

    public function iconKey(): string
    {
        return 'image';
    }

    public function renderContent(): void
    {
        echo BladeEngine::view('admin.media-optimization-page', $this->getViewData());
    }

    /**
     * @return array<string, mixed>
     */
    private function getViewData(): array
    {
        $active_optimizers  = $this->optimizer_detector->activeOptimizers();
        $lazyload_providers = $this->optimizer_detector->activeLazyLoadProviders();

        return array(
            'options'              => $this->settings->all(),
            'option_key'           => $this->settings->optionKey(),
            'settings_updated'     => isset($_GET['settings-updated']) && (string) wp_unslash($_GET['settings-updated']) === 'true',
            'active_optimizers'    => $active_optimizers,
            'lazyload_providers'   => $lazyload_providers,
            'external_lazyload_on' => $lazyload_providers !== array(),
            'optimizer_status'     => $active_optimizers === array()
                ? __('None detected', 'performance-toolkit')
                : implode(', ', $active_optimizers),
        );
    }
}
