<?php

declare(strict_types=1);

namespace PerformanceToolkit\Admin;

use PerformanceToolkit\Views\BladeEngine;

final class AdminShell
{
    /**
     * @param AdminPageInterface[] $pages
     */
    public static function render(AdminPageInterface $current_page, array $pages): void
    {
        $shell_data = array(
            'icon_url'  => PERFORMANCE_TOOLKIT_URL . 'assets/img/performance-toolkit.svg',
            'page_view' => null,
            'page_data' => array(),
            'content'   => '',
        );

        if ($current_page instanceof AdminPageViewInterface) {
            $shell_data['page_view'] = $current_page->view();
            $shell_data['page_data'] = $current_page->viewData();
        } else {
            ob_start();
            $current_page->renderContent();
            $shell_data['content'] = (string) ob_get_clean();
        }

        echo BladeEngine::view('admin.shell', $shell_data);
    }
}
