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
            <button id="ptk-sidebar-toggle" class="ptk-sidebar-toggle" aria-expanded="false" aria-controls="ptk-sidebar">
                <span class="screen-reader-text"><?php esc_html_e('Toggle navigation', 'performance-toolkit'); ?></span>
                <span class="ptk-sidebar-toggle-icon" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
            </button>
            <div class="ptk-shell">
                <aside id="ptk-sidebar" class="ptk-sidebar" aria-label="<?php esc_attr_e('Performance Toolkit sections', 'performance-toolkit'); ?>">
                    <nav class="ptk-nav">
                        <?php foreach ($pages as $page) : ?>
                            <?php
                            $is_active = $page->slug() === $current_page->slug();
                            $page_url = add_query_arg(
                                array('page' => $page->slug()),
                                admin_url('admin.php')
                            );
                            ?>
                            <a class="ptk-nav-link <?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($page_url); ?>">
                                <span class="ptk-icon" aria-hidden="true"><?php echo LucideIcons::render($page->iconKey()); ?></span>
                                <span><?php echo esc_html($page->menuTitle()); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    <div class="ptk-sidebar-footer">
                        <strong><?php esc_html_e('MVP', 'performance-toolkit'); ?></strong>
                        <span><?php echo esc_html(PERFORMANCE_TOOLKIT_VERSION); ?></span>
                    </div>
                </aside>

                <main class="ptk-content">
                    <?php $current_page->renderContent(); ?>
                </main>
            </div>
        </div>
        <?php
    }
}

