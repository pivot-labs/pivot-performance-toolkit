<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class MediaOptimizationPage implements AdminPageInterface
{
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
        ?>
        <section id="ptk-media-optimization" class="ptk-card">
            <h2><?php esc_html_e('Media Optimization', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Media optimization controls will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

