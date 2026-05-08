<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class FileOptimizationPage implements AdminPageInterface
{
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
        ?>
        <section id="ptk-file-optimization" class="ptk-card">
            <h2><?php esc_html_e('File Optimization', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('File optimization controls will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

