<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

final class AdminShell
{
    /**
     * @param AdminPageInterface[] $pages
     */
    public static function render(AdminPageInterface $current_page, array $pages): void
    {
        ?>
        <div class="wrap ptk-wrap">
            <h1 class="ptk-page-title">
                <img src="<?php echo esc_url(PERFORMANCE_TOOLKIT_URL . 'assets/img/performance-toolkit.svg'); ?>" alt="" class="ptk-page-title-icon" />
                <span><?php esc_html_e('Performance Toolkit', 'performance-toolkit'); ?></span>
            </h1>
            <hr class="wp-header-end">
            <div class="ptk-shell">

                <main class="ptk-content">
                    <?php $current_page->renderContent(); ?>
                </main>
            </div>
        </div>
        <?php
    }
}

