<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class AdvancedRulesPage implements AdminPageInterface
{
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
        ?>
        <section id="ptk-advanced-rules" class="ptk-card">
            <h2><?php esc_html_e('Advanced Rules', 'performance-toolkit'); ?></h2>
            <p><?php esc_html_e('Advanced cache and optimization rule controls will be added here.', 'performance-toolkit'); ?></p>
        </section>
        <?php
    }
}

